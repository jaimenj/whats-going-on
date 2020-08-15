<?php

include_once __DIR__.'/wp-content/plugins/whats-going-on/lib/geoip2.phar';
use GeoIp2\Database\Reader;

class WafGoingOn
{
    private static $instance;

    private $debug = false;
    private $url;
    private $regexes_errors_strings;
    private $allow_list_file_path;
    private $block_list_file_path;
    private $block_regexes_uri_file_path;
    private $block_regexes_payload_file_path;
    private $block_countries;
    private $retry_time;
    private $wp_options;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // Debug or not debug
        if ($this->debug) {
            $time_start = microtime(true);
        }

        // Define some variables
        $this->url = urlencode(substr(
            $_SERVER['REQUEST_SCHEME'].'://'
            .$_SERVER['SERVER_NAME']
            .(!in_array($_SERVER['SERVER_PORT'], [80, 443]) ? ':'.$_SERVER['SERVER_PORT'] : '')
            .$_SERVER['REQUEST_URI'], 0, 255));
        $this->regexes_errors_strings = [
            0 => 'PREG_NO_ERROR',
            1 => 'PREG_INTERNAL_ERROR',
            2 => 'PREG_BACKTRACK_LIMIT_ERROR',
            3 => 'PREG_RECURSION_LIMIT_ERROR',
            4 => 'PREG_BAD_UTF8_ERROR',
            5 => 'PREG_BAD_UTF8_OFFSET_ERROR',
            6 => 'PREG_JIT_STACKLIMIT_ERROR',
        ];
        $this->block_list_file_path = __DIR__.'/wp-content/uploads/wgo-things/block-list.php';
        $this->allow_list_file_path = __DIR__.'/wp-content/uploads/wgo-things/allow-list.php';
        $this->block_regexes_uri_file_path = __DIR__.'/wp-content/uploads/wgo-things/block-regexes-uri.php';
        $this->block_regexes_payload_file_path = __DIR__.'/wp-content/uploads/wgo-things/block-regexes-payload.php';
        $this->block_countries = __DIR__.'/wp-content/uploads/wgo-things/block-countries.php';
        $this->payloads_file_path = __DIR__.'/wp-content/uploads/wgo-things/waf-payloads.log';

        // The main things of the WAF
        $this->_main();

        // Debugging or not
        if ($this->debug) {
            $time_end = microtime(true);
            $time_consumed = $time_end - $time_start;
            echo 'Time consumed: '.number_format($time_consumed, 9).' secs<br><br>';
            die('Die, we are debugging..');
        }
    }

    private function _main()
    {
        $this->_load_configs($configs_array);

        // Connect to database
        $mysql_connection = new mysqli(
            $configs_array['DB_HOST'],
            $configs_array['DB_USER'],
            $configs_array['DB_PASSWORD']
        );
        $the_table_full_prefix = $configs_array['DB_NAME'].'.'.$configs_array['TABLE_PREFIX'];
        if ($mysql_connection->connect_error) {
            die('Connection failed: '.$mysql_connection->connect_error);
        }

        $this->_get_options($mysql_connection, $the_table_full_prefix);

        $requests_last_minute = $this->_get_requests_per_minutes(1, $mysql_connection, $the_table_full_prefix);
        $requests_last_hour = $this->_get_requests_per_minutes(60, $mysql_connection, $the_table_full_prefix);
        if ($this->debug) {
            echo 'requests_last_minute='.$requests_last_minute.'<br>'
                .'requests_last_hour='.$requests_last_hour.'<br>'
                .'<br>';
        }

        $this->_save_my_request($mysql_connection, $requests_last_minute, $requests_last_hour, $the_table_full_prefix);

        // To block or not to block, that's the matter..
        $comments = '';
        $this->retry_time = 1;

        // If it achieves max requests per minute..
        if ($this->wp_options['limit_requests_per_minute'] > 0 and $requests_last_minute > $this->wp_options['limit_requests_per_minute']) {
            $comments .= 'Reached max requests per minute: '.$this->wp_options['limit_requests_per_minute'].' ';
            $this->retry_time = 60;
        }

        // If it achieves max requests per hour..
        if ($this->wp_options['limit_requests_per_hour'] > 0 and $requests_last_hour > $this->wp_options['limit_requests_per_hour']) {
            $comments .= 'Reached max requests per hour: '.$this->wp_options['limit_requests_per_hour'].' ';
            $this->retry_time = 3600;
        }

        // Regexes for IPs, URIs and payloads..
        $regexes_errors_file = __DIR__.'/wp-content/uploads/wgo-things/waf-errors.log';
        $regexes_errors = file($regexes_errors_file);
        $this->_check_block_list($comments, $regexes_errors);
        $uri_matches = $this->_check_regexes_uri($comments, $regexes_errors);
        $payload_matches = $this->_check_regexes_payload($comments, $regexes_errors);
        if ('POST' === $_SERVER['REQUEST_METHOD'] and (
            ($this->wp_options['save_payloads'] and $this->wp_options['save_payloads_matching_uri_regex'] and $uri_matches)
            or ($this->wp_options['save_payloads'] and $this->wp_options['save_payloads_matching_payload_regex'] and $payload_matches)
        )) {
            $this->_save_payloads();
        }

        // Debug..
        if ($this->debug) {
            echo 'Regex errors:<br>';
            foreach ($regexes_errors as $item) {
                echo $item.'<br>';
            }
            echo '<br>';
        }

        // Save Regexes errors to review..
        file_put_contents($regexes_errors_file, array_unique($regexes_errors));

        $this->_check_countries($comments, $regexes_errors);

        // If we are blocking..
        if (!empty($comments)) {
            $bypassed = $this->_check_allow_list($comments, $regexes_errors);

            // Debug..
            if ($this->debug) {
                echo 'Regex errors:<br>';
                foreach ($regexes_errors as $item) {
                    echo $item.'<br>';
                }
                echo '<br>';
            }

            $this->_save_the_blocking($mysql_connection, $comments, $the_table_full_prefix);
            file_put_contents($regexes_errors_file, array_unique($regexes_errors));

            if (!$bypassed) {
                header('HTTP/1.1 429 Too Many Requests');
                header('Retry-After: '.$this->retry_time);
                die('You are not allowed to access this file.');
            }
        }

        // Close database connection
        mysqli_close($mysql_connection);
    }

    private function _load_configs(&$configs_array)
    {
        // Loading configs from wp-config.php file..
        $config_file_path = __DIR__.'/wp-content/uploads/wgo-things/.config';
        $config_file_content = file($config_file_path);
        foreach ($config_file_content as $line) {
            if (preg_match('/(.*)=(.*)/i', $line, $matches)) {
                $configs_array[$matches[1]] = $matches[2];
            }
        }
    }

    private function _check_block_list(&$comments, &$regexes_errors)
    {
        // If it's in the block list..
        if (file_exists($this->block_list_file_path)) {
            $file_content = file($this->block_list_file_path);
            $to_block = false;
            for ($i = 1; $i < count($file_content); ++$i) {
                $ip_regex = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$ip_regex.'<br>';
                }

                if (!empty($ip_regex) and preg_match('/'.$ip_regex.'/i', $this->_current_remote_ips())) {
                    $to_block = true;
                }
                if (PREG_NO_ERROR != preg_last_error()) {
                    $regexes_errors[] = $ip_regex.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                }
                //var_dump(preg_last_error());
            }

            // Debug..
            if ($this->debug) {
                echo '..with '.$this->_current_remote_ips().'<br>'
                    .'=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }

            if ($to_block) {
                $comments .= 'IP in the block-list. ';
                $this->retry_time = 86400;
            }
        }
    }

    private function _check_regexes_uri(&$comments, &$regexes_errors)
    {
        $to_block = false;

        // If hits a regex for query string..
        if (file_exists($this->block_regexes_uri_file_path)) {
            $file_content = file($this->block_regexes_uri_file_path);
            
            $to_block_regex_num = [];
            for ($i = 1; $i < count($file_content); ++$i) {
                $uri_regex = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$uri_regex.'<br>';
                }

                if (!empty($uri_regex)) {
                    // Check query string..
                    if (!empty($_SERVER['REQUEST_URI'])
                    and preg_match($uri_regex, $_SERVER['REQUEST_URI'])) {
                        $to_block = true;
                        $to_block_regex_num[] = $i;
                    }
                    if (PREG_NO_ERROR != preg_last_error()) {
                        $regexes_errors[] = $uri_regex.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                    }
                    //var_dump(preg_last_error());
                }
            }

            // Debug..
            if ($this->debug) {
                echo '..with URI '.$_SERVER['REQUEST_URI'].'<br>=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }

            if ($to_block) {
                $comments .= 'Regex for query string ('.implode('-', $to_block_regex_num).'). ';
                $this->retry_time = 86400;
            }
        }

        return $to_block;
    }

    private function _check_regexes_payload(&$comments, &$regexes_errors)
    {
        $to_block = false;

        // If hits a regex for post data..
        if (file_exists($this->block_regexes_payload_file_path)) {
            $file_content = file($this->block_regexes_payload_file_path);
            $to_block_regex_num = [];
            for ($i = 1; $i < count($file_content); ++$i) {
                $payload_regex = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$payload_regex.'<br>';
                }

                if (!empty($payload_regex)) {
                    // Check post data..
                    foreach ($_POST as $post_value) {
                        $this->_recursive_payload_check($payload_regex, $i, $post_value, $to_block, $to_block_regex_num, $regexes_errors);
                    }
                }
            }

            // Debug..
            if ($this->debug) {
                echo '=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }

            if ($to_block) {
                $comments .= 'Regex for payload ('.implode('-', $to_block_regex_num).'). ';
                $this->retry_time = 86400;
            }
        }

        return $to_block;
    }

    private function _recursive_payload_check($payload_regex, $i, $post_value, &$to_block, &$to_block_regex_num, &$regexes_errors)
    {
        if (is_array($post_value)) {
            foreach ($post_value as $post_sub_value) {
                $this->_recursive_payload_check($payload_regex, $i, $post_sub_value, $to_block, $to_block_regex_num, $regexes_errors);
            }
        } else {
            // Debug..
            if ($this->debug) {
                echo '..with PAYLOAD '.$post_value.'<br>';
            }

            if (preg_match($payload_regex, $post_value)) {
                $to_block = true;
                $to_block_regex_num[] = $i;
            }

            if (PREG_NO_ERROR != preg_last_error()) {
                $regexes_errors[] = $payload_regex.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
            }
        }
    }

    private function _save_payloads()
    {
        file_put_contents($this->payloads_file_path,
            date('Y-m-d H:i:s').' '.$this->_current_remote_ips().PHP_EOL
            .urldecode($this->url).PHP_EOL
            .'================================================================================'.PHP_EOL,
            FILE_APPEND
        );
        $this->_recursive_save_payloads('', $_POST);
        file_put_contents($this->payloads_file_path, '<<<'.PHP_EOL.PHP_EOL, FILE_APPEND);
    }

    private function _recursive_save_payloads($key_append = '', $post_items)
    {
        foreach ($post_items as $post_key => $post_value) {
            if (is_array($post_value)) {
                $this->_recursive_save_payloads($key_append.'['.$post_key.']', $post_value);
            } else {
                file_put_contents($this->payloads_file_path, $key_append.'['.$post_key.']='.$post_value.PHP_EOL, FILE_APPEND);
            }
        }
    }

    private function _check_countries(&$comments, &$regexes_errors)
    {
        if (file_exists($this->block_countries)) {
            $reader = new Reader(__DIR__.'/wp-content/plugins/whats-going-on/lib/GeoLite2-Country.mmdb');
            $request_country = '';
            $to_block = false;

            $blocking_countries = explode(PHP_EOL, file_get_contents($this->block_countries));
            unset($blocking_countries[0]);

            if ($this->wp_options['im_behind_proxy']) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            // Debug..
            if ($this->debug) {
                $ip = gethostbyname('jnjsite.com');
            }

            try {
                $record = $reader->country($ip);
                $request_country = $record->country->isoCode;
            } catch (\Throwable $th) {
            }

            if (in_array($request_country, $blocking_countries)) {
                $comments .= 'Request blocking by country. ';
                $this->retry_time = 86400;
                $to_block = true;
            }

            // Debug..
            if ($this->debug) {
                echo 'request_country='.$request_country.'<br>';
                echo '=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }
        }
    }

    private function _check_allow_list(&$comments, &$regexes_errors)
    {
        $bypassed = false;

        // If it's in the allow list..
        if (file_exists($this->allow_list_file_path)) {
            $file_content = file($this->allow_list_file_path);

            for ($i = 1; $i < count($file_content); ++$i) {
                $ip_regex = trim(str_replace(PHP_EOL, '', $file_content[$i]));
                if (!empty($ip_regex) and preg_match('/'.$ip_regex.'/i', $this->_current_remote_ips())) {
                    $bypassed = true;
                }
                if (PREG_NO_ERROR != preg_last_error()) {
                    $regexes_errors[] = $ip_regex.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                }

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$ip_regex.'<br>';
                }
            }

            // Debug..
            if ($this->debug) {
                echo '..with '.$this->_current_remote_ips().'<br>'
                    .'=> result $bypassed='.($bypassed ? 'true' : 'false').'<br><br>';
            }

            if ($bypassed) {
                $comments .= 'IP in the allow-list. Bypassed..';
            }
        }

        return $bypassed;
    }

    private function _save_the_blocking($mysql_connection, $comments, $the_table_full_prefix)
    {
        $sql = 'INSERT INTO '.$the_table_full_prefix.'whats_going_on_block '
            .'(time, url, remote_ip, remote_port, user_agent, comments) '
            .'VALUES ('
            ."now(), '"
            .$this->url."', '"
            .$this->_current_remote_ips()."', '"
            .$_SERVER['REMOTE_PORT']."', '"
            .(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."','"
            .$comments."'"
            .');';
        $result = mysqli_query($mysql_connection, $sql);
        //var_dump($result);
    }

    private function _save_my_request($mysql_connection, $requests_last_minute, $requests_last_hour, $the_table_full_prefix)
    {
        $sql = 'INSERT INTO '.$the_table_full_prefix.'whats_going_on '
            .'(time, url, remote_ip, remote_port, user_agent, method, last_minute, last_hour) '
            .'VALUES ('
            ."now(), '"
            .$this->url."', '"
            .$this->_current_remote_ips()."', '"
            .$_SERVER['REMOTE_PORT']."', '"
            .(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."','"
            .$_SERVER['REQUEST_METHOD']."', "
            .$requests_last_minute.', '
            .$requests_last_hour
            .');';

        $result = mysqli_query($mysql_connection, $sql);
        //var_dump($result);
    }

    private function _get_requests_per_minutes($minutes, $mysql_connection, $the_table_full_prefix)
    {
        $return_value = -1;

        $sql = 'SELECT count(*) FROM '.$the_table_full_prefix.'whats_going_on '
            ."WHERE remote_ip = '".$this->_current_remote_ips()."' "
            .'AND time > NOW() - INTERVAL '.$minutes.' MINUTE;';
        if ($result = mysqli_query($mysql_connection, $sql)) {
            //var_dump($result);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $return_value = $row[0];
                }
                mysqli_free_result($result);
            }
        }

        return $return_value;
    }

    private function _get_options($mysql_connection, $the_table_full_prefix)
    {
        $options_to_search = [
            'wgo_limit_requests_per_minute',
            'wgo_limit_requests_per_hour',
            'wgo_im_behind_proxy',
            'wgo_save_payloads',
            'wgo_save_payloads_matching_uri_regex',
            'wgo_save_payloads_matching_payload_regex',
        ];

        $sql = 'SELECT option_name, option_value FROM '.$the_table_full_prefix.'options '
            ."WHERE option_name IN ('"
            .implode("', '", $options_to_search)
            ."')";
        if ($result = mysqli_query($mysql_connection, $sql)) {
            //var_dump($result);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $option_name = substr($row['option_name'], strlen('wgo_'), strlen($row['option_name']) - strlen('wgo_'));
                    $this->wp_options[$option_name] = $row['option_value'];
                }
                mysqli_free_result($result);
            }
        }

        if ($this->debug) {
            foreach ($options_to_search as $option_to_search) {
                $option_name = substr($option_to_search, 7, strlen($option_to_search) - 7);
                echo $option_name.'='.$this->wp_options[$option_name].'<br>';
            }
            echo '<br>';
        }
    }

    private function _current_remote_ips()
    {
        return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '').'-'
            .(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').'-'
            .$_SERVER['REMOTE_ADDR'];
    }
}

// Do all..
WafGoingOn::get_instance();
