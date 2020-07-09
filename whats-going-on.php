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

include_once WGOJNJ_PATH.'inc/activation.php';
register_activation_hook(__FILE__, 'wgojnj_activation');
register_deactivation_hook(__FILE__, 'wgojnj_deactivation');

include_once WGOJNJ_PATH.'inc/backend.php';
include_once WGOJNJ_PATH.'inc/actions.php';
include_once WGOJNJ_PATH.'inc/filters.php';

function wpdocs_selectively_enqueue_admin_script($hook)
{
    wp_enqueue_style('wgojnj_custom_style', plugin_dir_url(__FILE__).'lib/wgojnj.css', false, '1.0.1');
    wp_enqueue_style('wgojnj_chart_style', plugin_dir_url(__FILE__).'lib/Chart.min.css', false, '1');
    wp_enqueue_script('wgojnj_custom_script', plugin_dir_url(__FILE__).'lib/wgojnj.js', [], '1.0.1');
    wp_enqueue_script('wgojnj_chart_script', plugin_dir_url(__FILE__).'lib/Chart.min.js', [], '1');
}
add_action('admin_enqueue_scripts', 'wpdocs_selectively_enqueue_admin_script');

function wgojnj_current_remote_ips()
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'].'-'.$_SERVER['HTTP_CLIENT_IP'].'-'.$_SERVER['REMOTE_ADDR'];
}

function wgojnj_print_countries($remote_ips, $reader)
{
    $remote_ip_array = explode('-', $remote_ips);
    $remote_country_array = [];
    foreach ($remote_ip_array as $key => $remote_ip) {
        try {
            $record = $reader->city($remote_ip);
            $remote_country_array[] = $record->country->isoCode.'::'.$record->country->name;
        } catch (\Throwable $th) {
        }
    }

    echo implode('-', $remote_country_array);
}

function wgojnj_remove_older_than_a_week_data(){
    global $wpdb;

    $days_to_store = get_option('wgojnj_days_to_store');

    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
        ."WHERE time < '".date('Y-m-d H:i:s', strtotime(date().' -'.$days_to_store.' day'))."';";
    $results = $wpdb->get_results($sql);
    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
        ."WHERE time < '".date('Y-m-d H:i:s', strtotime(date().' -'.$days_to_store.' day'))."';";
    $results = $wpdb->get_results($sql);
    $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
        ."WHERE time < '".date('Y-m-d H:i:s', strtotime(date().' -'.$days_to_store.' day'))."';";
    $results = $wpdb->get_results($sql);
}

/*
 * Clean records older than one week..
 */
function wgojnj_cron_remove_old_data()
{
    wgojnj_remove_older_than_a_week_data();
}
add_action('wgojnj_cron_remove_old_data_hook', 'wgojnj_cron_remove_old_data');
if (!wp_next_scheduled('wgojnj_cron_remove_old_data_hook')) {
    wp_schedule_event(time(), 'hourly', 'wgojnj_cron_remove_old_data_hook');
}
