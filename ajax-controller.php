<?php

defined('ABSPATH') or die('No no no');

class WhatsGoingOnAjaxController
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
        add_action('wp_ajax_wgo_all_ips_and_counters', [$this, 'wgo_all_ips_and_counters']);
        add_action('wp_ajax_wgo_all_ips_404s', [$this, 'wgo_all_ips_404s']);
        add_action('wp_ajax_wgo_all_urls_404s', [$this, 'wgo_all_urls_404s']);
        add_action('wp_ajax_wgo_all_blocks', [$this, 'wgo_all_blocks']);
        add_action('wp_ajax_wgo_show_payloads', [$this, 'wgo_show_payloads']);
    }

    public function wgo_all_ips_and_counters()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGOJNJ_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        $sql_most_visited_from = 'SELECT count(*) as times, remote_ip, country_code '
        .'FROM '.$wpdb->prefix.'whats_going_on '
        .'GROUP BY remote_ip ORDER BY times DESC';
        $results = $wpdb->get_results($sql_most_visited_from);

        // Paints the view..
        include WGOJNJ_PATH.'view/ajax-most-visited-from.php';

        wp_die();
    }

    public function wgo_all_ips_404s()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGOJNJ_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for 404s..
        $sql_404s = 'SELECT count(*) as times, remote_ip, country_code '
            .'FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC';
        $results = $wpdb->get_results($sql_404s);
        $sql_ips_doing_404s = 'SELECT count(DISTINCT remote_ip) FROM '.$wpdb->prefix.'whats_going_on_404s;';
        $total_ips_doing_404s = $wpdb->get_var($sql_ips_doing_404s);

        // Paints the view..
        include WGOJNJ_PATH.'view/ajax-last-ips-doing-404s.php';

        wp_die();
    }

    public function wgo_all_urls_404s()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGOJNJ_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for 404s..
        $sql_urls = 'SELECT count(*) as times, url, country_code FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY url ORDER BY times DESC';
        $results = $wpdb->get_results($sql_urls);
        $sql_urls_doing_404s = 'SELECT count(DISTINCT url) FROM '.$wpdb->prefix.'whats_going_on_404s;';
        $total_urls_doing_404s = $wpdb->get_var($sql_urls_doing_404s);

        // Paints the view..
        include WGOJNJ_PATH.'view/ajax-last-urls-doing-404s.php';

        wp_die();
    }

    public function wgo_all_blocks()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGOJNJ_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for blocks..
        $block_sql = 'SELECT * '
            .' FROM '.$wpdb->prefix.'whats_going_on_block wgob'
            .' ORDER BY time DESC';
        $results = $wpdb->get_results($block_sql);

        // Total blocks
        $total_blocks = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on_block');

        // Paints the view..
        include WGOJNJ_PATH.'view/ajax-last-blocks.php';

        wp_die();
    }

    public function wgo_show_payloads()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        if (file_exists(WGOJNJ_PATH.'waf-payloads.log')) {
            $waf_payloads_log = file_get_contents(WGOJNJ_PATH.'waf-payloads.log');
            $waf_payloads_log = str_replace(PHP_EOL, '<br>', $waf_payloads_log);
        } else {
            $waf_payloads_log = 'EMPTY FILE';
        }

        echo $waf_payloads_log;

        wp_die();
    }
}
