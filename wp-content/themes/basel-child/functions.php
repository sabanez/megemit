<?php

if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

/*
// Automatische übernahme des Forum-Namen aus dem SWPM-Formular in den Display-Namen der User-Datenbank
add_action('swpm_front_end_profile_edited_fb', 'cncl_after_edit_callback');
function cncl_after_edit_callback ($member_info)
{
	write_log('************************************************************************+');
	write_log($member_info);
	$_url = "https://solve.mgm.concloo.net/editmgm&memberid=".$member_info["member_id"]."&accountstate=".$member_info["account_state"];
	$_buffer = implode('', file($_url));
	global $wpdb;
	$prefix = $wpdb->prefix;
	$fbcf = $prefix . 'swpm_form_builder_fields';
	$cf	= $wpdb-> get_row("SELECT * FROM ".$prefix."swpm_form_builder_fields WHERE field_name LIKE 'Forum-Name'");
	$fid = $cf->field_id;
	$cfs = $wpdb->get_results("SELECT * FROM ".$prefix."swpm_form_builder_custom WHERE field_id = ".$cf->field_id);
	foreach ($cfs as $post) {
		$member = $wpdb->get_row("SELECT * FROM ".$prefix."swpm_members_tbl WHERE member_id = ".$post->user_id);
		$user = $wpdb->get_row("SELECT * FROM ".$prefix."users WHERE user_login LIKE '".$member->user_name."'");
		$user_data = wp_update_user( array( 'ID' => $user->ID, 'display_name' => $post->value ) );
	};
}
*/

// Hook robusto para marcar al usuario después del registro (ahora con Cookies)
add_action('swpm_front_end_registration_complete_user_data', 'mgmit_after_registration_hs_logic');
function mgmit_after_registration_hs_logic($member_info) {
    // Establecemos una cookie de 24 horas para identificar a este navegador como "en proceso de alta"
    // Usamos '/' para que sea válida en toda la web
    setcookie('mgmit_hs_pending', '1', time() + 86400, '/');
    
    // También guardamos en Meta por si el usuario se loguea en otro navegador o sesión más tarde
    $username = $member_info['user_name'];
    $wp_user = get_user_by('login', $username);
    if ($wp_user && $member_info['membership_level'] == 2) {
        update_user_meta($wp_user->ID, 'mgmit_hs_details_pending', '1');
    }
}

// Lógica de redirección forzosa basada en Cookies y Meta
add_action('template_redirect', 'mgmit_enforce_hs_form_completion', 1);
function mgmit_enforce_hs_form_completion() {
    // Si estamos en el logout, admin o procesos internos, no bloqueamos
    if (strstr($_SERVER['REQUEST_URI'], 'action=logout') || is_admin()) return;

    $is_pending = false;

    // Caso A: El usuario está logueado (comprobamos su Meta)
    if (is_user_logged_in()) {
        if (get_user_meta(get_current_user_id(), 'mgmit_hs_details_pending', true) === '1') {
            $is_pending = true;
        }
    } 
    // Caso B: El usuario acaba de registrarse pero NO tiene sesión (comprobamos la Cookie)
    else if (isset($_COOKIE['mgmit_hs_pending']) && $_COOKIE['mgmit_hs_pending'] === '1') {
        $is_pending = true;
    }

    if ($is_pending) {
        $forced_page_id = 21568; // ID de 'registrierungsdetails'
        if (!is_page($forced_page_id) && !is_page('registrierungsdetails')) {
            wp_redirect(get_permalink($forced_page_id) . '?enforced=1');
            exit;
        }
    }
}

// Limpiar bloqueo (Cookie + Meta)
add_action('init', 'mgmit_clear_hs_pending_status');
function mgmit_clear_hs_pending_status() {
    // Triple Seguro: Detectar el envío del formulario de registro antes del proceso
    if (isset($_POST['swpm_registration_submit']) && isset($_POST['level_id']) && $_POST['level_id'] == 2) {
        setcookie('mgmit_hs_pending', '1', time() + 86400, '/');
    }

    if (isset($_GET['hs_finish'])) {
        // Borramos cookie
        setcookie('mgmit_hs_pending', '', time() - 3600, '/');
        
        // Si está logueado, limpiamos su meta
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'mgmit_hs_details_pending', '0');
        }
        
        wp_redirect(home_url('/fachkreisbereich/'));
        exit;
    }
}

    }
}


// Change the default "from name"
function wp_email_set_from_name( $from_name ) {
    if ( 'WordPress' === $from_name ) {
        return 'MeGeMIT';
    }
 
    return $from_name;
}
add_filter( 'wp_mail_from_name', 'wp_email_set_from_name' );
// Change the default "from email"
function wp_email_set_from_email( $from_email ) {
    if ( false !== strpos( $from_email, 'wordpress@' ) ) {
        return 'info@megemit.org';
    }
 
    return $from_email;
}
add_filter( 'wp_mail_from', 'wp_email_set_from_email' );


function add_query_vars_filter( $vars ){
  $vars[] = "termin";
  return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

// Concloo Service-Plattform Shortcode: [concloo]
function concloo_function(){
        global $current_user;
	wp_get_current_user();
	date_default_timezone_set("Europe/Berlin");
	$timestamp = time();
//	$key = $current_user->user_login.'concloo'.date("dmYH",$timestamp);
	$key = hash('sha256',strtolower($current_user->user_login).'concloo'.date("dmYH",$timestamp));
	$termin = '';
	if ( get_query_var('termin') ) {
		$termin = '&termin='.get_query_var('termin');
	}
	$Ausgabe = '<iframe id="superFrame" src="https://service.mgm.concloo.net/'.$key.'&user='.$current_user->user_login.'&ts='.$timestamp.$termin.'" style="width: 100%; border: none;" scrolling="no" height="100"></iframe>
<script type="text/javascript">
  window.addEventListener(\'message\', function(e) {
//    var eventName = e.data[0];
    switch(e.data[0]) {
      case \'setIframeHeight\':
        document.getElementById(\'superFrame\').height = e.data[1];
        break;
      case \'setcnclIframeHeight\':
        document.getElementById(\'superHint\').height = e.data[1];
        break;
    }
  }, false);
</script>';
                return $Ausgabe;
}
add_shortcode('concloo', 'concloo_function' );

// Concloo Service-Plattform Shortcode: [cncl]
function cncl_function(){
        global $current_user;
	wp_get_current_user();
	date_default_timezone_set("Europe/Berlin");
	$timestamp = time();

	$Ausgabe = '<iframe id="superHint" src="https://service.mgm.concloo.net/hint&user='.$current_user->user_login.'&ts='.$timestamp.'" style="width: 100%; border: none;" scrolling="no" height="201"></iframe>';
                return $Ausgabe;
}
add_shortcode('cncl', 'cncl_function' );

// Concloo Spezialistensuche Shortcode: [cnclspecial]
function cncl_special_function(){
	date_default_timezone_set("Europe/Berlin");
	$timestamp = time();
	$Ausgabe = '<iframe id="superFrame" src="https://service.mgmsolve.concloo.net/special&&ts='.$timestamp.'" style="width: 100%; border: none;" scrolling="no" height="201"></iframe>';
                return $Ausgabe;
}
add_shortcode('cnclspecial', 'cncl_special_function' );


function add_custom_role( $bbp_roles ) {
$bbp_roles['my_custom_role1'] = array(
'name' => 'Profesional',
'capabilities' => bbp_get_caps_for_role( bbp_get_participant_role() ) // the same capabilities as participants
);
$bbp_roles['my_custom_role2'] = array(
'name' => 'Socio',
'capabilities' => bbp_get_caps_for_role( bbp_get_participant_role() ) // the same capabilities as participants
);

/*
$bbp_roles['my_custom_role3'] = array(
'name' => 'name 3',
'capabilities' => bbp_get_caps_for_role( bbp_get_keymaster_role() ) // the same capabilities as keymaster
); */
return $bbp_roles;
}
add_filter( 'bbp_get_dynamic_roles', 'add_custom_role', 1 );

function basel_child_enqueue_styles() {
	$version = basel_get_theme_info( 'Version' );
	if( basel_get_opt( 'minified_css' ) ) {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.min.css', array('bootstrap'), $version );
	} else {
		wp_enqueue_style( 'basel-style', get_template_directory_uri() . '/style.css', array('bootstrap'), $version );
	}
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('bootstrap'), $version );
    // WP Bakery lädt auch Font Awesome, braucht sie nicht zweimal
    wp_dequeue_style( 'font-awesome' );
    wp_deregister_style( 'font-awesome' );
}
add_action( 'wp_enqueue_scripts', 'basel_child_enqueue_styles', 100000 );

function custom_thumb_size() {
    $size = array( 100, 100 );
    return $size;
}
add_filter( 'wpsl_thumb_size', 'custom_thumb_size' );

// Enqueue Google Fonts, instead of adding manually to header.php
function bg_load_google_fonts() {
	wp_enqueue_style( 'google-fonts', '//fonts.googleapis.com/css?family=Mr+Dafoe', array() );
}
add_action( 'wp_enqueue_scripts', 'bg_load_google_fonts' );

/**
 * ------------------------------------------------------------------------------------------------
 * Personalización Listado de terapeutas
 * ------------------------------------------------------------------------------------------------
 */
add_filter( 'wpsl_meta_box_fields', 'custom_meta_box_fields' );

function custom_meta_box_fields( $meta_fields ) {
    
    $meta_fields[__( 'Others', 'wpsl' )] = array(
        'specialty' => array(
            'label' => __( 'Specialty', 'wpsl' )
        )
    );

    return $meta_fields;
}

add_filter( 'wpsl_frontend_meta_fields', 'custom_frontend_meta_fields' );

function custom_frontend_meta_fields( $store_fields ) {

    $store_fields['wpsl_specialty'] = array( 
        'name' => 'specialty',
        'type' => 'text'
    );

    return $store_fields;
}
add_filter( 'wpsl_listing_template', 'custom_listing_template' );

function custom_listing_template() {

    global $wpsl_settings;

    $listing_template = '<li data-store-id="<%= id %>">' . "\r\n";
    $listing_template .= "\t\t" . '<div>' . "\r\n";
    $listing_template .= "\t\t\t" . '<p><%= thumb %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . wpsl_store_header_template( 'listing' ) . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span class="wpsl-street"><%= address %></span>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span class="wpsl-street"><%= address2 %></span>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span>' . wpsl_address_format_placeholders() . '</span>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span class="wpsl-country"><%= country %></span>' . "\r\n";
    $listing_template .= "\t\t\t" . '</p>' . "\r\n";
    
    // Check if the 'specialty' contains data before including it.
    $listing_template .= "\t\t\t" . '<% if ( specialty ) { %>' . "\r\n";
    $listing_template .= "\t\t\t" . '<p style="color: #f39910;"><%= specialty %></p>' . "\r\n";
    $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
    
    if ( $wpsl_settings['show_contact_details'] ) {
        $listing_template .= "\t\t\t" . '<p class="wpsl-contact-details">' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'phone_label', __( 'Phone', 'wpsl' ) ) ) . '</strong>: <%= formatPhoneNumber( phone ) %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( fax ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'fax_label', __( 'Fax', 'wpsl' ) ) ) . '</strong>: <%= fax %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( email ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'email_label', __( 'Email', 'wpsl' ) ) ) . '</strong>: <%= email %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '</p>' . "\r\n";
    }

    $listing_template .= "\t\t\t" . wpsl_more_info_template() . "\r\n"; // Check if we need to show the 'More Info' link and info
    $listing_template .= "\t\t" . '</div>' . "\r\n";

    // Check if we need to show the distance.
    if ( !$wpsl_settings['hide_distance'] ) {
        $listing_template .= "\t\t" . '<%= distance %> ' . esc_html( $wpsl_settings['distance_unit'] ) . '' . "\r\n";
    }
 
    $listing_template .= "\t\t" . '<%= createDirectionUrl() %>' . "\r\n"; 
    $listing_template .= "\t" . '</li>' . "\r\n"; 

    return $listing_template;
}


// 1.4.2020 | Basel Theme scheint jetzt die FORM Tag im theme meta blocks zu entfernen,
// also läuft die Suchfunktion im Header direkt im functions.php
if( ! function_exists( 'basel_header_block_widget_area' ) ) {
	function basel_header_block_widget_area() { 
		$cart_count = WC()->cart->get_cart_contents_count();
    ?>
		<div class="widgetarea-head">
			<div class="cncl_cart-contents">
				<a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'Warenkorb ansehen' ); ?>">
                    <i class="fa fa-shopping-cart"></i>
                    <?php if ( $cart_count > 0 ) : ?>
                    <span class="cart-contents-count"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
			</div>
            <div style="float: right;">
                <div class="search-button basel-search-full-screen mobile-search-icon">
                    <a href="#"><i class="fa fa-search"></i></a>
                    <div class="basel-search-wrapper">
                        <div class="basel-search-inner">
                            <span class="basel-close-search">Schließen</span>
                            <form role="search" method="get" id="searchform" class="searchform " action="https://www.megemit.org/">
                                <div>
                                    <label class="screen-reader-text">Suche:</label>
                                    <input type="text" class="search-field" placeholder="Suche…" value="" name="s" id="s">
                                    <input type="hidden" name="post_type" id="post_type" value="post,page">
                                    <button type="submit" id="searchsubmit" value="Buscar">Suche</button>
                                </div>
                            </form>
                            <div class="search-results-wrapper">
                                <div class="basel-search-results"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			
			<?php
				global $current_user;
//				if ($current_user->user_level == 0)
				if ($current_user->ID > 0)
				{
//					echo $current_user->ID;
					
			echo '<div style="float: right;">
                <h4 style="padding: 6px 0px; margin: 1px 10px 1px 0px; width: auto; text-align: center;"><a class="aep" style="font-weight: 400; color: #ffffff;" href="http://megemit.loc/profil-bearbeiten/"><i class="fa fa-user-md"></i></a></h4>
            </div>';
			
				}
					
			?>
            <div style="float: right;">
                <h4 style="padding: 6px 0px; margin: 1px 10px 1px 10px; width: 180px; text-align: center;"><a class="aep" style="font-weight: 400; color: #ffffff;" href="http://megemit.loc/fachkreisbereich/"><i class="fa fa-user-md"></i> Fachkreisbereich</a></h4>
            </div>
            <div style="float: right" class="idiomas">
                <a target="_blank" href="https://www.micro-immunotherapy.com/">EN</a> <a target="_blank" href="https://www.aemi.es/">ES</a> <a target="_blank" href="https://www.microimmuno.fr/">FR</a>
            </div>
		</div>
	<?php }
}

// Basel Theme Cookie Banner entfernen
function basel_cookies_popup() {
    // Nichts
}
add_action( 'basel_after_footer', 'basel_cookies_popup', 300 );

// Die normale Mitgliedsseite weiterleiten, falls Besucher eingeloggt ist
function add_login_check() {
    if ( is_user_logged_in() && is_page( 2534 ) ) {
        wp_redirect( get_permalink( 5665 ) );
        exit;
    }
}
add_action('wp', 'add_login_check');
#WP Store Locator Settings
add_filter( 'wpsl_geocode_components', 'custom_geocode_components' );

function custom_geocode_components( $geocode_components ) {

    if ( is_page( 'store-locations-austria' ) ) {
        $geocode_components['country'] = 'at';
    } else if ( is_page( 'store-locations-germany') ) {
        $geocode_components['country'] = 'de';
    }

    return $geocode_components;
}	

#Arreglar desactivación de All-in-One Event Calendar Extended Views
if ( !is_plugin_active( 'all-in-one-event-calendar-extended-views/all-in-one-event-calendar-extended-views.php' ) ) {
	activate_plugin('all-in-one-event-calendar-extended-views/all-in-one-event-calendar-extended-views.php');
}	

// Text vor und nach den Produkten der Online-Akademie
function cncl_content_above() {
    if (is_product_category('online')) { 
        echo '<div class="custom-content-above">';
		echo '<a href="/online-akademie/" class="cncl_wiederholer">Mehr über die Online-Akademie</a><br /><br />';
//        echo '<a href="/produktkategorie/wiederholer/" class="cncl_wiederholer">Ich möchte einen Kurs wiederholen*</a><br /><br />';
        echo '</div>';
    }
}
add_action('woocommerce_before_main_content', 'cncl_content_above', 5);

function cncl_content_below() {
    if (is_product_category('online')) { 
        echo '<div class="custom-content-below">';
		echo '<a href="/produktkategorie/wiederholer/" class="cncl_wiederholer">Ich möchte einen Kurs wiederholen*</a><br /><br />';
        echo '<p style="font-size:0.9em;">*Wenn Sie bereits einen Kurs online oder präsenziell abgelegt haben, können Sie diesen gegen einen kleinen Aufpreis in der interaktiven Online-Akademie wiederholen. Sollte Ihnen noch ein Kursteil fehlen, müssten Sie diesen gesondert zum regulären Tarif kaufen. Wenn Sie das A1 oder A2 online oder präsenziell abgelegt haben, können Sie den reduzierten Preis des Aufbaukurs der Online-Akademie in Anspruch nehmen. Wenn Sie weitere Fragen haben, wenden Sie sich bitte an: <a href="mailto:akademie@megemit.org">akademie@megemit.org</a></p>';
        echo '</div>';
    }
}
add_action('woocommerce_after_main_content', 'cncl_content_below', 5);

// Hinzufügen eines benutzerdefinierten Feldes im Produkt-Bearbeitungsbildschirm
add_action('woocommerce_product_options_general_product_data', 'add_custom_date_field');
function add_custom_date_field() {
    woocommerce_wp_text_input(
        array(
            'id' => 'veranstaltungsdatum',
            'label' => __('Veranstaltungsdatum', 'woocommerce'),
            'type' => 'date',
            'desc_tip' => 'true',
            'description' => __('Datum der Veranstaltung eingeben.', 'woocommerce')
        )
    );
	woocommerce_wp_text_input(
        array(
            'id' => 'veranstaltungsort',
            'label' => __('Veranstaltungsort', 'woocommerce'),
            'placeholder' => 'Veranstaltungsort',
            'desc_tip' => 'true',
            'description' => __('Hier Veranstaltungsort eingeben.', 'woocommerce'),
            'type' => 'text'
        )
    );
}

// Speichern des benutzerdefinierten Feldes
add_action('woocommerce_process_product_meta', 'save_custom_date_field');
function save_custom_date_field($post_id) {
    $date = $_POST['veranstaltungsdatum'];
    if (!empty($date)) {
        update_post_meta($post_id, 'veranstaltungsdatum', esc_attr($date));
    }
	$veranstaltungsort = isset($_POST['veranstaltungsort']) ? sanitize_text_field($_POST['veranstaltungsort']) : '';

    update_post_meta($post_id, 'veranstaltungsort', $veranstaltungsort);
}


// Hinzufügen des Datums zur Produktliste
//add_action('woocommerce_shop_loop_item_title', 'show_event_date_in_product_list', 15);
add_action('woocommerce_before_shop_loop_item_title', 'show_event_date_in_product_list', 15);
function show_event_date_in_product_list() {
    global $product;
    $event_date = get_post_meta($product->get_id(), 'veranstaltungsdatum', true);
	$event_ort = get_post_meta($product->get_id(), 'veranstaltungsort', true);
    if (!empty($event_date)) {
		$dateTime = new DateTime($event_date);
		$formatter = new IntlDateFormatter(
	    'de_DE',
	    IntlDateFormatter::LONG,
	    IntlDateFormatter::NONE
		);

		$formattedDate = $formatter->format($dateTime);
		echo '<p class="event-date">' . esc_html($event_ort) . ': ' . esc_html($formattedDate) . '</p>';
    }
}

function cncl_display_event_date() {
    global $product;
    if (has_term('cncl-praesenziell', 'product_cat', $product->get_id())) {
        $event_date = get_post_meta($product->get_id(), 'veranstaltungsdatum', true);
        $dateTime = new DateTime($event_date);
        if ($event_date) {
			$dateTime = new DateTime($event_date);
			$formatter = new IntlDateFormatter('de_DE', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
			$formattedDate = $formatter->format($dateTime);
            echo '<p class="event-date">Veranstaltungsdatum: ' . esc_html($formattedDate) . '</p>';
        }
    }
}

// Haken, um das Datum nach dem Produkt-Titel anzuzeigen
add_action('woocommerce_single_product_summary', 'cncl_display_event_date', 6);

// Hinzufügen einer benutzerdefinierten Sortieroption für das Veranstaltungsdatum
add_filter('woocommerce_get_catalog_ordering_args', 'custom_woocommerce_get_catalog_ordering_args');
function custom_woocommerce_get_catalog_ordering_args($args) {
    if (isset($_GET['orderby']) && 'event_date' === $_GET['orderby']) {
        $args['orderby'] = 'meta_value';
        $args['order'] = 'asc';
        $args['meta_key'] = 'veranstaltungsdatum';
    }
    return $args;
}

// Hinzufügen der Sortieroption zur Dropdown-Liste der Sortierung
add_filter('woocommerce_default_catalog_orderby_options', 'custom_woocommerce_catalog_orderby');
add_filter('woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby');
function custom_woocommerce_catalog_orderby($sortby) {
    $sortby['event_date'] = __('nach Veranstaltungsdatum', 'woocommerce');
    return $sortby;
}

// Füge eine benutzerdefinierte CSS-Klasse zu Produkten einer bestimmten Kategorie hinzu
/* function add_custom_css_class_to_product_group($classes, $class, $post_id) {
    if ('product' == get_post_type($post_id)) {
        $terms = get_the_terms($post_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Überprüfe den Slug oder die ID der Kategorie und füge die Klasse hinzu
                if ($term->slug == 'praesenziell' || $term->term_id == 932) {
                    $classes[] = 'cncl_event';
                    break;
                }
            }
        }
    }
    return $classes;
}
add_filter('post_class', 'add_custom_css_class_to_product_group', 10, 3);
*/

/*function custom_add_to_wishlist_text($text) {
    return str_replace('Add to wishlist', 'Zu meiner Wunschliste hinzufügen', $text);
}
add_filter('the_content', 'custom_add_to_wishlist_text');*/


// Entfernt den Standardpreis-Hook
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);

// Fügt den Preis unter der Kurzbeschreibung ein
add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 25);

function my_custom_cart_icon() {
    ?>
    <a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e( 'View your shopping cart' ); ?>">
        <i class="fa fa-shopping-cart"></i>
        <span class="cart-contents-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    </a>
    <?php
}

function add_cart_icon_to_header() {
    add_action( 'wp_head', 'my_custom_cart_icon' );
}
add_action( 'init', 'add_cart_icon_to_header' );

add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

// Zustimmung zum Newsletter an der Kasse hinzufügen
add_action('woocommerce_review_order_before_submit', 'cncl_add_newsletter_checkbox', 10);
function cncl_add_newsletter_checkbox() {
    woocommerce_form_field('newsletter_optin', array(
        'type' => 'checkbox',
        'class' => array('form-row newsletter'),
        'label_class' => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class' => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required' => false,
        'label' => 'Ich möchte den Newsletter abonnieren',
    ));
}

// Zustimmung zum Newsletter zur Bestellung hinzufügen
add_action('woocommerce_checkout_update_order_meta', 'cncl_add_newsletter_checkbox_order_meta');
function cncl_add_newsletter_checkbox_order_meta( $order_id ) {
    if ( isset( $_POST['newsletter_optin'] ) ) {
        update_post_meta( $order_id, 'newsletter_optin', esc_attr( $_POST['newsletter_optin'] ) );
    }
}

// Anzeige der Zustimmung zum Newsletter im Admin-Bereich der Bestellung
add_action('woocommerce_admin_order_data_after_billing_address', 'cncl_display_newsletter_optin_in_admin_order_meta', 10, 1);
function cncl_display_newsletter_optin_in_admin_order_meta($order){
    $newsletter_optin = get_post_meta($order->get_id(), 'newsletter_optin', true);
    if ($newsletter_optin) {
        echo '<p><strong>' . __('Newsletter Opt-in') . ':</strong> ' . __('Yes') . '</p>';
    }
}

// Zustimmung zur Datenschutzerklärung an der Kasse hinzufügen
add_action('woocommerce_review_order_before_submit', 'cncl_add_privacy_policy_checkbox', 9);
function cncl_add_privacy_policy_checkbox() {
    woocommerce_form_field('privacy_policy', array(
        'type' => 'checkbox',
        'class' => array('form-row privacy'),
        'label_class' => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class' => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required' => true,
        'label' => 'Ich habe die <strong><a href="' . get_privacy_policy_url() . '" target="_blank">Datenschutzerklärung</a></strong> gelesen und stimme zu',
    ));
}

// Validierung der Zustimmung zur Datenschutzerklärung
add_action('woocommerce_checkout_process', 'cncl_privacy_policy_checkbox_validation');
function cncl_privacy_policy_checkbox_validation() {
    if ( ! (int) isset( $_POST['privacy_policy'] ) ) {
        wc_add_notice(__('Bitte stimmen Sie der Datenschutzerklärung zu, um fortzufahren.'), 'error');
    }
}

// Zustimmung zur Datenschutzerklärung zur Bestellung hinzufügen
add_action('woocommerce_checkout_update_order_meta', 'cncl_add_privacy_policy_checkbox_order_meta');
function cncl_add_privacy_policy_checkbox_order_meta( $order_id ) {
    if ( isset( $_POST['privacy_policy'] ) ) {
        update_post_meta( $order_id, 'privacy_policy', esc_attr( $_POST['privacy_policy'] ) );
    }
}

// Anrede und Titel zum Checkout
add_filter( 'woocommerce_checkout_fields' , 'cncl_add_custom_checkout_field' );
function cncl_add_custom_checkout_field( $fields ) {
    $fields['billing']['billing_anrede'] = array(
        'type'        => 'select', 
        'label'       => __('Anrede', 'woocommerce'),
        'placeholder' => _x('Bitte auswählen', 'placeholder', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'options'     => array(
            'frau'       => __('Frau', 'woocommerce'),
            'herr'      => __('Herr', 'woocommerce'),
        ),
    );
	$fields['billing']['billing_titel'] = array(
        'type'        => 'select', 
        'label'       => __('Titel', 'woocommerce'),
        'placeholder' => _x('Bitte auswählen', 'placeholder', 'woocommerce'),
        'required'    => false,
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'options'     => array(
            ''         => __('-', 'woocommerce'),
            'prof'       => __('Prof.', 'woocommerce'),
            'dr'      => __('Dr.', 'woocommerce'),
            'mag'      => __('Mag.', 'woocommerce'),
            'diplmed'      => __('Dipl. Med.', 'woocommerce'),
        ),
    );
    return $fields;
}

add_filter('woocommerce_admin_billing_fields', 'cncl_custom_admin_billing_fields');
function cncl_custom_admin_billing_fields($fields) {
    $fields['billing_anrede'] = array(
        'label' => __('Anrede', 'woocommerce'),
        'type'  => 'select',
        'options' => array(
            'frau'       => __('Frau', 'woocommerce'),
            'herr'      => __('Herr', 'woocommerce'),
        ),
        'show'  => true,
    );

    $fields['billing_titel'] = array(
        'label' => __('Titel', 'woocommerce'),
        'type'  => 'select',
        'options' => array(
            ''         => __('-', 'woocommerce'),
            'prof'       => __('Prof.', 'woocommerce'),
            'dr'      => __('Dr.', 'woocommerce'),
            'mag'      => __('Mag.', 'woocommerce'),
            'diplmed'      => __('Dipl. Med.', 'woocommerce'),
        ),
        'show'  => true,
    );

    return $fields;
}


// Speichern des benutzerdefinierten Feldes
add_action('woocommerce_checkout_update_order_meta', 'cncl_save_custom_checkout_field');

function cncl_save_custom_checkout_field( $order_id ) {
    if ( ! empty( $_POST['billing_anrede'] ) ) {
        update_post_meta( $order_id, '_anrede', sanitize_text_field( $_POST['billing_anrede'] ) );
    }
	if ( ! empty( $_POST['billing_titel'] ) ) {
        update_post_meta( $order_id, '_titel', sanitize_text_field( $_POST['billing_titel'] ) );
    }
}

/* add_action( 'woocommerce_admin_order_data_after_billing_address', 'cncl_display_custom_field_in_admin_order_meta', 13, 1 );
function cncl_display_custom_field_in_admin_order_meta($order){
    echo '<p><strong>'.__('Anrede').':</strong> ' . get_post_meta( $order->get_id(), '_billing_anrede', true ) . '</p>';
	echo '<p><strong>'.__('Titel').':</strong> ' . get_post_meta( $order->get_id(), '_billing_titel', true ) . '</p>';
} */

// Reihenfolge der Felder ändern.
add_filter('woocommerce_checkout_fields', 'cncl_move_anrede_to_top_checkout');

function cncl_move_anrede_to_top_checkout($fields) {
    // Speichere das "Anrede"-Feld und entferne es aus der aktuellen Position
    $anrede_field = $fields['billing']['billing_anrede'];
	$titel_field = $fields['billing']['billing_titel'];
    unset($fields['billing']['billing_anrede']);
	unset($fields['billing']['billing_titel']);

    // Füge das "Anrede"-Feld an der ersten Position hinzu
    $fields['billing'] = array_merge(['billing_titel' => $titel_field], $fields['billing']);
	$fields['billing'] = array_merge(['billing_anrede' => $anrede_field], $fields['billing']);

    return $fields;
}

// "Ähnliche Produkte" entfernen
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

// Sortierung entfernen
add_action( 'woocommerce_before_shop_loop', 'cncl_remove_sorting_dropdown' );
function cncl_remove_sorting_dropdown() {
    if ( is_product_category() ) {
        remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
    }
}

function cncl_wc_ajax_variation_threshold( $qty, $product ) {
return 100;
}
add_filter( 'woocommerce_ajax_variation_threshold', 'cncl_wc_ajax_variation_threshold', 10, 2 );

add_filter('woocommerce_dropdown_variation_attribute_options_args', 'cncl_variation_dropdown_per_attribute');
function cncl_variation_dropdown_per_attribute($args) {
    if ($args['attribute'] === 'Mein Grundlagenseminar') { 
        $args['show_option_none'] = 'Wählen Sie einen Grundkurs';
    } elseif ($args['attribute'] === 'Grundkurs') { 
        $args['show_option_none'] = 'Wählen Sie einen Grundkurs';
    } elseif ($args['attribute'] === 'Aufbaukurs Teil 1') { 
        $args['show_option_none'] = 'Wählen Sie Teil 1 Aufbaukurs';
    } elseif ($args['attribute'] === 'Aufbaukurs Teil 2') { 
        $args['show_option_none'] = 'Wählen Sie Teil 2 Aufbaukurs';
    }
    return $args;
}


add_filter( 'wpsl_meta_box_fields', 'cncl_custom_meta_box_fields' );
function cncl_custom_meta_box_fields( $meta_fields ) {
    $meta_fields[__( 'Zertifikat…', 'wpsl' )] = array(
        'certcheckbox' => array(
            'label' => __( 'Zertifikat', 'wpsl' ),
		'type'  => 'checkbox'
        ),
		'onlinecheckbox' => array(
            'label' => __( 'Online-Therapie', 'wpsl' ),
		'type'  => 'checkbox'
        ),
		'sprachen' => array(
            'label' => __( 'Angebotene Sprachen', 'wpsl' )
        )
    );

    return $meta_fields;
}

add_filter( 'wpsl_frontend_meta_fields', 'cncl_custom_frontend_meta_fields' );
function cncl_custom_frontend_meta_fields( $store_fields ) {
    $store_fields['wpsl_certcheckbox'] = array( 
        'name' => 'certcheckbox'
    );  
	$store_fields['wpsl_onlinecheckbox'] = array( 
        'name' => 'onlinecheckbox'
    );
	$store_fields['wpsl_sprachen'] = array( 
        'name' => 'sprachen' 
    );
    return $store_fields;
}

add_filter( 'wpsl_listing_template', 'cncl_custom_listing_template' );

function cncl_custom_listing_template() {

    global $wpsl, $wpsl_settings;
    
    $listing_template = '<li data-store-id="<%= id %>">' . "\r\n";
    $listing_template .= "\t\t" . '<div class="wpsl-store-location ' . "\r\n";
	$listing_template .= "\t\t" . '<% if ( certcheckbox ) { %>' . 'cnclcert' . '<% } %>' . "\r\n";
	$listing_template .= '">' . "\r\n";
    $listing_template .= "\t\t\t" . '<p><%= thumb %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . wpsl_store_header_template( 'listing' ) . "\r\n"; // Check which header format we use
    $listing_template .= "\t\t\t\t" . '<span class="wpsl-street"><%= address %></span>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span class="wpsl-street"><%= address2 %></span>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
    $listing_template .= "\t\t\t\t" . '<span>' . wpsl_address_format_placeholders() . '</span>' . "\r\n"; // Use the correct address format

    if ( !$wpsl_settings['hide_country'] ) {
        $listing_template .= "\t\t\t\t" . '<span class="wpsl-country"><%= country %></span>' . "\r\n";
    }

    $listing_template .= "\t\t\t" . '</p>' . "\r\n";
    
    // Show the phone, fax or email data if they exist.
    if ( $wpsl_settings['show_contact_details'] ) {
        $listing_template .= "\t\t\t" . '<p class="wpsl-contact-details">' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'phone_label', __( 'Phone', 'wpsl' ) ) ) . '</strong>: <%= formatPhoneNumber( phone ) %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( fax ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'fax_label', __( 'Fax', 'wpsl' ) ) ) . '</strong>: <%= fax %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% if ( email ) { %>' . "\r\n";
        $listing_template .= "\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'email_label', __( 'Email', 'wpsl' ) ) ) . '</strong>: <%= email %></span>' . "\r\n";
        $listing_template .= "\t\t\t" . '<% } %>' . "\r\n";
        $listing_template .= "\t\t\t" . '</p>' . "\r\n";
    }

    $listing_template .= "\t\t\t" . wpsl_more_info_template() . "\r\n"; // Check if we need to show the 'More Info' link and info
    $listing_template .= "\t\t" . '</div>' . "\r\n";
    $listing_template .= "\t\t" . '<div class="wpsl-direction-wrap">' . "\r\n";

    if ( !$wpsl_settings['hide_distance'] ) {
        $listing_template .= "\t\t\t" . '<%= distance %> ' . esc_html( $wpsl_settings['distance_unit'] ) . '' . "\r\n";
    }

    $listing_template .= "\t\t\t" . '<%= createDirectionUrl() %>' . "\r\n"; 
    $listing_template .= "\t\t" . '</div>' . "\r\n";
    $listing_template .= "\t" . '</li>';

    return $listing_template;
}

add_filter( 'wpsl_more_info_template', 'custom_more_info_template' );

function custom_more_info_template() {

    global $wpsl_settings, $wpsl;

    $more_info_url = '#';

    if ( $wpsl_settings['template_id'] == 'default' && $wpsl_settings['more_info_location'] == 'info window' ) {
        $more_info_url = '#wpsl-search-wrap';
    }

    if ( $wpsl_settings['more_info_location'] == 'store listings' ) {
        $more_info_template = '<% if ( !_.isEmpty( phone ) || !_.isEmpty( fax ) || !_.isEmpty( email ) ) { %>' . "\r\n";
        $more_info_template .= "\t\t\t" . '<p><a class="wpsl-store-details wpsl-store-listing" href="#wpsl-id-<%= id %>">' . esc_html( $wpsl->i18n->get_translation( 'more_label', __( 'More info', 'wpsl' ) ) ) . '</a></p>' . "\r\n";
        $more_info_template .= "\t\t\t" . '<div id="wpsl-id-<%= id %>" class="wpsl-more-info-listings">' . "\r\n";
        $more_info_template .= "\t\t\t\t" . '<% if ( description ) { %>' . "\r\n";
        $more_info_template .= "\t\t\t\t" . '<%= description %>' . "\r\n";
        $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";

        if ( !$wpsl_settings['show_contact_details'] ) {
            $more_info_template .= "\t\t\t\t" . '<p>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% if ( phone ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'phone_label', __( 'Phone', 'wpsl' ) ) ) . '</strong>: <%= formatPhoneNumber( phone ) %></span>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% if ( fax ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'fax_label', __( 'Fax', 'wpsl' ) ) ) . '</strong>: <%= fax %></span>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% if ( email ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'email_label', __( 'Email', 'wpsl' ) ) ) . '</strong>: <a href="mailto:<%= email %>"><%= email %></a></span>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '</p>' . "\r\n";
        }

        if ( !$wpsl_settings['hide_hours'] ) {
            $more_info_template .= "\t\t\t\t" . '<% if ( hours ) { %>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<div class="wpsl-store-hours"><strong>' . esc_html( $wpsl->i18n->get_translation( 'hours_label', __( 'Hours', 'wpsl' ) ) ) . '</strong><%= hours %></div>' . "\r\n";
            $more_info_template .= "\t\t\t\t" . '<% } %>' . "\r\n";
        }
            
        $more_info_template .= '<% if ( sprachen ) { %>' . "\r\n";
        $more_info_template .= '<p><strong>Weitere Sprachen: </strong><%= sprachen %></p>' . "\r\n";
        $more_info_template .= '<% } %>' . "\r\n";
		$more_info_template .= '<% if ( onlinecheckbox ) { %>' . "\r\n";
        $more_info_template .= '<p><strong>Online-Sprechstunde wird angeboten</strong></p>' . "\r\n";
        $more_info_template .= '<% } %>' . "\r\n";
        $more_info_template .= "\t\t\t" . '</div>' . "\r\n";
        $more_info_template .= "\t\t\t" . '<% } %>';

    } else {
        $more_info_template = '<p><a class="wpsl-store-details" href="' . $more_info_url . '">' . esc_html( $wpsl->i18n->get_translation( 'more_label', __( 'More info', 'wpsl' ) ) ) . '</a></p>';
    }
    
    return $more_info_template;
}

add_filter('action_scheduler_queue_runner_interval', function() {
    return 120; // alle 120 Sekunden statt jede Minute
});

add_filter('action_scheduler_queue_runner_concurrent_batches', function() {
    return 1; // nur ein Batch gleichzeitig
});

function swpm_hubspot_mapper_script() {
    $js_path = '/inc/hubspot_map.js';
    
    wp_enqueue_script(
        'swpm-hubspot-mapper',
        get_stylesheet_directory_uri() . $js_path,
        array('jquery'),
        filemtime(get_stylesheet_directory() . $js_path),
        true
    );

    // Pasamos la configuración de PHP a JavaScript de forma segura
    $config = array(
        array(
            'formId' => '#registro-profesional-13, #swpm-registration-form, .swpm-registration-form',
            'hubspotFormName' => 'MeGeMIT_DE_Fachkreisbereich_Registration',
            'mapping' => array(
                'swpm-472' => 'firstname',
                'swpm-474' => 'lastname',
                'swpm-456' => 'email'
            )
        ),
        array(
            'formId' => '#profile-form-level-13-16',
            'hubspotFormName' => 'MeGeMIT_DE_Profile_Update',
            'mapping' => array(
                'swpm-526' => 'firstname',
                'swpm-527' => 'lastname',
                'swpm-531' => 'email'
            )
        )
    );

    wp_localize_script('swpm-hubspot-mapper', 'HS_CONFIG', $config);
}
add_action('wp_enqueue_scripts', 'swpm_hubspot_mapper_script', 20);