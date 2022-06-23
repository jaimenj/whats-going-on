<?php

defined('ABSPATH') or exit('No no no');

class WhatsGoingOnIaBanRules
{
    private static $instance;
    private $debug;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function process_ban_rules($debug = false)
    {
        global $wpdb;
        $this->debug = $debug;

        if ($this->debug) {
            $start_time = microtime(true);
            echo 'Processing ban rules..'.PHP_EOL;
        }

        $distinct_ips = $this->_get_distinct_ips();

        // Prepare variables..
        $array_total_404s = [];
        $array_max_requests_per_minute_achieved = [];
        $array_max_requests_per_hour_achieved = [];
        $array_total_regex_for_payload_blocks = [];
        $array_total_regex_for_query_string_blocks = [];
        $result = $wpdb->get_results('SELECT remote_ip, count(*) total FROM '.$wpdb->prefix.'whats_going_on WHERE is_404 = true GROUP BY remote_ip;');
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

        $array_rules = $this->_load_the_ban_rules();

        // Process each IP, adding to the ban list if necessary..
        $total_processed = 0;
        $ips_to_block = [];
        foreach ($distinct_ips as $ip => $total) {
            $totalRequests = intval($total);
            $total404s = isset($array_total_404s[$ip]) ?
                intval($array_total_404s[$ip]) : 0;
            $maxRequestsPerMinuteAchieved = isset($array_max_requests_per_minute_achieved[$ip]) ?
                intval($array_max_requests_per_minute_achieved[$ip]) : 0;
            $maxRequestsPerHourAchieved = isset($array_max_requests_per_hour_achieved[$ip]) ?
                intval($array_max_requests_per_hour_achieved[$ip]) : 0;
            $totalRegexForPayloadBlocks = isset($array_total_regex_for_payload_blocks[$ip]) ?
                intval($array_total_regex_for_payload_blocks[$ip]) : 0;
            $totalRegexForQueryStringBlocks = isset($array_total_regex_for_query_string_blocks[$ip]) ?
                intval($array_total_regex_for_query_string_blocks[$ip]) : 0;

            if ($this->debug) {
                echo '==>'.PHP_EOL
                    .'IP: '.$ip.PHP_EOL
                    .'Total requests: '.$totalRequests.PHP_EOL
                    .'Total 404s: '.$total404s.PHP_EOL
                    .'Max requests per minute achieved: '.$maxRequestsPerMinuteAchieved.PHP_EOL
                    .'Max requests per hour achieved: '.$maxRequestsPerHourAchieved.PHP_EOL
                    .'Total regex for payload blocks: '.$totalRegexForPayloadBlocks.PHP_EOL
                    .'Total regex for query string blocks: '.$totalRegexForQueryStringBlocks.PHP_EOL;
            }

            foreach ($array_rules as $key => $rule) {
                $matchesTheCriteria = eval('return '.$rule['criteria'].';');
                if ($this->debug) {
                    echo $key.' ? '.$rule['criteria'].' '.($matchesTheCriteria ? 'true' : 'false').PHP_EOL;
                }
                if ($matchesTheCriteria) {
                    $ips_to_block[$ip] = [
                        'days' => $rule['seconds_to_ban'],
                        'comments' => 'Ban rule '.($key + 1),
                    ];
                    break;
                }
            }

            ++$total_processed;
        }

        $block_list = $this->_add_to_the_block_list($ips_to_block);

        $this->_add_update_or_remove_the_bans($ips_to_block, $block_list);

        // Save new block list..
        //var_dump($ips_to_block);
        //var_dump($block_list);
        file_put_contents(wp_upload_dir()['basedir'].'/wgo-things/block-list.php', $block_list);

        if ($this->debug) {
            echo 'Total distinct IPs: '.count($distinct_ips).PHP_EOL
                .'Total IPs processed: '.$total_processed.PHP_EOL
                .'Total processing time: '.(microtime(true) - $start_time).'secs'.PHP_EOL;
        }
    }

    private function _get_distinct_ips()
    {
        global $wpdb;

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

        // Order by number of IPs found desc..
        arsort($distinct_ips);

        return $distinct_ips;
    }

    private function _load_the_ban_rules()
    {
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

        return $array_rules;
    }

    private function _add_to_the_block_list($ips_to_block)
    {
        // Add to the block list if necessary..
        $file_path = wp_upload_dir()['basedir'].'/wgo-things/block-list.php';
        $block_list = [];
        $block_list[] = '<?php/*'.PHP_EOL;
        if (file_exists($file_path)) {
            $the_file = file($file_path);
            if (count($the_file) > 1) {
                for ($i = 1; $i < count($the_file); ++$i) {
                    $block_list[] = $the_file[$i];
                }
            }
        }
        foreach ($ips_to_block as $ip_to_block => $value) {
            if (!in_array($ip_to_block.PHP_EOL, $block_list)) {
                $block_list[] = $ip_to_block.PHP_EOL;
            }
        }

        return $block_list;
    }

    private function _add_update_or_remove_the_bans($ips_to_block, &$block_list)
    {
        global $wpdb;

        $result_bans = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'whats_going_on_bans');

        // Update..
        foreach ($result_bans as $ban) {
            if (isset($ips_to_block[$ban->remote_ip])) {
                $sql_update = 'UPDATE '.$wpdb->prefix.'whats_going_on_bans '
                    .'SET time = now(), '
                    .'time_until = now() + INTERVAL '.$ips_to_block[$ban->remote_ip]['days'].' DAY, '
                    ."comments = '".$ips_to_block[$ban->remote_ip]['comments']."' "
                    ."WHERE remote_ip = '".$ban->remote_ip."';";
                if ($this->debug) {
                    echo $sql_update.PHP_EOL;
                }
                $wpdb->get_results($sql_update);
                unset($ips_to_block[$ban->remote_ip]);
            }
        }

        // Add..
        foreach ($ips_to_block as $ip => $value) {
            $sql_insert = 'INSERT INTO '.$wpdb->prefix.'whats_going_on_bans (time, time_until, remote_ip, comments) '
                .'VALUES (now(), now() + INTERVAL '.$value['days']." DAY, '".$ip."', '".$value['comments']."');";
            if ($this->debug) {
                echo $sql_insert.PHP_EOL;
            }
            $wpdb->get_results($sql_insert);
        }

        // Remove..
        $result_bans_to_remove = $wpdb->get_results(
            'SELECT * FROM '.$wpdb->prefix.'whats_going_on_bans '
            .'WHERE time_until < now()'
        );
        foreach ($result_bans_to_remove as $ban) {
            $sql_remove = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_bans '
                ."WHERE remote_ip = '".$ban->remote_ip."';";
            if ($this->debug) {
                echo 'Ban to remove: '.$ban->remote_ip.PHP_EOL.$sql_remove.PHP_EOL;
            }
            $wpdb->get_results($sql_remove);

            foreach ($block_list as $key => $value) {
                if (false !== strpos($value, $ban->remote_ip)) {
                    unset($block_list[$key]);
                }
            }
        }
    }
}
