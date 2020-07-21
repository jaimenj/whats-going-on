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
define('WGOJNJ_PATH', plugin_dir_path(__FILE__));

include_once WGOJNJ_PATH.'cronjobs.php';
include_once WGOJNJ_PATH.'backend-controller.php';

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

    public function __construct()
    {
        // Activation and deactivation..
        register_activation_hook(__FILE__, [$this, 'wgojnj_activation']);
        register_deactivation_hook(__FILE__, [$this, 'wgojnj_deactivation']);

        // Main actions..
        add_action('template_redirect', [$this, 'wgojnj_save_404s']);
        add_action('admin_enqueue_scripts', [$this, 'wpdocs_selectively_enqueue_admin_script']);

        WhatsGoingOnCronjobs::get_instance();
        WhatsGoingOnBackendController::get_instance();
    }

    public function wgojnj_activation()
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

        $this->wgojnj_register_options();
        add_option('wgojnj_limit_requests_per_minute', '-1');
        add_option('wgojnj_limit_requests_per_hour', -1);
        add_option('wgojnj_items_per_page', '10');
        add_option('wgojnj_days_to_store', '7');
        add_option('wgojnj_im_behind_proxy', 0);
        add_option('wgojnj_notification_email', '');
        add_option('wgojnj_notify_requests_more_than_sd', 0);
        add_option('wgojnj_notify_requests_more_than_sd', 1);
        add_option('wgojnj_notify_requests_more_than_sd', 2);
    }

    // Options
    public function wgojnj_register_options()
    {
        register_setting('wgojnj_options_group', 'wgojnj_limit_requests_per_minute');
        register_setting('wgojnj_options_group', 'wgojnj_limit_requests_per_hour');
        register_setting('wgojnj_options_group', 'wgojnj_items_per_page');
        register_setting('wgojnj_options_group', 'wgojnj_days_to_store');
        register_setting('wgojnj_options_group', 'wgojnj_im_behind_proxy');
        register_setting('wgojnj_options_group', 'wgojnj_notification_email');
        register_setting('wgojnj_options_group', 'wgojnj_notify_requests_more_than_sd');
        register_setting('wgojnj_options_group', 'wgojnj_notify_requests_more_than_2sd');
        register_setting('wgojnj_options_group', 'wgojnj_notify_requests_more_than_3sd');
    }

    public function wgojnj_deactivation()
    {
        global $wpdb;
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_block;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_404s;';
        $wpdb->get_results($sql);

        if (file_exists(ABSPATH.'.user.ini')) {
            unlink(ABSPATH.'.user.ini');
        }
    }

    /**
     * It simply returns HTTP_X_FORWARDED_FOR - HTTP_CLIENT_IP - REMOTE_ADDR..
     */
    public function wgojnj_current_remote_ips()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'].'-'.$_SERVER['HTTP_CLIENT_IP'].'-'.$_SERVER['REMOTE_ADDR'];
    }

    /**
     * This only saves 404s to detect anomalous behaviours..
     */
    public function wgojnj_save_404s()
    {
        if (is_404()) {
            global $wpdb;
            $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

            $sql = 'INSERT INTO '.$wpdb->prefix.'whats_going_on_404s '
                .'(time, url, remote_ip, remote_port, user_agent, method) '
                .'VALUES ('
                ."now(), '"
                .$url."', '"
                .$this->wgojnj_current_remote_ips()."', '"
                .$_SERVER['REMOTE_PORT']."', '"
                .$_SERVER['HTTP_USER_AGENT']."', '"
                .$_SERVER['REQUEST_METHOD']."'"
                .');';
            $wpdb->get_results($sql);
        }
    }

    /**
     * It adds assets only for the backend..
     */
    public function wpdocs_selectively_enqueue_admin_script($hook)
    {
        wp_enqueue_style('wgojnj_custom_style', plugin_dir_url(__FILE__).'lib/wgojnj.css', false, '1.0.1');
        wp_enqueue_style('wgojnj_chart_style', plugin_dir_url(__FILE__).'lib/Chart.min.css', false, '1');
        wp_enqueue_script('wgojnj_custom_script', plugin_dir_url(__FILE__).'lib/wgojnj.js', [], '1.0.1');
        wp_enqueue_script('wgojnj_chart_script', plugin_dir_url(__FILE__).'lib/Chart.min.js', [], '1');
    }
}

WhatsGoingOn::get_instance();
