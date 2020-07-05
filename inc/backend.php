<?php

defined('ABSPATH') or die('No no no');

// Topbar
function wgojnj_admin_bar_menu($admin_bar)
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
add_action('admin_bar_menu', 'wgojnj_admin_bar_menu', 99);

// Tools menu
function wgojnj_whats_going_on_controller()
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
            if (isset($_REQUEST['submit'])) {
                update_option('wgojnj_limit_requests_per_minute', stripslashes($_REQUEST['limit_requests_per_minute']));
                update_option('wgojnj_limit_requests_per_hour', stripslashes($_REQUEST['limit_requests_per_hour']));
                update_option('wgojnj_items_per_page', stripslashes($_REQUEST['items_per_page']));

                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Configurations saved!</p></div>';
            } elseif (isset($_REQUEST['submit-previous-page'])) {
                --$current_page;
            } elseif (isset($_REQUEST['submit-next-page'])) {
                ++$current_page;
            } elseif (isset($_REQUEST['submit-remove-all'])) {
                $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on;');
                $wpdb->get_results('TRUNCATE '.$wpdb->prefix.'whats_going_on_block;');
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>All records removed!</p></div>';
            } elseif (isset($_REQUEST['submit-remove-old'])) {
                wgojnj_remove_older_than_a_week_data();
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
                wgojnj_save_clean_file($_REQUEST['txt-block-list'], WGOJNJ_PATH.'block-list.php');
                wgojnj_save_clean_file($_REQUEST['txt-allow-list'], WGOJNJ_PATH.'allow-list.php');
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Block lists saved!</p></div>';
            } elseif (isset($_REQUEST['submit-save-regexes'])) {
                // Save Regexes
                wgojnj_save_clean_file($_REQUEST['txt-regexes-block'], WGOJNJ_PATH.'block-regexes.php');
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Regexes saved!</p></div>';
            } elseif (isset($_REQUEST['submit-install-full-waf'])) {
                file_put_contents(
                    $userIniFilePath,
                    "auto_prepend_file = '".WGOJNJ_PATH."inc/waf-going-on.php';".PHP_EOL
                );
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Installed!</p></div>';
            } elseif (isset($_REQUEST['submit-uninstall-full-waf'])) {
                unlink($userIniFilePath);
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Uninstalled!</p></div>';
            } else {
                $wgojnjSms = '<div id="message" class="notice notice-success is-dismissible"><p>Cannot understand submitting!</p></div>';
            }
        }
    }

    include WGOJNJ_PATH.'view/whats-going-on-view.php';
}
function wgojnj_handle_admin_page()
{
    $page_title = 'What\'s going on';
    $menu_title = $page_title;
    $capability = 'administrator';
    $menu_slug = 'whats-going-on';
    $function = 'wgojnj_whats_going_on_controller';
    $position = null;
    add_management_page($page_title, $menu_title, $capability, $menu_slug, $function, $position);
}
add_action('admin_menu', 'wgojnj_handle_admin_page');

// Options
function wgojnj_register_options()
{
    register_setting('wgojnj_options_group', 'wgojnj_limit_requests_per_minute');
    register_setting('wgojnj_options_group', 'wgojnj_limit_requests_per_hour');
    register_setting('wgojnj_options_group', 'wgojnj_items_per_page');
}

function wgojnj_add_ip_to_the_block_list($the_ip)
{
    $return_msg = '';

    $file_path = WGOJNJ_PATH.'block-list.php';
    if (file_exists($file_path)) {
        $the_file = file($file_path);
        $ips = [];
        for ($i = 1; $i < count($the_file); ++$i) {
            $ips[] = trim($the_file[$i]).PHP_EOL;
        }
    }
    $ips[] = $the_ip.PHP_EOL;
    if (count($ips) != count(array_unique($ips))) {
        $return_msg = '<div id="message" class="notice notice-success is-dismissible"><p>IP yet in the list!</p></div>';
    } else {
        $return_msg = '<div id="message" class="notice notice-success is-dismissible"><p>IP '.$_REQUEST['txt_this_ip'].' added to the block list!</p></div>';
    }
    $ips = array_unique($ips);
    file_put_contents($file_path, array_merge(['<?php'.PHP_EOL], $ips));

    return $return_msg;
}

function wgojnj_remove_ip_from_the_block_list($the_ip)
{
    $removed = false;

    $file_path = WGOJNJ_PATH.'block-list.php';
    if (file_exists($file_path)) {
        $the_file = file($file_path);
        $ips = [];
        if (count($the_file > 1)) {
            for ($i = 1; $i < count($the_file); ++$i) {
                if (trim($the_ip) != trim($the_file[$i])) {
                    $ips[] = trim($the_file[$i]).PHP_EOL;
                } else {
                    $removed = true;
                }
            }
        }
        file_put_contents($file_path, array_merge(['<?php'.PHP_EOL], $ips));
    }

    if ($removed) {
        return '<div id="message" class="notice notice-success is-dismissible"><p>IP '.$_REQUEST['txt_this_ip'].' removed from the block list!</p></div>';
    } else {
        return '<div id="message" class="notice notice-success is-dismissible"><p>IP not found!</p></div>';
    }
}

function wgojnj_add_ip_to_the_allow_list($the_ip)
{
    $return_msg = '';

    $file_path = WGOJNJ_PATH.'allow-list.php';
    if (file_exists($file_path)) {
        $the_file = file($file_path);
        $ips = [];
        for ($i = 1; $i < count($the_file); ++$i) {
            $ips[] = trim($the_file[$i]).PHP_EOL;
        }
    }
    $ips[] = $the_ip.PHP_EOL;
    if (count($ips) != count(array_unique($ips))) {
        $return_msg = '<div id="message" class="notice notice-success is-dismissible"><p>IP yet in the list!</p></div>';
    } else {
        $return_msg = '<div id="message" class="notice notice-success is-dismissible"><p>IP '.$_REQUEST['txt_this_ip'].' added to the allow list!</p></div>';
    }
    $ips = array_unique($ips);
    file_put_contents($file_path, array_merge(['<?php'.PHP_EOL], $ips));

    return $return_msg;
}

function wgojnj_remove_ip_from_the_allow_list($the_ip)
{
    $removed = false;

    $file_path = WGOJNJ_PATH.'allow-list.php';
    if (file_exists($file_path)) {
        $the_file = file($file_path);
        $ips = [];
        if (count($the_file > 1)) {
            for ($i = 1; $i < count($the_file); ++$i) {
                if (trim($the_ip) != trim($the_file[$i])) {
                    $ips[] = trim($the_file[$i]).PHP_EOL;
                }
            }
        }
        file_put_contents($file_path, array_merge(['<?php'.PHP_EOL], $ips));
    }

    if ($removed) {
        return '<div id="message" class="notice notice-success is-dismissible"><p>IP '.$_REQUEST['txt_this_ip'].' removed from the allow list!</p></div>';
    } else {
        return '<div id="message" class="notice notice-success is-dismissible"><p>IP not found!</p></div>';
    }
}

function wgojnj_save_clean_file($txt_regexes_block, $file_path)
{
    //var_dump($txt_regexes_block); die;
    $final_array = [];
    $final_array[] = '<?php'.PHP_EOL;
    $array = explode("\r\n", $txt_regexes_block);
    foreach ($array as $item) {
        $item = trim($item);
        if (!empty($item)) {
            $final_array[] = $item.PHP_EOL;
        }
    }

    file_put_contents($file_path, $final_array);
}
