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

    private function __construct()
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
            'id' => 'wgo-topbar',
            'parent' => null,
            'group' => null,
            'title' => 'What\'s going on',
            'href' => admin_url('tools.php?page=whats-going-on'),
            'meta' => [
                'title' => 'What\'s going on', //This title will show on hover
                'class' => 'wgo-topbar',
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

        if (isset($_REQUEST['current-page'])) {
            $current_page = intval($_REQUEST['current-page']);
        } else {
            $current_page = 1;
        }
        //var_dump($current_page);

        $submitting = false;
        foreach ($_REQUEST as $key => $value) {
            if (preg_match('/submit/', $key)) {
                $submitting = true;
            }
        }

        // Security control
        if ($submitting) {
            if (!isset($_REQUEST['wgo_nonce'])) {
                $wgoSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: nonce field is missing.</p></div>';
            } elseif (!wp_verify_nonce($_REQUEST['wgo_nonce'], 'wgojnj')) {
                $wgoSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: invalid nonce specified.</p></div>';
            } else {
                /*
                 * Handling actions..
                 */
                if (isset($_REQUEST['submit-previous-page'])) {
                    --$current_page;
                } elseif (isset($_REQUEST['submit-next-page'])) {
                    ++$current_page;
                } elseif (isset($_REQUEST['btn-submit'])) {
                    $wgoSms = $this->_save_main_configs();
                } elseif (isset($_REQUEST['submit-check-email'])) {
                    $wgoSms = $this->_check_email();
                } elseif (isset($_REQUEST['submit-dos-configs'])) {
                    $wgoSms = $this->_save_dos_configs();
                } elseif (isset($_REQUEST['submit-ddos-configs'])) {
                    $wgoSms = $this->_save_ddos_configs();
                } elseif (isset($_REQUEST['submit-remove-all'])) {
                    $wgoSms = $this->_remove_all_data();
                } elseif (isset($_REQUEST['submit-remove-old'])) {
                    $wgoSms = $this->_remove_old_data();
                } elseif (isset($_REQUEST['submit-remove-this-ip'])) {
                    $wgoSms = $this->_remove_this_ip_data();
                } elseif (isset($_REQUEST['submit-save-ips-lists'])) {
                    $wgoSms = $this->_save_ip_lists();
                } elseif (isset($_REQUEST['submit-save-regexes-uri'])) {
                    $wgoSms = $this->_save_regexes_uri();
                } elseif (isset($_REQUEST['submit-save-regexes-payload'])) {
                    $wgoSms = $this->_save_regexes_payload();
                } elseif (isset($_REQUEST['submit-truncate-payloads-log'])) {
                    $wgoSms = $this->_truncate_payloads_log();
                } elseif (isset($_REQUEST['submit-regexes-configs'])) {
                    $wgoSms = $this->_save_regexes_configs();
                } elseif (isset($_REQUEST['submit-remove-regexes-errors'])) {
                    $wgoSms = $this->_remove_regexes_errors_log();
                } elseif (isset($_REQUEST['submit-block-selected-countries'])) {
                    $wgoSms = $this->_add_countries_to_block();
                } elseif (isset($_REQUEST['submit-unblock-selected-countries'])) {
                    $wgoSms = $this->_remove_countries_to_block();
                } elseif (isset($_REQUEST['submit-block-continent'])) {
                    $wgoSms = $this->_block_continent();
                } elseif (isset($_REQUEST['submit-unblock-continent'])) {
                    $wgoSms = $this->_unblock_continent();
                } elseif (isset($_REQUEST['submit-install-full-waf'])) {
                    $wgoSms = $this->_install_waf();
                } elseif (isset($_REQUEST['submit-uninstall-full-waf'])) {
                    $wgoSms = $this->_uninstall_waf();
                } else {
                    $wgoSms = '<div id="message" class="notice notice-success is-dismissible"><p>Cannot understand submitting!</p></div>';
                }
            }
        }

        // Paints the view..
        include WGO_PATH.'view/whats-going-on-view.php';
    }

    private function _install_waf()
    {
        WhatsGoingOn::get_instance()->install_waf();

        return  '<div id="message" class="notice notice-success is-dismissible"><p>Installed!</p></div>';
    }

    private function _uninstall_waf()
    {
        WhatsGoingOn::get_instance()->uninstall_waf();

        return '<div id="message" class="notice notice-success is-dismissible"><p>Uninstalled!</p></div>';
    }

    private function _save_main_configs()
    {
        update_option('wgo_items_per_page', intval($_REQUEST['items_per_page']));
        update_option('wgo_days_to_store', intval($_REQUEST['days_to_store']));
        update_option('wgo_im_behind_proxy', intval($_REQUEST['im_behind_proxy']));
        update_option('wgo_notification_email', sanitize_email($_REQUEST['notification_email']));

        return  '<div id="message" class="notice notice-success is-dismissible"><p>Configurations saved!</p></div>';
    }

    private function _check_email()
    {
        wp_mail(
            get_option('wgo_notification_email'),
            get_bloginfo('name').': What\'s going on: checking email',
            'This is a check for testing that email is working.'
        );

        return  '<div id="message" class="notice notice-success is-dismissible"><p>Email sent!</p></div>';
    }

    private function _save_dos_configs()
    {
        update_option('wgo_limit_requests_per_minute', intval($_REQUEST['limit_requests_per_minute']));
        update_option('wgo_limit_requests_per_hour', intval($_REQUEST['limit_requests_per_hour']));

        return '<div id="message" class="notice notice-success is-dismissible"><p>DoS configs saved!</p></div>';
    }

    private function _save_ddos_configs()
    {
        update_option('wgo_notify_requests_more_than_sd', intval($_REQUEST['notify_requests_more_than_sd']));
        update_option('wgo_notify_requests_more_than_2sd', intval($_REQUEST['notify_requests_more_than_2sd']));
        update_option('wgo_notify_requests_more_than_3sd', intval($_REQUEST['notify_requests_more_than_3sd']));
        update_option('wgo_notify_requests_less_than_25_percent', intval($_REQUEST['notify_requests_less_than_25_percent']));

        return  '<div id="message" class="notice notice-success is-dismissible"><p>DDoS configs saved!</p></div>';
    }

    private function _remove_all_data()
    {
        global $wpdb;

        $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on;');
        $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on_block;');
        $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on_404s;');

        return '<div id="message" class="notice notice-success is-dismissible"><p>All records removed!</p></div>';
    }

    private function _remove_old_data()
    {
        WhatsGoingOnCronjobs::get_instance()->remove_older_than_x_days(7);

        return '<div id="message" class="notice notice-success is-dismissible"><p>Old records removed!</p></div>';
    }

    private function _remove_this_ip_data()
    {
        global $wpdb;

        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
            ."WHERE remote_ip = '".sanitize_text_field($_REQUEST['txt_this_ip'])."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
            ."WHERE remote_ip = '".sanitize_text_field($_REQUEST['txt_this_ip'])."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
            ."WHERE remote_ip = '".sanitize_text_field($_REQUEST['txt_this_ip'])."';";
        $results = $wpdb->get_results($sql);

        return '<div id="message" class="notice notice-success is-dismissible"><p>Records with IP '.sanitize_text_field($_REQUEST['txt_this_ip']).' removed!</p></div>';
    }

    private function _save_ip_lists()
    {
        $this->_save_clean_file(sanitize_textarea_field($_REQUEST['txt_block_list']), ABSPATH.'/wp-content/uploads/wgo-things/block-list.php');
        $this->_save_clean_file(sanitize_textarea_field($_REQUEST['txt_allow_list']), ABSPATH.'/wp-content/uploads/wgo-things/allow-list.php');

        return '<div id="message" class="notice notice-success is-dismissible"><p>Block lists saved!</p></div>';
    }

    private function _save_regexes_uri()
    {
        // Save Regexes
        if (!empty($_FILES['file_regexes_uri']['tmp_name'])) {
            $this->_save_clean_file(file_get_contents($_FILES['file_regexes_uri']['tmp_name']), ABSPATH.'/wp-content/uploads/wgo-things/block-regexes-uri.php');
            $wgoSms = '<div id="message" class="notice notice-success is-dismissible"><p>Regexes only for request uri saved!</p></div>';
        } else {
            $wgoSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: no file selected.</p></div>';
        }

        return $wgoSms;
    }

    private function _save_regexes_payload()
    {
        // Save Regexes
        if (!empty($_FILES['file_regexes_payload']['tmp_name'])) {
            $this->_save_clean_file(file_get_contents($_FILES['file_regexes_payload']['tmp_name']), ABSPATH.'/wp-content/uploads/wgo-things/block-regexes-payload.php');
            $wgoSms = '<div id="message" class="notice notice-success is-dismissible"><p>Regexes only for payload saved!</p></div>';
        } else {
            $wgoSms = '<div id="message" class="notice notice-error is-dismissible"><p>ERROR: no file selected.</p></div>';
        }

        return $wgoSms;
    }

    private function _truncate_payloads_log()
    {
        file_put_contents(WGO_PATH.'waf-payloads.log', '');

        return '<div id="message" class="notice notice-success is-dismissible"><p>Payloads log truncated!</p></div>';
    }

    private function _save_regexes_configs()
    {
        update_option('wgo_save_payloads', intval($_REQUEST['save_payloads']));
        update_option('wgo_save_only_payloads_matching_regex', intval($_REQUEST['save_only_payloads_matching_regex']));

        return '<div id="message" class="notice notice-success is-dismissible"><p>Regexes configs saved!</p></div>';
    }

    private function _remove_regexes_errors_log()
    {
        unlink(WGO_PATH.'waf-errors.log');

        return  '<div id="message" class="notice notice-success is-dismissible"><p>Log file with errors removed!</p></div>';
    }

    private function _save_clean_file($txt_regexes_block, $file_path)
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

    private function _add_countries_to_block()
    {
        $add_countries = sanitize_text_field($_REQUEST['select_block_countries']);

        if (file_exists(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php')) {
            $current_blocking_countries = explode(PHP_EOL, file_get_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php'));
        } else {
            $current_blocking_countries = [];
            $current_blocking_countries[] = '<?php/*';
        }
        foreach ($add_countries as $country_to_block) {
            $current_blocking_countries[] = $country_to_block;
        }
        file_put_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php', implode(PHP_EOL, $current_blocking_countries));

        return '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries blocked ('.implode(', ', $add_countries).')!</p></div>';
    }

    private function _remove_countries_to_block()
    {
        $remove_countries = sanitize_text_field($_REQUEST['select_unblock_countries']);

        if (file_exists(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php')) {
            $current_blocking_countries = explode(PHP_EOL, file_get_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php'));
        } else {
            $current_blocking_countries = [];
            $current_blocking_countries[] = '<?php/*';
        }
        file_put_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php', implode(PHP_EOL, array_diff($current_blocking_countries, $remove_countries)));

        return '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries unblocked ('.implode(', ', $remove_countries).')!</p></div>';
    }

    private function _block_continent()
    {
        $continent_to_block = sanitize_text_field($_REQUEST['select_block_continent'][0]);

        $add_countries = [];
        $array_countries_continents = explode(PHP_EOL, file_get_contents(WGO_PATH.'lib/isoCountriesContinents.csv'));
        foreach ($array_countries_continents as $row) {
            if (!empty($row)) {
                $row_country_code = explode(',', $row)[0];
                $row_continent_code = explode(',', $row)[1];
                if ($continent_to_block == $row_continent_code) {
                    $add_countries[] = $row_country_code;
                }
            }
        }
        //var_dump($add_countries);

        if (file_exists(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php')) {
            $current_blocking_countries = explode(PHP_EOL, file_get_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php'));
        } else {
            $current_blocking_countries = [];
            $current_blocking_countries[] = '<?php/*';
        }
        foreach ($add_countries as $country_to_block) {
            $current_blocking_countries[] = $country_to_block;
        }
        $current_blocking_countries = array_unique($current_blocking_countries);
        file_put_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php', implode(PHP_EOL, $current_blocking_countries));

        return '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries blocked ('.implode(', ', $add_countries).')!</p></div>';
    }

    private function _unblock_continent()
    {
        $continent_to_unblock = sanitize_text_field($_REQUEST['select_unblock_continent'][0]);

        $remove_countries = [];
        $array_countries_continents = explode(PHP_EOL, file_get_contents(WGO_PATH.'lib/isoCountriesContinents.csv'));
        foreach ($array_countries_continents as $row) {
            if (!empty($row)) {
                $row_country_code = explode(',', $row)[0];
                $row_continent_code = explode(',', $row)[1];
                if ($continent_to_unblock == $row_continent_code) {
                    $remove_countries[] = $row_country_code;
                }
            }
        }
        //var_dump($remove_countries);

        if (file_exists(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php')) {
            $current_blocking_countries = explode(PHP_EOL, file_get_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php'));
        } else {
            $current_blocking_countries = [];
            $current_blocking_countries[] = '<?php/*';
        }
        file_put_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php', implode(PHP_EOL, array_diff($current_blocking_countries, $remove_countries)));

        return '<div id="message" class="notice notice-success is-dismissible"><p>Selected countries unblocked ('.implode(', ', $remove_countries).')!</p></div>';
    }
}
