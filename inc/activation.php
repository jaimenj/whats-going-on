<?php

defined('ABSPATH') or die('No no no');

function wgojnj_activation()
{
    global $wpdb;
    
    // Main table..
    $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on ('
        //."id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,"
        .'time DATETIME NOT NULL,'
        .'url VARCHAR(256) NOT NULL,'
        .'remote_ip VARCHAR(64) NOT NULL,'
        .'remote_port INT NOT NULL,'
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
        .'user_agent VARCHAR(128) NOT NULL,'
        .'method VARCHAR(8) NOT NULL'
        .');';
    $wpdb->get_results($sql);

    add_option('wgojnj_limit_requests_per_minute', '-1');
    add_option('wgojnj_limit_requests_per_hour', -1);
    add_option('wgojnj_items_per_page', '10');
}

function wgojnj_deactivation()
{
    global $wpdb;
    $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on;';
    $wpdb->get_results($sql);
    $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_block;';
    $wpdb->get_results($sql);
    $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_404s;';

    if (file_exists(ABSPATH.'.user.ini')) {
        unlink(ABSPATH.'.user.ini');
    }
}
