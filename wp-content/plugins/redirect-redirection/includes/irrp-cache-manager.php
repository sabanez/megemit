<?php

if (!defined("ABSPATH")) {
    exit();
}

class IRRPCacheManager
{

    const KEY_REDIRECT_RULES = "irrp_get_redirect_rules_";
    const KEY_CHECK_ARE_404S_RULE_EXISTS = "irrp_check_are_404s_rule_exists";
    const KEY_CHECK_ALL_URLS_RULE_EXISTS = "irrp_check_all_urls_rule_exists";

    /**
     * Get cached value
     */
    public static function get($key)
    {
        return get_transient($key);
    }

    /**
     * Set cached value
     */
    public static function set($key, $value, $expiration = HOUR_IN_SECONDS)
    {
        set_transient($key, $value, $expiration);
    }

    /**
     * Delete cached value
     */
    public static function delete($key)
    {
        delete_transient($key);
    }

    /**
     * Purge all plugin caches
     */
    public static function purgeAll()
    {
        // Purge rules for active and inactive status
        self::delete(self::KEY_REDIRECT_RULES . "0");
        self::delete(self::KEY_REDIRECT_RULES . "1");

        self::delete(self::KEY_CHECK_ARE_404S_RULE_EXISTS);
        self::delete(self::KEY_CHECK_ALL_URLS_RULE_EXISTS);
    }
}
