<?php

namespace Sendcloud\Shipping\Services\BusinessLogic;

use Sendcloud\Shipping\Repositories\SC_Config_Repository;
use Sendcloud\Shipping\Utility\Logger;

/**
 * Class Support_Service
 *
 * @package Sendcloud\Shipping\Services\BusinessLogic
 */
class Support_Service
{
    private const SUPPORT_ENDPOINT_PASSWORD_HASH = '$2y$10$Ajni0UGvMm1QXdqy86kXf.jM7GTcceTyjQlKgyG5nKJdxyGS8H7V6';

    /**
     * Return system configuration parameters
     *
     * @param array|null $payload
     *
     * @return array
     */
    public function get(?array $payload): array
    {
        if (!$payload) {
            return ['message' => 'Invalid payload.'];
        }

        if (!$this->validate_support_password($payload)) {
            return ['message' => 'Sendcloud support password not valid.'];
        }

        $config_repository = $this->get_config_repository();

        return [
            'MIN_LOG_LEVEL' => $config_repository->get_min_log_level()
        ];
    }

    /**
     * Updates system configuration parameters
     *
     * @param array|null $payload
     *
     * @return array
     */
    public function update(?array $payload): array
    {
        if (!$payload) {
            return ['message' => 'Invalid payload.'];
        }

        if (!$this->validate_support_password($payload)) {
            return ['message' => 'Sendcloud support password not valid.'];
        }

        $config_repository = $this->get_config_repository();

        if (array_key_exists('min_log_level', $payload)) {
            $min_log_level = $this->is_min_log_level_correct((int)$payload['min_log_level']) ?
                $payload['min_log_level'] : Logger::$level_to_severity[Logger::WARNING];
            $config_repository->save_min_log_level($min_log_level);
        }

        return ['status' => 'success'];
    }

    /**
     * @return SC_Config_Repository
     */
    private function get_config_repository(): SC_Config_Repository
    {
        return new SC_Config_Repository();
    }

    /**
     * @param array $payload
     *
     * @return bool
     */
    private function validate_support_password(array $payload): bool
    {
        return array_key_exists('support_password', $payload) &&
            password_verify($payload['support_password'], self::SUPPORT_ENDPOINT_PASSWORD_HASH);
    }

    /**
     * @param int $min_log_level
     *
     * @return bool
     */
    private function is_min_log_level_correct(int $min_log_level): bool
    {
        return in_array($min_log_level, Logger::$level_to_severity);
    }
}
