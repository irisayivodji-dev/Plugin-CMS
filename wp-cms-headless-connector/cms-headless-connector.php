<?php

/**
 * Plugin Name: CMS Headless Connector
 * Plugin URI:  https://github.com/yascodev/projet-semestriel
 * Description: Plugin pour communiquer avec le CMS headless du projet semestriel (connexion API, shortcodes, consultation et édition du contenu).
 * Version:     1.0.0
 * Author:      Iris AYIVODJI
 * Author URI:  https://github.com/yascodev/projet-semestriel
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cms-headless-connector
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CMS_CONNECTOR_VERSION', '1.0.0');
define('CMS_CONNECTOR_DIR', plugin_dir_path(__FILE__));
define('CMS_CONNECTOR_URL', plugin_dir_url(__FILE__));

require_once CMS_CONNECTOR_DIR . 'includes/class-cms-connector-api.php';
require_once CMS_CONNECTOR_DIR . 'includes/class-cms-connector-cache.php';
require_once CMS_CONNECTOR_DIR . 'includes/class-cms-connector-admin.php';
require_once CMS_CONNECTOR_DIR . 'includes/class-cms-connector-shortcodes.php';

add_action('plugins_loaded', 'cms_connector_init');
function cms_connector_init()
{
    Cms_Connector_Admin::get_instance();
    Cms_Connector_Shortcodes::get_instance();
}

register_uninstall_hook(__FILE__, 'cms_connector_uninstall');
function cms_connector_uninstall()
{
    delete_option('wp_cms_connector_base_url');
    delete_option('wp_cms_connector_token');
    delete_option('wp_cms_connector_cookies');
    delete_option('wp_cms_connector_cache_duration');
    global $wpdb;
    $p1 = $wpdb->esc_like('_transient_wp_cms_connector_cache_') . '%';
    $p2 = $wpdb->esc_like('_transient_timeout_wp_cms_connector_cache_') . '%';
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $p1));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $p2));
}
