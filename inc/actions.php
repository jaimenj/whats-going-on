<?php

defined('ABSPATH') or die('No no no');

/**
 * This only saves 404s to detect anomalous behaviours..
 */
function wgojnj_save_404s()
{
    if (is_404()) {
        global $wpdb;
        $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

        $sql = 'INSERT INTO '.$wpdb->prefix.'whats_going_on_404s '
            .'(time, url, remote_ip, remote_port, user_agent, method) '
            .'VALUES ('
            ."now(), '"
            .$url."', '"
            .wgojnj_current_remote_ips()."', '"
            .$_SERVER['REMOTE_PORT']."', '"
            .$_SERVER['HTTP_USER_AGENT']."', '"
            .$_SERVER['REQUEST_METHOD']."'"
            .');';
        $wpdb->get_results($sql);
    }
}
add_action('template_redirect', 'wgojnj_save_404s');
