<?php

if (! defined('ABSPATH')) {
    exit;
}

class Cms_Connector_Cache
{
    const TRANSIENT_PREFIX = 'wp_cms_connector_cache_';
    private static $instance = null;
    private $duration = 300;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->duration = (int) get_option('wp_cms_connector_cache_duration', 300);
    }

    public function set_duration($seconds)
    {
        $this->duration = max(0, (int) $seconds);
    }

    public function get_duration()
    {
        return $this->duration;
    }

    private function key($endpoint)
    {
        return self::TRANSIENT_PREFIX . md5($endpoint);
    }

    public function get($endpoint)
    {
        if ($this->duration <= 0) {
            return false;
        }
        return get_transient($this->key($endpoint));
    }

    public function set($endpoint, $data)
    {
        if ($this->duration <= 0) {
            return;
        }
        set_transient($this->key($endpoint), $data, $this->duration);
    }

    public function flush()
    {
        global $wpdb;
        $pattern = $wpdb->esc_like('_transient_' . self::TRANSIENT_PREFIX) . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern));
        $pattern_timeout = $wpdb->esc_like('_transient_timeout_' . self::TRANSIENT_PREFIX) . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern_timeout));
    }
}
