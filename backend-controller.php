<?php

defined('ABSPATH') or die('No no no');

class WhatsGoingOnBackendController
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
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 99);
        add_action('admin_menu', [$this, 'add_admin_page']);
    }

    public function add_admin_bar_menu($admin_bar)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $admin_bar->add_menu([
            'id' => 'wgojnj-topbar',
            'parent' => null,
            'group' => null,
            'title' => 'What\'s going on',
            'href' => admin_url('tools.php?page=whats-going-on'),
            'meta' => [
                'title' => 'What\'s going on', //This title will show on hover
                'class' => 'wgojnj-topbar',
            ],
        ]);
    }

    public function add_admin_page()
    {
        $page_title = 'What\'s going on';
        $menu_title = $page_title;
        $capability = 'administrator';
        $menu_slug = 'whats-going-on';
        $function = [$this, 'wgo_main_admin_controller'];
        $position = null;
        add_management_page($page_title, $menu_title, $capability, $menu_slug, $function, $position);
    }

    public function wgo_main_admin_controller()
    {
        global $wpdb;
        global $current_page;
        $userIniFilePath = ABSPATH.'.user.ini';

        if (isset($_REQUEST['current-page'])) {
            $current_page = intval($_REQUEST['current-page']);
        } else {
            $current_page = 1;
        }
        //var_export($current_page);

        $submitting = false;
        foreach ($_REQUEST as $key => $value) {
            if (preg_match('/submit/', $key)) {
                $submitting = true;
            }
        }

        // Security control
        if ($submitting) {
            if (!isset($_REQUEST['wgojnj_nonce'])) {
                $wgojnjSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: nonce field is missing.</p></div>';
            } elseif (!wp_verify_nonce($_REQUEST['wgojnj_nonce'], 'wgojnj')) {
                $wgojnjSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: invalid nonce specified.</p></div>';
            } else {
                /*
                * Handling actions..
                */
                if (isset($_REQUEST['btn-submit'])) {
                    update_option('wgojnj_items_per_page', stripslashes($_REQUEST['items_per_page']));
                    update_option('wgojnj_days_to_store', stripslashes($_REQUEST['days_to_store']));
                    update_option('wgojnj_im_behind_proxy', stripslashes($_REQUEST['im_behind_proxy']));
                    update_option('wgojnj_notification_email', stripslashes($_REQUEST['notification_email']));
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Configurations saved!</p></div>';
                } elseif (isset($_REQUEST['submit-dos-configs'])) {
                    update_option('wgojnj_limit_requests_per_minute', stripslashes($_REQUEST['limit_requests_per_minute']));
                    update_option('wgojnj_limit_requests_per_hour', stripslashes($_REQUEST['limit_requests_per_hour']));
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>DoS configs saved!</p></div>';
                } elseif (isset($_REQUEST['submit-ddos-configs'])) {
                    update_option('wgojnj_notify_requests_more_than_sd', stripslashes($_REQUEST['notify_requests_more_than_sd']));
                    update_option('wgojnj_notify_requests_more_than_2sd', stripslashes($_REQUEST['notify_requests_more_than_2sd']));
                    update_option('wgojnj_notify_requests_more_than_3sd', stripslashes($_REQUEST['notify_requests_more_than_3sd']));
                    update_option('wgojnj_notify_requests_less_than_25_percent', stripslashes($_REQUEST['notify_requests_less_than_25_percent']));
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>DDoS configs saved!</p></div>';
                } elseif (isset($_REQUEST['submit-previous-page'])) {
                    --$current_page;
                } elseif (isset($_REQUEST['submit-next-page'])) {
                    ++$current_page;
                } elseif (isset($_REQUEST['submit-remove-all'])) {
                    $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on;');
                    $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on_block;');
                    $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on_404s;');
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>All records removed!</p></div>';
                } elseif (isset($_REQUEST['submit-remove-old'])) {
                    WhatsGoingOnCronjobs::get_instance()->remove_older_than_x_days(7);
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Old records removed!</p></div>';
                } elseif (isset($_REQUEST['submit-remove-this-ip'])) {
                    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
                        ."WHERE remote_ip = '".$_REQUEST['txt_this_ip']."';";
                    $results = $wpdb->get_results($sql);
                    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
                        ."WHERE remote_ip = '".$_REQUEST['txt_this_ip']."';";
                    $results = $wpdb->get_results($sql);
                    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
                        ."WHERE remote_ip = '".$_REQUEST['txt_this_ip']."';";
                    $results = $wpdb->get_results($sql);
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Records with IP '.$_REQUEST['txt_this_ip'].' removed!</p></div>';
                } elseif (isset($_REQUEST['submit-save-ips-lists'])) {
                    $this->save_clean_file($_REQUEST['txt-block-list'], WGOJNJ_PATH.'block-list.php');
                    $this->save_clean_file($_REQUEST['txt-allow-list'], WGOJNJ_PATH.'allow-list.php');
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Block lists saved!</p></div>';
                } elseif (isset($_REQUEST['submit-save-regexes-uri'])) {
                    // Save Regexes
                    if (!empty($_FILES['file-regexes-uri']['tmp_name'])) {
                        $this->save_clean_file(file_get_contents($_FILES['file-regexes-uri']['tmp_name']), WGOJNJ_PATH.'block-regexes-uri.php');
                        $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Regexes only for request uri saved!</p></div>';
                    } else {
                        $wgojnjSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: no file selected.</p></div>';
                    }
                } elseif (isset($_REQUEST['submit-save-regexes-payload'])) {
                    // Save Regexes
                    if (!empty($_FILES['file-regexes-payload']['tmp_name'])) {
                        $this->save_clean_file(file_get_contents($_FILES['file-regexes-payload']['tmp_name']), WGOJNJ_PATH.'block-regexes-payload.php');
                        $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Regexes only for payload saved!</p></div>';
                    } else {
                        $wgojnjSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: no file selected.</p></div>';
                    }
                } elseif (isset($_REQUEST['submit-install-full-waf'])) {
                    file_put_contents(
                        $userIniFilePath,
                        "auto_prepend_file = '".WGOJNJ_PATH."waf-going-on.php';".PHP_EOL
                    );
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Installed!</p></div>';
                } elseif (isset($_REQUEST['submit-uninstall-full-waf'])) {
                    unlink($userIniFilePath);
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Uninstalled!</p></div>';
                } elseif (isset($_REQUEST['submit-remove-regexes-errors'])) {
                    unlink(WGOJNJ_PATH.'waf-errors.log');
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Log file with errors removed!</p></div>';
                } elseif (isset($_REQUEST['submit-block-selected-countries'])) {

                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries blocked!</p></div>';
                } elseif (isset($_REQUEST['submit-unblock-selected-countries'])) {

                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries unblocked!</p></div>';
                } else {
                    $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Cannot understand submitting!</p></div>';
                }
            }
        }

        include WGOJNJ_PATH.'view/whats-going-on-view.php';
    }

    public function save_clean_file($txt_regexes_block, $file_path)
    {
        $final_array = [];
        $final_array[] = '<?php/*'.PHP_EOL;

        $array = explode("\r\n", $txt_regexes_block);
        foreach ($array as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $final_array[] = $item.PHP_EOL;
            }
        }

        file_put_contents($file_path, $final_array);
    }
}
