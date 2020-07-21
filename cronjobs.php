<?php

defined('ABSPATH') or die('No no no');

include_once WGOJNJ_PATH.'lib/geoip2.phar';
use GeoIp2\Database\Reader;

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

                if (!empty($record->country->isoCode)) {
                    // Update records..
                    $sql = 'UPDATE '.$wpdb->prefix.$tableName.' SET country_code = \''.$record->country->isoCode.'\' WHERE remote_ip = \''.$result->remote_ip.'\';';
                    $wpdb->get_results($sql);
                    echo 'Saving '.$result->remote_ip.' '.$record->country->isoCode;
                } else {
                    echo 'Not saving empty ISO country code.';
                }
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
