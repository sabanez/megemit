<?php
namespace Sendcloud\Shipping\Repositories;

class SC_Config_Repository {
	public function save_last_published_time( $last_published_time) {
		$this->save_value('sc_last_published_time', $last_published_time);
	}

	public function get_last_published_time() {
		return get_option('sc_last_published_time');
	}

	public function delete_last_published_time() {
		$this->delete_by_key('sc_last_published_time');
	}

	public function save_integration_id( $integration_id) {
		$this->save_value('sc_integration_id', $integration_id);
	}

	public function delete_integration_id() {
		$this->delete_by_key('sc_integration_id');
	}

	public function get_integration_id() {
		return get_option('sc_integration_id');
	}

    public function save_min_log_level($min_log_level) {
        $this->save_value('sc_min_log_level', $min_log_level);
    }

    public function get_min_log_level() {
        return get_option('sc_min_log_level');
    }

	/**
	 * Save config value
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return bool
	 */
	private function save_value( $key, $value) {
		$existing_option = get_option($key);
		if ($existing_option) {
			return update_option($key, $value);
		}

		return add_option($key, $value);
	}

	/**
	 * Delete config value
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	private function delete_by_key( $key) {
		delete_option($key);
	}
}
