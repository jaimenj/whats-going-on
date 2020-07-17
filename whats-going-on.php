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
include_once WGOJNJ_PATH.'lib/geoip2.phar';

use GeoIp2\Database\Reader;

/**
 * It adds assets only for the backend..
 */
function wpdocs_selectively_enqueue_admin_script($hook)
{
    wp_enqueue_style('wgojnj_custom_style', plugin_dir_url(__FILE__).'lib/wgojnj.css', false, '1.0.1');
    wp_enqueue_style('wgojnj_chart_style', plugin_dir_url(__FILE__).'lib/Chart.min.css', false, '1');
    wp_enqueue_script('wgojnj_custom_script', plugin_dir_url(__FILE__).'lib/wgojnj.js', [], '1.0.1');
    wp_enqueue_script('wgojnj_chart_script', plugin_dir_url(__FILE__).'lib/Chart.min.js', [], '1');
}
add_action('admin_enqueue_scripts', 'wpdocs_selectively_enqueue_admin_script');

/**
 * It simply returns HTTP_X_FORWARDED_FOR - HTTP_CLIENT_IP - REMOTE_ADDR..
 */
function wgojnj_current_remote_ips()
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'].'-'.$_SERVER['HTTP_CLIENT_IP'].'-'.$_SERVER['REMOTE_ADDR'];
}

/**
 * Clean records older than x days..
 */
function wgojnj_remove_older_than_x_days()
{
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

/**
 * Cronjob to remove old data..
 */
function wgojnj_cron_remove_old_data()
{
    wgojnj_remove_older_than_x_days();
}
add_action('wgojnj_cron_remove_old_data_hook', 'wgojnj_cron_remove_old_data');
if (!wp_next_scheduled('wgojnj_cron_remove_old_data_hook')) {
    wp_schedule_event(time(), 'hourly', 'wgojnj_cron_remove_old_data_hook');
}

/*
 * Fill country columns..
 */
function wgojnj_add_cron_intervals($schedules)
{
    $schedules['minutely'] = [
        'interval' => 60,
        'display' => esc_html__('Every Minute'), ];
    $schedules['five-minutes'] = [
        'interval' => 300,
        'display' => esc_html__('Every 5 Minutes'), ];
    $schedules['ten-minutes'] = [
        'interval' => 600,
        'display' => esc_html__('Every 10 Minutes'), ];
    $schedules['half-hour'] = [
        'interval' => 1800,
        'display' => esc_html__('Half Hour'), ];

    return $schedules;
}
add_filter('cron_schedules', 'wgojnj_add_cron_intervals');
function wgojnj_cron_fill_country_columns()
{
    echo 'Filling countries..'.PHP_EOL;

    global $wpdb;
    $reader = new Reader(WGOJNJ_PATH.'lib/GeoLite2-City.mmdb');
    $im_behind_proxy = get_option('wgojnj_im_behind_proxy');

    $tableNames = ['whats_going_on', 'whats_going_on_block', 'whats_going_on_404s'];

    foreach ($tableNames as $tableName) {
        $sql = 'SELECT * FROM '.$wpdb->prefix.$tableName.' WHERE country_code IS NULL LIMIT 100;';
        $results = $wpdb->get_results($sql);
        foreach ($results as $result) {
            echo $result->remote_ip.'.. ';
            $ips = explode('-', $result->remote_ip);

            if ($im_behind_proxy) {
                $ip = $ips[0];
            } else {
                $ip = $ips[2];
            }

            try {
                $record = $reader->city($ip);

                // Update records..
                $sql = 'UPDATE '.$wpdb->prefix.$tableName.' SET country_code = \''.$record->country->isoCode.'\' WHERE remote_ip = \''.$result->remote_ip.'\';';
                $wpdb->get_results($sql);
                echo 'Saving '.$result->remote_ip.' '.$record->country->isoCode;
            } catch (\Throwable $th) {
            }
            echo PHP_EOL;
        }
    }
}
add_action('wgojnj_cron_fill_country_columns_hook', 'wgojnj_cron_fill_country_columns');
if (!wp_next_scheduled('wgojnj_cron_fill_country_columns_hook')) {
    wp_schedule_event(time(), 'minutely', 'wgojnj_cron_fill_country_columns_hook');
}
