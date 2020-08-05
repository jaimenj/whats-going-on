<?php
/**
 * Plugin Name: What's going on WAF
 * Plugin URI: https://jnjsite.com/whats-going-on-for-wordpress/
 * Description: A tiny WAF, a tool for showing what kind of requests are being made in real time.
 * Version: 1.0
 * Author: Jaime Niñoles
 * Author URI: https://jnjsite.com/.
 */
defined('ABSPATH') or die('No no no');
define('WGO_PATH', plugin_dir_path(__FILE__));

include_once WGO_PATH.'whats-going-on-cronjobs.php';
include_once WGO_PATH.'whats-going-on-backend-controller.php';
include_once WGO_PATH.'whats-going-on-ajax-controller.php';

class WhatsGoingOn
{
    private static $instance;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // Activation and deactivation..
        register_activation_hook(__FILE__, [$this, 'activation']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation']);

        // Main actions..
        add_action('template_redirect', [$this, 'save_404s']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_css_js']);

        WhatsGoingOnCronjobs::get_instance();
        WhatsGoingOnBackendController::get_instance();
        WhatsGoingOnAjaxController::get_instance();
    }

    public function activation()
    {
        global $wpdb;

        // Main table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on ('
            //."id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,"
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'method VARCHAR(8) NOT NULL,'
            .'last_minute INT NOT NULL,'
            .'last_hour INT NOT NULL'
            .');';
        $wpdb->get_results($sql);

        // Blocks table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on_block ('
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'comments VARCHAR(256)'
            .');';
        $wpdb->get_results($sql);

        // 404s table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on_404s ('
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'method VARCHAR(8) NOT NULL'
            .');';
        $wpdb->get_results($sql);

        register_setting('wgo_options_group', 'wgo_limit_requests_per_minute');
        register_setting('wgo_options_group', 'wgo_limit_requests_per_hour');
        register_setting('wgo_options_group', 'wgo_items_per_page');
        register_setting('wgo_options_group', 'wgo_days_to_store');
        register_setting('wgo_options_group', 'wgo_im_behind_proxy');
        register_setting('wgo_options_group', 'wgo_notification_email');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_2sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_3sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_less_than_25_percent');
        register_setting('wgo_options_group', 'wgo_save_payloads');
        register_setting('wgo_options_group', 'wgo_save_only_payloads_matching_regex');

        add_option('wgo_limit_requests_per_minute', '-1');
        add_option('wgo_limit_requests_per_hour', -1);
        add_option('wgo_items_per_page', '10');
        add_option('wgo_days_to_store', '7');
        add_option('wgo_im_behind_proxy', 0);
        add_option('wgo_notification_email', '');
        add_option('wgo_notify_requests_more_than_sd', 0);
        add_option('wgo_notify_requests_more_than_2sd', 0);
        add_option('wgo_notify_requests_more_than_3sd', 0);
        add_option('wgo_notify_requests_less_than_25_percent', 0);
        add_option('wgo_save_payloads', 0);
        add_option('wgo_save_only_payloads_matching_regex', 0);
    }

    public function deactivation()
    {
        global $wpdb;
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_block;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_404s;';
        $wpdb->get_results($sql);

        WhatsGoingOnBackendController::get_instance()->_uninstall_waf();
    }

    public function uninstall()
    {
        delete_option('wgo_limit_requests_per_minute');
        delete_option('wgo_limit_requests_per_hour');
        delete_option('wgo_items_per_page');
        delete_option('wgo_days_to_store');
        delete_option('wgo_im_behind_proxy');
        delete_option('wgo_notification_email');
        delete_option('wgo_notify_requests_more_than_sd');
        delete_option('wgo_notify_requests_more_than_2sd');
        delete_option('wgo_notify_requests_more_than_3sd');
        delete_option('wgo_save_payloads');
        delete_option('wgo_save_only_payloads_matching_regex');

        WhatsGoingOnBackendController::get_instance()->_uninstall_waf();
    }

    /**
     * It simply returns HTTP_X_FORWARDED_FOR - HTTP_CLIENT_IP - REMOTE_ADDR..
     */
    public function current_remote_ips()
    {
        return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '').'-'
            .(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').'-'
            .$_SERVER['REMOTE_ADDR'];
    }

    /**
     * This only saves 404s to detect anomalous behaviours..
     */
    public function save_404s()
    {
        if (is_404()) {
            global $wpdb;
            $url = $_SERVER['REQUEST_SCHEME'].'://'
                .$_SERVER['SERVER_NAME']
                .(!in_array($_SERVER['SERVER_PORT'], [80, 443]) ? ':'.$_SERVER['SERVER_PORT'] : '')
                .$_SERVER['REQUEST_URI'];

            $sql = 'INSERT INTO '.$wpdb->prefix.'whats_going_on_404s '
                .'(time, url, remote_ip, remote_port, user_agent, method) '
                .'VALUES ('
                ."now(), '"
                .urlencode(substr($url, 0, 255))."', '"
                .$this->current_remote_ips()."', '"
                .$_SERVER['REMOTE_PORT']."', '"
                .(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."','"
                .$_SERVER['REQUEST_METHOD']."'"
                .');';
            $wpdb->get_results($sql);
        }
    }

    /**
     * It adds assets only for the backend..
     */
    public function enqueue_admin_css_js($hook)
    {
        wp_enqueue_style('wgo_custom_style', plugin_dir_url(__FILE__).'lib/wgo.css', false, '1.0.2');
        wp_enqueue_style('wgo_chart_style', plugin_dir_url(__FILE__).'lib/Chart.min.css', false, '1');
        wp_enqueue_style('wgo_map_style', plugin_dir_url(__FILE__).'lib/svgMap.min.css', false, '1');
        wp_enqueue_script('wgo_custom_script', plugin_dir_url(__FILE__).'lib/wgo.js', [], '1.0.2');
        wp_enqueue_script('wgo_chart_script', plugin_dir_url(__FILE__).'lib/Chart.min.js', [], '1');
        wp_enqueue_script('wgo_map_script', plugin_dir_url(__FILE__).'lib/svgMap.min.js', [], '1');
    }
}

// Do all..
WhatsGoingOn::get_instance();
