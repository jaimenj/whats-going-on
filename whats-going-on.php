<?php
/**
 * Plugin Name: What's going on
 * Plugin URI: https://jnjsite.com/whats-going-on-for-wordpress/
 * License: GPLv2 or later
 * Description: A tiny WAF, a tool for control and showing what kind of requests are being made to your WordPress.
 * Version: 0.9
 * Author: Jaime Niñoles
 * Author URI: https://jnjsite.com/
 */
defined('ABSPATH') or die('No no no');
define('WGO_PATH', plugin_dir_path(__FILE__));

include_once WGO_PATH.'whats-going-on-database.php';
include_once WGO_PATH.'whats-going-on-cronjobs.php';
include_once WGO_PATH.'whats-going-on-backend-controller.php';
include_once WGO_PATH.'whats-going-on-ajax-controller.php';

class WhatsGoingOn
{
    private static $instance;

    private $waf_config_line;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->waf_config_line = PHP_EOL."auto_prepend_file = '".ABSPATH."waf-going-on.php';".PHP_EOL;

        // Activation and deactivation..
        register_activation_hook(__FILE__, [$this, 'activation']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation']);

        WhatsGoingOnDatabase::get_instance();

        // Main actions..
        add_action('template_redirect', [$this, 'save_404s']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_css_js']);
        add_action('in_admin_header', [$this, 'add_admin_header_libraries']);

        WhatsGoingOnCronjobs::get_instance();
        WhatsGoingOnBackendController::get_instance();
        WhatsGoingOnAjaxController::get_instance();
    }

    public function activation()
    {
        register_setting('wgo_options_group', 'wgo_db_version');
        register_setting('wgo_options_group', 'wgo_waf_installed');
        register_setting('wgo_options_group', 'wgo_limit_requests_per_minute');
        register_setting('wgo_options_group', 'wgo_limit_requests_per_hour');
        register_setting('wgo_options_group', 'wgo_items_per_page');
        register_setting('wgo_options_group', 'wgo_days_to_store');
        register_setting('wgo_options_group', 'wgo_im_behind_proxy');
        register_setting('wgo_options_group', 'wgo_notification_email');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_2sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_more_than_3sd');
        register_setting('wgo_options_group', 'wgo_notify_requests_less_than_x_percent');
        register_setting('wgo_options_group', 'wgo_save_payloads');
        register_setting('wgo_options_group', 'wgo_save_payloads_matching_uri_regex');
        register_setting('wgo_options_group', 'wgo_save_payloads_matching_payload_regex');

        add_option('wgo_db_version', 1);
        add_option('wgo_waf_installed', 0);
        add_option('wgo_limit_requests_per_minute', -1);
        add_option('wgo_limit_requests_per_hour', -1);
        add_option('wgo_items_per_page', 10);
        add_option('wgo_days_to_store', 7);
        add_option('wgo_im_behind_proxy', 0);
        add_option('wgo_notification_email', '');
        add_option('wgo_notify_requests_more_than_sd', 0);
        add_option('wgo_notify_requests_more_than_2sd', 0);
        add_option('wgo_notify_requests_more_than_3sd', 0);
        add_option('wgo_notify_requests_less_than_x_percent', -1);
        add_option('wgo_save_payloads', 0);
        add_option('wgo_save_payloads_matching_uri_regex', 0);
        add_option('wgo_save_payloads_matching_payload_regex', 0);

        WhatsGoingOnDatabase::get_instance()->create_initial_tables();

        if (!file_exists(wp_upload_dir()['basedir'].'/wgo-things')) {
            mkdir(wp_upload_dir()['basedir'].'/wgo-things');
        }
        file_put_contents(
            wp_upload_dir()['basedir'].'/wgo-things/.htaccess',
            'Order allow,deny'.PHP_EOL
            .'Deny from all'.PHP_EOL
        );
    }

    public function deactivation()
    {
        WhatsGoingOnDatabase::get_instance()->remove_tables();

        $this->uninstall_waf();
    }

    public function uninstall()
    {
        delete_option('wgo_db_version');
        delete_option('wgo_waf_installed');
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
        delete_option('wgo_save_payloads_matching_uri_regex');
        delete_option('wgo_save_payloads_matching_payload_regex');

        $this->uninstall_waf();

        if (file_exists(wp_upload_dir()['basedir'].'/wgo-things')) {
            $dir = dir(wp_upload_dir()['basedir'].'/wgo-things');
            while (false !== ($entry = $dir->read())) {
                $current_path = wp_upload_dir()['basedir'].'/wgo-things/'.$entry;
                if ('.' != $entry and '..' != $entry) {
                    unlink($current_path);
                }
            }
            $dir->close();
            rmdir(wp_upload_dir()['basedir'].'/wgo-things');
        }

        if (file_exists(ABSPATH.'waf-going-on.php')) {
            unlink(ABSPATH.'waf-going-on.php');
        }
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
        wp_enqueue_style('wgo_custom_style', plugin_dir_url(__FILE__).'lib/wgo.min.css', false, '0.9');
        wp_enqueue_script('wgo_custom_script', plugin_dir_url(__FILE__).'lib/wgo.min.js', [], '0.9');
    }

    public function add_admin_header_libraries()
    {
        echo '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.22/b-1.6.4/b-colvis-1.6.4/b-flash-1.6.4/b-html5-1.6.4/b-print-1.6.4/cr-1.5.2/fc-3.3.1/r-2.2.6/datatables.min.css"/>'
            .'<link href="https://cdn.jsdelivr.net/gh/StephanWagner/svgMap@v1.5.0/dist/svgMap.min.css" rel="stylesheet">'
            .'<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>'
            .'<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>'
            .'<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.22/b-1.6.4/b-colvis-1.6.4/b-flash-1.6.4/b-html5-1.6.4/b-print-1.6.4/cr-1.5.2/fc-3.3.1/r-2.2.6/datatables.min.js"></script>'
            .'<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>'
            .'<script src="https://cdn.jsdelivr.net/gh/StephanWagner/svgMap@v1.5.0/dist/svgMap.min.js"></script>';
    }

    public function is_waf_installed()
    {
        $its_ok = false;

        if (file_exists(ABSPATH.'.user.ini')) {
            $main_user_ini_content = file_get_contents(ABSPATH.'.user.ini');
            if (false !== strpos($main_user_ini_content, $this->waf_config_line)) {
                $its_ok = true;
            }
        }

        return $its_ok;
    }

    public function install_waf()
    {
        // Main .user.ini file..
        if (!$this->is_waf_installed()) {
            file_put_contents(ABSPATH.'.user.ini', $this->waf_config_line, FILE_APPEND);
        }

        $this->copy_main_waf_file();

        $this->_install_recursive_waf('wp-admin/');
        $content_dir = explode('/', WP_CONTENT_DIR)[count(explode('/', WP_CONTENT_DIR)) - 1];
        $this->_install_recursive_waf($content_dir.'/');
        $this->_install_recursive_waf('wp-includes/');

        update_option('wgo_waf_installed', 1);
    }

    public function copy_main_waf_file()
    {
        global $wpdb;

        // WAF file and setting configs..
        $waf_content = explode(PHP_EOL, file_get_contents(WGO_PATH.'waf-going-on.php'));
        $waf_content[2] = "define('WGO_ABSPATH', '".ABSPATH."');";
        $waf_content[3] = "define('WGO_DB_NAME', '".DB_NAME."');";
        $waf_content[4] = "define('WGO_DB_USER', '".DB_USER."');";
        $waf_content[5] = "define('WGO_DB_PASSWORD', '".DB_PASSWORD."');";
        $waf_content[6] = "define('WGO_DB_HOST', '".DB_HOST."');";
        $waf_content[7] = "define('WGO_TABLE_PREFIX', '".$wpdb->prefix."');";
        $waf_content[8] = "define('WGO_WP_UPLOAD_DIR', '".wp_upload_dir()['basedir']."');";
        $waf_content[9] = "define('WGO_PLUGIN_DIR_PATH', '".plugin_dir_path(__FILE__)."');";
        file_put_contents(ABSPATH.'waf-going-on.php', implode(PHP_EOL, $waf_content));
    }

    public function install_recursive_waf($current_path)
    {
        $this->_install_recursive_waf($current_path);
    }

    private function _install_recursive_waf($current_path)
    {
        file_put_contents(ABSPATH.$current_path.'.user.ini', $this->waf_config_line);

        $dir = dir(ABSPATH.$current_path);
        while (false !== ($entry = $dir->read())) {
            $new_current_path = $current_path.$entry.'/';
            if ('.' != $entry and '..' != $entry and is_dir(ABSPATH.$new_current_path)) {
                $this->_install_recursive_waf($new_current_path);
            }
        }
        $dir->close();
    }

    public function uninstall_waf()
    {
        $new_main_user_ini_content = file_get_contents(ABSPATH.'.user.ini');
        $new_main_user_ini_content = str_replace($this->waf_config_line, '', $new_main_user_ini_content);
        file_put_contents(ABSPATH.'.user.ini', $new_main_user_ini_content);
        $this->_uninstall_recursive_waf('wp-admin/');
        $content_dir = explode('/', WP_CONTENT_DIR)[count(explode('/', WP_CONTENT_DIR)) - 1];
        $this->_uninstall_recursive_waf($content_dir.'/');
        $this->_uninstall_recursive_waf('wp-includes/');

        update_option('wgo_waf_installed', 0);
    }

    private function _uninstall_recursive_waf($current_path)
    {
        if (file_exists(ABSPATH.$current_path.'.user.ini')) {
            unlink(ABSPATH.$current_path.'.user.ini');
        }

        $dir = dir(ABSPATH.$current_path);
        while (false !== ($entry = $dir->read())) {
            $new_current_path = $current_path.$entry.'/';
            if ('.' != $entry and '..' != $entry and is_dir(ABSPATH.$new_current_path)) {
                //echo $new_current_path.'<br>';
                $this->_uninstall_recursive_waf($new_current_path);
            }
        }
        $dir->close();
    }
}

// Do all..
WhatsGoingOn::get_instance();
