<?php

defined('ABSPATH') or exit('No no no');

include_once WGO_PATH.'lib/geoip2.phar';
use GeoIp2\Database\Reader;

class WhatsGoingOnCronjobs
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
        // Add some new cron schedules..
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);

        // Job for checking installation of WAF..
        add_action('wgo_cron_check_waf_install', [$this, 'check_waf_install']);
        if (!wp_next_scheduled('wgo_cron_check_waf_install')) {
            wp_schedule_event(time(), 'hourly', 'wgo_cron_check_waf_install');
        }

        // Job remove old records from DB..
        add_action('wgo_cron_remove_old_data_hook', [$this, 'remove_old_data']);
        if (!wp_next_scheduled('wgo_cron_remove_old_data_hook')) {
            wp_schedule_event(time(), 'hourly', 'wgo_cron_remove_old_data_hook');
        }

        // Job fill countries data of IPs in background..
        add_action('wgo_cron_fill_country_columns_hook', [$this, 'fill_country_columns']);
        if (!wp_next_scheduled('wgo_cron_fill_country_columns_hook')) {
            wp_schedule_event(time(), 'minutely', 'wgo_cron_fill_country_columns_hook');
        }

        // Job notify by email for DDoS detections..
        add_action('wgo_cron_notify_ddos_hook', [$this, 'notify_ddos']);
        if (!wp_next_scheduled('wgo_cron_notify_ddos_hook')) {
            wp_schedule_event(time(), 'half-hour', 'wgo_cron_notify_ddos_hook');
        }
    }

    public function check_waf_install()
    {
        echo 'WAF is installed: '.WhatsGoingOn::get_instance()->is_waf_installed().PHP_EOL;

        if (get_option('wgo_waf_installed')) {
            WhatsGoingOn::get_instance()->install_waf();
            echo '..reinstalling subdirectories and main file!'.PHP_EOL;
        }

        WhatsGoingOn::get_instance()->copy_main_waf_file();
        echo 'Overwrite waf-going-on.php file to update it..'.PHP_EOL;
    }

    public function add_cron_intervals($schedules)
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

    /**
     * Cronjob for cleanning records older than x days..
     */
    public function remove_older_than_x_days($days = 0)
    {
        global $wpdb;

        if (empty($days)) {
            $days = get_option('wgo_days_to_store');
        }

        foreach (WhatsGoingOnDatabase::get_instance()->get_table_names() as $tableName) {
            $sql = 'DELETE FROM '.$wpdb->prefix.$tableName
               .' WHERE time < NOW() - INTERVAL '.$days.' DAY;';
            $wpdb->get_results($sql);
        }
    }

    /**
     * Cronjob for removing old data..
     */
    public function remove_old_data()
    {
        $this->remove_older_than_x_days();
    }

    /**
     * Cronjob for filling country columns..
     */
    public function fill_country_columns()
    {
        echo 'Filling countries..'.PHP_EOL;

        global $wpdb;
        $reader = new Reader(WGO_PATH.'lib/GeoLite2-Country.mmdb');
        $im_behind_proxy = get_option('wgo_im_behind_proxy');

        foreach (WhatsGoingOnDatabase::get_instance()->get_table_names() as $tableName) {
            $sql = 'SELECT * FROM '.$wpdb->prefix.$tableName.' WHERE country_code IS NULL ORDER BY rand() DESC LIMIT 100;';
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
                    $record = $reader->country($ip);

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

    /**
     * Cronjob for notifying DDoS detections..
     */
    public function notify_ddos()
    {
        global $wpdb;

        $notify_requests_more_than_sd = get_option('wgo_notify_requests_more_than_sd');
        $notify_requests_more_than_2sd = get_option('wgo_notify_requests_more_than_2sd');
        $notify_requests_more_than_3sd = get_option('wgo_notify_requests_more_than_3sd');
        $notify_requests_less_than_x_percent = get_option('wgo_notify_requests_less_than_x_percent');
        $notification_email = get_option('wgo_notification_email');

        if (!empty($notification_email) and (
        $notify_requests_more_than_sd
        or $notify_requests_more_than_2sd
        or $notify_requests_more_than_3sd)) {
            $chart_sql = 'SELECT count(*) hits FROM '.$wpdb->prefix.'whats_going_on wgo'
                .' GROUP BY year(wgo.time), month(wgo.time), day(wgo.time), hour(wgo.time)';
            $chart_results = $wpdb->get_results($chart_sql);
            //var_dump($chart_results);

            // Apply mathematics..
            $average = 0;
            $standard_deviation = 0;
            if (count($chart_results) > 0) {
                foreach ($chart_results as $key => $item) {
                    $average += $item->hits;
                }
                $average = $average / count($chart_results);
                foreach ($chart_results as $key => $item) {
                    $standard_deviation += pow(($item->hits - $average), 2);
                }
                $standard_deviation = sqrt($standard_deviation / count($chart_results));
            }

            // Last hits..
            $chart_sql = 'SELECT count(*) hits FROM '.$wpdb->prefix.'whats_going_on wgo'
                .' WHERE time > NOW() - INTERVAL 1 HOUR;';
            $chart_results = $wpdb->get_results($chart_sql);
            $last_hits = $chart_results[0]->hits;

            echo 'Last hits: '.$last_hits.PHP_EOL
                .'Average: '.$average.PHP_EOL
                .'Standard Deviation: '.$standard_deviation.PHP_EOL;

            if ($last_hits >= 0) {
                if (($notify_requests_more_than_3sd and $last_hits > $average + 3 * $standard_deviation)
                or ($notify_requests_more_than_3sd and $last_hits < $average - 3 * $standard_deviation)) {
                    echo 'Notify 3SD..'.PHP_EOL;
                    wp_mail(
                        $notification_email,
                        get_bloginfo('name').': What\'s going on: DDoS notification A±3SD',
                        'Requests have reached Average ± 3 * Standard Deviation: A='.$average.', SD='.$standard_deviation.', LastHits='.$last_hits
                    );
                } else {
                    echo 'NOT Notify 3SD..'.PHP_EOL;
                }

                if (($notify_requests_more_than_2sd and $last_hits > $average + 2 * $standard_deviation)
                or ($notify_requests_more_than_2sd and $last_hits < $average - 2 * $standard_deviation)) {
                    echo 'Notify 2SD..'.PHP_EOL;
                    wp_mail(
                        $notification_email,
                        get_bloginfo('name').': What\'s going on: DDoS notification A±2SD',
                        'Requests have reached Average ± 2 * Standard Deviation: A='.$average.', SD='.$standard_deviation.', LastHits='.$last_hits
                    );
                } else {
                    echo 'NOT Notify 2SD..'.PHP_EOL;
                }

                if (($notify_requests_more_than_sd and $last_hits > $average + $standard_deviation)
                or ($notify_requests_more_than_sd and $last_hits < $average - $standard_deviation)) {
                    echo 'Notify SD..'.PHP_EOL;
                    wp_mail(
                        $notification_email,
                        get_bloginfo('name').': What\'s going on: DDoS notification A±SD',
                        'Requests have reached Average ± Standard Deviation: A='.$average.', SD='.$standard_deviation.', LastHits='.$last_hits
                    );
                } else {
                    echo 'NOT Notify SD..'.PHP_EOL;
                }

                if ($last_hits < $average * ($notify_requests_less_than_x_percent / 100)) {
                    echo 'Notify '.$notify_requests_less_than_x_percent.'%A..'.PHP_EOL;
                    wp_mail(
                        $notification_email,
                        get_bloginfo('name').': What\'s going on: DDoS notification '.$notify_requests_less_than_x_percent.'%A',
                        'Requests have reached less than '.$notify_requests_less_than_x_percent.'% of the average: A='.$average.', SD='.$standard_deviation.', LastHits='.$last_hits
                    );
                } else {
                    echo 'NOT Notify '.$notify_requests_less_than_x_percent.'%A..'.PHP_EOL;
                }
            }
        }
    }

    public function process_ban_rules($debug = false)
    {
        global $wpdb;

        if ($debug) {
            $start_time = microtime(true);
            echo 'Processing ban rules..'.PHP_EOL;
        }

        // Getting distinct IPs..
        $distinct_ips = [];
        $result = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'whats_going_on');
        foreach ($result as $item) {
            if (isset($distinct_ips[$item->remote_ip])) {
                ++$distinct_ips[$item->remote_ip];
            } else {
                $distinct_ips[$item->remote_ip] = 1;
            }
        }
        $result = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'whats_going_on_404s');
        foreach ($result as $item) {
            if (isset($distinct_ips[$item->remote_ip])) {
                ++$distinct_ips[$item->remote_ip];
            } else {
                $distinct_ips[$item->remote_ip] = 1;
            }
        }
        $result = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'whats_going_on_block');
        foreach ($result as $item) {
            if (isset($distinct_ips[$item->remote_ip])) {
                ++$distinct_ips[$item->remote_ip];
            } else {
                $distinct_ips[$item->remote_ip] = 1;
            }
        }

        // Order by number of IPs found desc..
        arsort($distinct_ips);

        // Prepare variables..
        $array_total_requests = [];
        $array_total_404s = [];
        $array_max_requests_per_minute_achieved = [];
        $array_max_requests_per_hour_achieved = [];
        $array_total_regex_for_payload_blocks = [];
        $array_total_regex_for_query_string_blocks = [];
        $result = $wpdb->get_results('SELECT remote_ip, count(*) total FROM '.$wpdb->prefix.'whats_going_on GROUP BY remote_ip;');
        foreach ($result as $item) {
            $array_total_requests[$item->remote_ip] = $item->total;
        }
        $result = $wpdb->get_results('SELECT remote_ip, count(*) total FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip;');
        foreach ($result as $item) {
            $array_total_404s[$item->remote_ip] = $item->total;
        }
        $result = $wpdb->get_results('SELECT remote_ip, max(last_minute) total FROM '.$wpdb->prefix.'whats_going_on GROUP BY remote_ip;');
        foreach ($result as $item) {
            $array_max_requests_per_minute_achieved[$item->remote_ip] = $item->total;
        }
        $result = $wpdb->get_results('SELECT remote_ip, max(last_hour) total FROM '.$wpdb->prefix.'whats_going_on GROUP BY remote_ip;');
        foreach ($result as $item) {
            $array_max_requests_per_hour_achieved[$item->remote_ip] = $item->total;
        }
        $result = $wpdb->get_results('SELECT remote_ip, count(*) total FROM '.$wpdb->prefix."whats_going_on_block WHERE comments LIKE '%Regex for payload%' GROUP BY remote_ip;");
        foreach ($result as $item) {
            $array_total_regex_for_payload_blocks[$item->remote_ip] = $item->total;
        }
        $result = $wpdb->get_results('SELECT remote_ip, count(*) total FROM '.$wpdb->prefix."whats_going_on_block WHERE comments LIKE '%Regex for query string%' GROUP BY remote_ip;");
        foreach ($result as $item) {
            $array_total_regex_for_query_string_blocks[$item->remote_ip] = $item->total;
        }

        // Load the rules..
        $file_path = wp_upload_dir()['basedir'].'/wgo-things/ban-rules.php';
        $array_rules = [];
        if (file_exists($file_path)) {
            $the_file = file($file_path);

            if (count($the_file) > 1) {
                for ($i = 1; $i < count($the_file); ++$i) {
                    $array_rules[] = [
                        'criteria' => trim(explode('=>', $the_file[$i])[0]),
                        'seconds_to_ban' => trim(explode('=>', $the_file[$i])[1]),
                    ];
                }
            }
        }

        // Process each IP, adding to the ban list if necessary..
        $total_processed = 0;
        $ips_to_block = [];
        foreach ($distinct_ips as $ip => $total) {
            $totalRequests = intval($array_total_requests[$ip]);
            $total404s = intval($array_total_404s[$ip]);
            $maxRequestsPerMinuteAchieved = intval($array_max_requests_per_minute_achieved[$ip]);
            $maxRequestsPerHourAchieved = intval($array_max_requests_per_hour_achieved[$ip]);
            $totalRegexForPayloadBlocks = intval($array_total_regex_for_payload_blocks[$ip]);
            $totalRegexForQueryStringBlocks = intval($array_total_regex_for_query_string_blocks[$ip]);

            if ($debug) {
                echo 'IP: '.$ip.PHP_EOL
                    .'Total requests: '.$totalRequests.' == '.$total.PHP_EOL
                    .'Total 404s: '.$total404s.PHP_EOL
                    .'Max requests per minute achieved: '.$maxRequestsPerMinuteAchieved.PHP_EOL
                    .'Max requests per hour achieved: '.$maxRequestsPerHourAchieved.PHP_EOL
                    .'Total regex for payload blocks: '.$totalRegexForPayloadBlocks.PHP_EOL
                    .'Total regex for query string blocks: '.$totalRegexForQueryStringBlocks.PHP_EOL;
            }

            foreach ($array_rules as $rule) {
                $matchesTheCriteria = eval('return '.$rule['criteria'].';');
                if ($debug) {
                    echo 'Matches the criteria ? '.$rule['criteria'].' '.($matchesTheCriteria ? 'true' : 'false').PHP_EOL;
                }
                if ($matchesTheCriteria) {
                    $ips_to_block[$ip] = $rule['seconds_to_ban'];
                    break;
                }
            }

            if ($debug) {
                echo PHP_EOL;
            }

            ++$total_processed;
        }
        var_dump($ips_to_block);

        // Add to the block list if necessary..

        // Add to the bans into DB if necessary..
        $result_bans = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'whats_going_on_bans');

        // Remove from the block list if necessary..


        if ($debug) {
            echo 'Total distinct IPs: '.count($distinct_ips).PHP_EOL
                .'Total IPs processed: '.$total_processed.PHP_EOL
                .'Total processing time: '.(microtime(true) - $start_time).'secs'.PHP_EOL;
        }
    }
}
