<?php
/**
 * MeGeMIT — HubSpot Member Migration Tool
 * Sube CSV de HubSpot y sincroniza el estado del formulario de miembros existentes.
 *
 * Acceso: solo administradores WP autenticados.
 * Uso: /hs-migration.php (desde el root del sitio)
 */

define('MGMIT_HS_MIG_REQUIRED_FIELDS', ['Courtesy DE', 'First Name', 'Last Name', 'Main speciality', 'Origin']);

// --- Bootstrap WordPress ---
require_once dirname(__FILE__) . '/wp-load.php';

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Acceso denegado. Debes estar autenticado como administrador.');
}

$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mgmit_mig_submit'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mgmit_hs_mig')) {
        wp_die('Nonce inválido. Recarga la página e inténtalo de nuevo.');
    }
    $dry_run = !empty($_POST['dry_run']);
    $results = mgmit_mig_process_csv($dry_run);
}

// ---------------------------------------------------------------------------

function mgmit_mig_process_csv($dry_run = false) {
    if (!isset($_FILES['hs_csv']) || $_FILES['hs_csv']['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error al subir el archivo. Código: ' . ($_FILES['hs_csv']['error'] ?? '?')];
    }

    $handle = fopen($_FILES['hs_csv']['tmp_name'], 'r');
    if (!$handle) {
        return ['error' => 'No se pudo abrir el archivo CSV.'];
    }

    // Leer cabeceras y limpiar BOM UTF-8
    $raw = fgetcsv($handle);
    if ($raw === false) {
        fclose($handle);
        return ['error' => 'CSV vacío o formato inválido.'];
    }
    $raw[0] = ltrim($raw[0], "\xEF\xBB\xBF");

    // Mapa: nombre_columna_lowercase => índice
    $col = [];
    foreach ($raw as $i => $name) {
        $col[strtolower(trim($name))] = $i;
    }

    // Localizar columna email
    $email_col = isset($col['email']) ? $col['email'] : (isset($col['email address']) ? $col['email address'] : null);
    if ($email_col === null) {
        fclose($handle);
        return ['error' => 'Columna "Email" no encontrada en el CSV.'];
    }

    // Localizar columnas requeridas
    $req_cols = [];
    $missing_headers = [];
    foreach (MGMIT_HS_MIG_REQUIRED_FIELDS as $field) {
        $key = strtolower($field);
        if (isset($col[$key])) {
            $req_cols[$field] = $col[$key];
        } else {
            $missing_headers[] = $field;
        }
    }
    if (!empty($missing_headers)) {
        fclose($handle);
        return ['error' => 'Columnas no encontradas en el CSV: ' . implode(', ', $missing_headers)];
    }

    // Construir índice HubSpot: email_lowercase => ['complete' => bool, 'fields' => [...], 'dupes' => int]
    // Lógica AND para duplicados: si CUALQUIER ocurrencia tiene campos vacíos → incompleto
    $hs = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (!isset($row[$email_col])) continue;
        $email = strtolower(trim($row[$email_col]));
        if ($email === '') continue;

        $field_values = [];
        $complete = true;
        foreach ($req_cols as $field => $idx) {
            $val = isset($row[$idx]) ? trim($row[$idx]) : '';
            $field_values[$field] = $val;
            if ($val === '') {
                $complete = false;
            }
        }

        if (!isset($hs[$email])) {
            $hs[$email] = ['complete' => $complete, 'fields' => $field_values, 'dupes' => 1];
        } else {
            // AND: si alguna ocurrencia es incompleta, el resultado final es incompleto
            $hs[$email]['complete'] = $hs[$email]['complete'] && $complete;
            $hs[$email]['dupes']++;
            // Conservar los valores del registro más completo para mostrar en dry run
            if ($complete) {
                $hs[$email]['fields'] = $field_values;
            }
        }
    }
    fclose($handle);

    // Procesar usuarios WP
    $users = get_users(['fields' => ['ID', 'user_email'], 'number' => -1]);

    $stats = [
        'dry_run'        => $dry_run,
        'total'          => count($users),
        'completed'      => 0,
        'legacy_pending' => 0,
        'skipped'        => 0,
        'not_in_csv'     => 0,
        'rows'           => [],
    ];

    foreach ($users as $u) {
        $email = strtolower(trim($u->user_email));
        $current = get_user_meta($u->ID, 'mgmit_hs_details_pending', true);

        if ($current === '0') {
            $stats['skipped']++;
            if ($dry_run) {
                $stats['rows'][] = [
                    'email'   => $u->user_email,
                    'action'  => 'omitido',
                    'reason'  => 'Ya completado',
                    'fields'  => [],
                    'dupes'   => 0,
                ];
            }
            continue;
        }

        if (!isset($hs[$email])) {
            $stats['not_in_csv']++;
            $stats['legacy_pending']++;
            if (!$dry_run) {
                update_user_meta($u->ID, 'mgmit_hs_legacy_pending', '1');
            }
            if ($dry_run) {
                $stats['rows'][] = [
                    'email'   => $u->user_email,
                    'action'  => 'legacy_pending',
                    'reason'  => 'No encontrado en CSV',
                    'fields'  => [],
                    'dupes'   => 0,
                ];
            }
            continue;
        }

        $entry = $hs[$email];

        if ($entry['complete'] === true) {
            $stats['completed']++;
            if (!$dry_run) {
                update_user_meta($u->ID, 'mgmit_hs_details_pending', '0');
                delete_user_meta($u->ID, 'mgmit_hs_legacy_pending');
            }
            if ($dry_run) {
                $stats['rows'][] = [
                    'email'   => $u->user_email,
                    'action'  => 'completado',
                    'reason'  => $entry['dupes'] > 1 ? 'Campos rellenos (' . $entry['dupes'] . ' registros en CSV)' : 'Campos rellenos',
                    'fields'  => $entry['fields'],
                    'dupes'   => $entry['dupes'],
                ];
            }
        } else {
            $stats['legacy_pending']++;
            if (!$dry_run) {
                update_user_meta($u->ID, 'mgmit_hs_legacy_pending', '1');
            }
            if ($dry_run) {
                $stats['rows'][] = [
                    'email'   => $u->user_email,
                    'action'  => 'legacy_pending',
                    'reason'  => $entry['dupes'] > 1 ? 'Campos vacíos (' . $entry['dupes'] . ' registros en CSV)' : 'Campos vacíos',
                    'fields'  => $entry['fields'],
                    'dupes'   => $entry['dupes'],
                ];
            }
        }
    }

    return $stats;
}

// ---------------------------------------------------------------------------
$nonce = wp_create_nonce('mgmit_hs_mig');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>HubSpot Member Migration — MeGeMIT</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f0f0f1; margin: 0; padding: 40px 20px; }
  .wrap { max-width: 960px; margin: 0 auto; }
  h1 { color: #1d2327; font-size: 23px; margin-bottom: 6px; }
  .subtitle { color: #50575e; margin-bottom: 30px; }
  .card { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 24px 28px; margin-bottom: 24px; }
  .card h2 { font-size: 15px; margin: 0 0 12px; color: #1d2327; }
  ul { margin: 0 0 0 18px; padding: 0; color: #3c434a; }
  li { margin-bottom: 4px; }
  code { background: #f6f7f7; padding: 1px 5px; border-radius: 3px; font-size: 12px; }
  .notice { padding: 14px 18px; border-left: 4px solid #ccc; border-radius: 0 4px 4px 0; margin-bottom: 24px; }
  .notice.success { background: #edfaef; border-color: #00a32a; }
  .notice.warning { background: #fcf9e8; border-color: #dba617; }
  .notice.error { background: #fce8e8; border-color: #d63638; }
  .notice ul { margin-top: 8px; }
  label { display: block; font-weight: 600; margin-bottom: 6px; color: #1d2327; }
  input[type=file] { display: block; margin-bottom: 6px; }
  .desc { font-size: 13px; color: #646970; margin: 0; }
  .btn { background: #2271b1; color: #fff; border: none; border-radius: 3px; padding: 10px 20px; font-size: 14px; cursor: pointer; }
  .btn:hover { background: #135e96; }
  .btn.dry { background: #6c757d; }
  .btn.dry:hover { background: #545b62; }
  .legend { font-size: 13px; color: #50575e; margin-top: 14px; line-height: 1.6; }
  .dry-label { display: flex; align-items: center; gap: 8px; font-weight: normal; margin-bottom: 16px; cursor: pointer; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
  th { background: #f6f7f7; text-align: left; padding: 8px 10px; border-bottom: 2px solid #c3c4c7; white-space: nowrap; }
  td { padding: 7px 10px; border-bottom: 1px solid #f0f0f1; vertical-align: top; }
  tr:hover td { background: #f9f9f9; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
  .badge.completado { background: #edfaef; color: #00693a; }
  .badge.legacy_pending { background: #fcf9e8; color: #7a5500; }
  .badge.omitido { background: #f0f0f1; color: #50575e; }
  .field-ok { color: #00693a; }
  .field-empty { color: #d63638; font-style: italic; }
  .dupe-warn { color: #dba617; font-size: 11px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>HubSpot Member Migration</h1>
  <p class="subtitle">Sincroniza el estado del formulario de miembros existentes a partir de la exportación CSV de HubSpot.</p>

  <?php if ($results !== null): ?>
    <?php if (isset($results['error'])): ?>
      <div class="notice error"><strong>Error:</strong> <?php echo esc_html($results['error']); ?></div>
    <?php elseif ($results['dry_run']): ?>
      <div class="notice warning">
        <strong>Modo simulación — ningún cambio aplicado</strong>
        <ul>
          <li>👤 Usuarios WP analizados: <strong><?php echo $results['total']; ?></strong></li>
          <li>✅ Serían marcados como completados: <strong><?php echo $results['completed']; ?></strong></li>
          <li>⚠️ Serían marcados como pendiente (soft): <strong><?php echo $results['legacy_pending']; ?></strong>
            (<?php echo $results['not_in_csv']; ?> no están en el CSV)</li>
          <li>⏭️ Serían omitidos (ya completados): <strong><?php echo $results['skipped']; ?></strong></li>
        </ul>
      </div>
      <?php if (!empty($results['rows'])): ?>
      <div class="card" style="overflow-x:auto;">
        <h2>Detalle por usuario</h2>
        <table>
          <thead>
            <tr>
              <th>Email</th>
              <th>Acción</th>
              <th>Motivo</th>
              <?php foreach (MGMIT_HS_MIG_REQUIRED_FIELDS as $f): ?>
                <th><?php echo esc_html($f); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results['rows'] as $r): ?>
            <tr>
              <td><?php echo esc_html($r['email']); ?></td>
              <td><span class="badge <?php echo esc_attr($r['action']); ?>"><?php echo esc_html($r['action']); ?></span></td>
              <td>
                <?php echo esc_html($r['reason']); ?>
                <?php if ($r['dupes'] > 1): ?>
                  <br><span class="dupe-warn">⚠ <?php echo $r['dupes']; ?> entradas en CSV</span>
                <?php endif; ?>
              </td>
              <?php foreach (MGMIT_HS_MIG_REQUIRED_FIELDS as $f): ?>
                <td>
                  <?php if (!empty($r['fields'])): ?>
                    <?php if ($r['fields'][$f] !== ''): ?>
                      <span class="field-ok"><?php echo esc_html(mb_strimwidth($r['fields'][$f], 0, 30, '…')); ?></span>
                    <?php else: ?>
                      <span class="field-empty">vacío</span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="field-empty">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="notice success">
        <strong>Proceso completado</strong>
        <ul>
          <li>👤 Usuarios WP procesados: <strong><?php echo $results['total']; ?></strong></li>
          <li>✅ Marcados como completados: <strong><?php echo $results['completed']; ?></strong></li>
          <li>⚠️ Marcados como pendiente (soft): <strong><?php echo $results['legacy_pending']; ?></strong>
            (<?php echo $results['not_in_csv']; ?> no estaban en el CSV)</li>
          <li>⏭️ Omitidos (ya completados): <strong><?php echo $results['skipped']; ?></strong></li>
        </ul>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="card">
    <h2>Campos verificados en el CSV</h2>
    <ul>
      <?php foreach (MGMIT_HS_MIG_REQUIRED_FIELDS as $f): ?>
        <li><code><?php echo esc_html($f); ?></code></li>
      <?php endforeach; ?>
    </ul>
    <p class="legend">
      ✅ Todos los campos rellenos → <code>mgmit_hs_details_pending = 0</code><br>
      ⚠️ Campos vacíos o email no en CSV → <code>mgmit_hs_legacy_pending = 1</code><br>
      ⏭️ Usuarios con <code>pending = 0</code> no se modifican.<br>
      Si el mismo email aparece varias veces en el CSV, se aplica lógica AND: si cualquier entrada tiene campos vacíos, se considera incompleto.
    </p>
  </div>

  <div class="card">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
      <label for="hs_csv">Archivo CSV exportado de HubSpot</label>
      <input type="file" id="hs_csv" name="hs_csv" accept=".csv" required>
      <p class="desc">Exportación directa de contactos desde HubSpot (UTF-8 o con BOM).</p>
      <br>
      <label class="dry-label">
        <input type="checkbox" name="dry_run" value="1" checked>
        Modo simulación (ver resultados sin aplicar cambios)
      </label>
      <button type="submit" name="mgmit_mig_submit" class="btn">Procesar CSV</button>
    </form>
  </div>
</div>
</body>
</html>
