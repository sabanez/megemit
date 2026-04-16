<?php if ( ! defined( 'BASEL_THEME_DIR' ) ) {
	exit( 'No direct script access allowed' );
}

/**
 * Notices helper class
 */

class BASEL_Notices {
	public $notices;
	public $ignore_key = '';

	public function __construct() {
		$this->notices = array();
	}

	public function add_msg( $msg, $type, $global = false ) {
		$this->notices[] = array(
			'msg' => $msg,
			'type' => $type,
			'global' => $global
		);
	}

	public function get_msgs( $globals = false ) {
		if ( $globals ) {
			return array_filter( $this->notices, function( $v ) {
				return $v['global'];
			} );
		}

		return $this->notices;
	}

	public function clear_msgs( $globals = true ) {
		if ( $globals ) {
			$this->notices = array_filter( $this->notices, function( $v ) {
				return ! $v['global'];
			} );
		} else {
			$this->notices = array();
		}
	}

	public function show_msgs( $globals = false ) {
		$msgs = $this->get_msgs( $globals );
		if ( ! empty( $msgs ) ) {
			foreach ( $msgs as $key => $msg ) {
				if ( ! $globals && $msg['global'] ) {
					continue;
				}
				echo '<div class="basel-msg">';
					echo '<p class="basel-' . $msg['type'] . '">' . $msg['msg'] . '</p>';
				echo '</div>';
			}
		}

		$this->clear_msgs( $globals );
	}

	public function add_error( $msg, $global = false ) {
		$this->add_msg( $msg, 'error', $global );
	}

	public function add_warning( $msg, $global = false ) {
		$this->add_msg( $msg, 'warning', $global );
	}

	public function add_success( $msg, $global = false ) {
		$this->add_msg( $msg, 'success', $global );
	}
}
