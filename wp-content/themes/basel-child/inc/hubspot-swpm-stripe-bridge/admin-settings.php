<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'mgmit_bridge_admin_menu' );
add_action( 'admin_init', 'mgmit_bridge_admin_settings_init' );

function mgmit_bridge_admin_menu() {
    add_options_page(
        'SWPM → HubSpot Bridge',
        'SWPM Bridge',
        'manage_options',
        'mgmit-bridge-settings',
        'mgmit_bridge_settings_page'
    );
}

function mgmit_bridge_admin_settings_init() {
    register_setting(
        'mgmit_bridge_settings_group',
        MGMIT_Webhook_Sender::SECRET_KEY,
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    register_setting(
        'mgmit_bridge_settings_group',
        'mgmit_bridge_free_level_id',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 2,
        )
    );

    add_settings_section(
        'mgmit_bridge_main_section',
        'Configuración del webhook',
        '__return_false',
        'mgmit-bridge-settings'
    );

    add_settings_field(
        'mgmit_bridge_secret_field',
        'Webhook Secret (WOO_WEBHOOK_SECRET_DE)',
        'mgmit_bridge_secret_field_render',
        'mgmit-bridge-settings',
        'mgmit_bridge_main_section'
    );

    add_settings_field(
        'mgmit_bridge_free_level_field',
        'ID nivel membresía gratuita',
        'mgmit_bridge_free_level_field_render',
        'mgmit-bridge-settings',
        'mgmit_bridge_main_section'
    );
}

function mgmit_bridge_free_level_field_render() {
    $value = (int) get_option( 'mgmit_bridge_free_level_id', 2 );
    printf(
        '<input type="number" name="mgmit_bridge_free_level_id" value="%d" class="small-text" min="1" />'
        . '<p class="description">ID del nivel SWPM al que se degrada el usuario cuando se cancela su suscripción de pago. Nivel actual: <strong>%d</strong>.</p>',
        $value,
        $value
    );
}

function mgmit_bridge_secret_field_render() {
    $value = get_option( MGMIT_Webhook_Sender::SECRET_KEY, '' );
    printf(
        '<input type="password" name="%s" value="%s" class="regular-text" autocomplete="new-password" />'
        . '<p class="description">Secret que debe coincidir con <code>WOO_WEBHOOK_SECRET_DE</code> en el servidor webhook.</p>',
        esc_attr( MGMIT_Webhook_Sender::SECRET_KEY ),
        esc_attr( $value )
    );
}

function mgmit_bridge_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>SWPM → HubSpot Bridge</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'mgmit_bridge_settings_group' );
            do_settings_sections( 'mgmit-bridge-settings' );
            submit_button( 'Guardar' );
            ?>
        </form>
        <hr>
        <h2>Log de errores</h2>
        <?php
        $log_path = WP_CONTENT_DIR . '/uploads/mgmit-bridge-log.txt';
        if ( file_exists( $log_path ) ) {
            $lines = array_slice( file( $log_path ), -50 );
            echo '<pre style="background:#f1f1f1;padding:10px;max-height:400px;overflow:auto;">'
                . esc_html( implode( '', $lines ) )
                . '</pre>';
            printf(
                '<form method="post"><input type="hidden" name="mgmit_bridge_clear_log" value="1">%s<button type="submit" class="button">Vaciar log</button></form>',
                wp_nonce_field( 'mgmit_bridge_clear_log', '_wpnonce', true, false )
            );
        } else {
            echo '<p>Sin entradas de log.</p>';
        }

        if (
            isset( $_POST['mgmit_bridge_clear_log'] ) &&
            check_admin_referer( 'mgmit_bridge_clear_log' )
        ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents( $log_path, '' );
            echo '<p class="updated">Log vaciado.</p>';
        }
        ?>
    </div>
    <?php
}
