<?php

class SwpmFbSettings {

    function __construct() {
	if ( isset( $_POST[ 'swpm-fb-settings' ] ) ) {
	    $this->save_settings();
	}
    }

    private function save_settings() {
	if ( wp_verify_nonce( $_POST[ '_wpnonce' ], 'swpm-fb-save-settings' ) ) {
	    $opts				 = $_POST[ 'swpm-fb-settings' ];
	    $opts[ 'enable-light-css' ]	 = isset( $opts[ 'enable-light-css' ] ) ? true : false;
	    update_option( 'swpm_fb_settings', $opts );
	    $class				 = 'success';
	    $msg				 = __( 'Settings updated.', 'swpm-form-builder' );
	} else {
	    $class	 = 'error';
	    $msg	 = __( 'Nonce check failed.', 'swpm-form-builder' );
	}
	?>
	<div class="notice notice-<?php echo $class; ?> is-dismissible">
	    <p><?php echo $msg; ?></p>
	</div>
	<?php
    }

    static function checked( $name ) {
	if ( self::get_setting( $name ) !== false ) {
	    echo ' checked';
	}
    }

    static function get_setting( $name, $default = false ) {
	$opt = get_option( 'swpm_fb_settings' );
	if ( isset( $opt[ $name ] ) ) {
	    return $opt[ $name ];
	}
	return $default;
    }

    function render_settings_page() {
	ob_start();
	require_once(SWPM_FORM_BUILDER_PATH . 'views/general_settings.php');
	$tpl = ob_get_clean();
	echo $tpl;
    }

}
