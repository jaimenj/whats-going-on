<?php

defined('ABSPATH') or die('No no no');

/**
 * REQUEST_URI /jhkjhkjlhhkj?kjasdhkasdjk=ajsdbakjb
 * REQUEST_METHOD
 * REQUEST_SCHEME
 * REMOTE_PORT
 * REMOTE_ADDR
 * HTTP_USER_AGENT
 * HTTP_HOST
 * SERVER_NAME localhost
 * SERVER_PORT.
 *
 * REQUEST_SCHEME :// SERVER_NAME : SERVER_PORT REQUEST_URI
 */
function wgojnj_save_request()
{
    // Do not track if .user.ini
    if (file_exists(ABSPATH.'.user.ini')) {
        return;
    }

    global $wpdb;
    $url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $max_per_minute = get_option('wgojnj_limit_requests_per_minute');
    $max_per_hour = get_option('wgojnj_limit_requests_per_hour');

    $requests_last_minute = $wpdb->get_var(
        'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on '
        ."WHERE remote_ip = '".$_SERVER['REMOTE_ADDR']." '"
        .'AND time > NOW() - INTERVAL 1 MINUTE;'
    );
    $requests_last_hour = $wpdb->get_var(
        'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on '
        ."WHERE remote_ip = '".$_SERVER['REMOTE_ADDR']." '"
        .'AND time > NOW() - INTERVAL 60 MINUTE;'
    );

    $sql = 'INSERT INTO '.$wpdb->prefix.'whats_going_on '
        .'(time, url, remote_ip, remote_port, user_agent, method, last_minute, last_hour) '
        .'VALUES ('
        ."now(), '"
        .$url."', '"
        .$_SERVER['REMOTE_ADDR']."', '"
        .$_SERVER['REMOTE_PORT']."', '"
        .$_SERVER['HTTP_USER_AGENT']."', '"
        .$_SERVER['REQUEST_METHOD']."', "
        .$requests_last_minute.', '
        .$requests_last_hour
        .');';
    $wpdb->get_results($sql);

    $comments = '';
    $retry = 0;
    if ($max_per_minute > 0 and $requests_last_minute > $max_per_minute) {
        $comments .= 'Reached max requests per minute: '.$max_per_minute.' ';
        $retry_time = 60;
    }
    if ($max_per_hour > 0 and $request_last_hour > $max_per_hour) {
        $comments .= 'Reached max requests per hour: '.$max_per_hour.' ';
        $retry_time = 3600;
    }
    if (!empty($comments)) {
        $sql = 'INSERT INTO '.$wpdb->prefix.'whats_going_on_block '
            .'(time, remote_ip, remote_port, user_agent, comments) '
            .'VALUES ('
            ."now(), '"
            .$_SERVER['REMOTE_ADDR']."', '"
            .$_SERVER['REMOTE_PORT']."', '"
            .$_SERVER['HTTP_USER_AGENT']."','"
            .$comments."'"
            .');';
        $wpdb->get_results($sql);

        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: '.$retry_time);
        die('You are not allowed to access this file.');
    }
}
add_action('init', 'wgojnj_save_request');
