<?php

class WafGoingOn
{
    private static $instance;

    private $debug = false;
    private $url;
    private $regexes_errors_strings;
    private $block_list_file_path;
    private $allow_list_file_path;
    private $block_regexes_uri_file_path;
    private $block_regexes_payload_file_path;
    private $retry_time;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        // Debug or not debug
        if ($this->debug) {
            $timeStart = microtime(true);
        }

        // Define some variables
        $this->url = substr(
            $_SERVER['REQUEST_SCHEME'].'://'
            .$_SERVER['SERVER_NAME']
            .(80 != $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '')
            .$_SERVER['REQUEST_URI']
        , 0, 255);
        $this->regexes_errors_strings = [
            0 => 'PREG_NO_ERROR',
            1 => 'PREG_INTERNAL_ERROR',
            2 => 'PREG_BACKTRACK_LIMIT_ERROR',
            3 => 'PREG_RECURSION_LIMIT_ERROR',
            4 => 'PREG_BAD_UTF8_ERROR',
            5 => 'PREG_BAD_UTF8_OFFSET_ERROR',
            6 => 'PREG_JIT_STACKLIMIT_ERROR',
        ];
        $this->block_list_file_path = __DIR__.'/block-list.php';
        $this->allow_list_file_path = __DIR__.'/allow-list.php';
        $this->block_regexes_uri_file_path = __DIR__.'/block-regexes-uri.php';
        $this->block_regexes_payload_file_path = __DIR__.'/block-regexes-payload.php';

        // The main things of the WAF
        $this->_main();

        // Debugging or not
        if ($this->debug) {
            $timeEnd = microtime(true);
            $timeConsumed = $timeEnd - $timeStart;
            echo 'Time consumed: '.number_format($timeConsumed, 9).' secs';
            die;
        }
    }

    private function _main()
    {
        $this->_load_configs($configsArray);

        // Connect to database
        $mysqlConnection = new mysqli(
            $configsArray['DB_HOST'],
            $configsArray['DB_USER'],
            $configsArray['DB_PASSWORD']
        );
        $the_table_full_prefix = $configsArray['DB_NAME'].'.'.$configsArray['TABLE_PREFIX'];
        if ($mysqlConnection->connect_error) {
            die('Connection failed: '.$mysqlConnection->connect_error);
        }

        $this->_get_options($mysqlConnection, $the_table_full_prefix, $max_per_minute, $max_per_hour);

        $requests_last_minute = $this->_get_requests_per_minutes(1, $mysqlConnection, $the_table_full_prefix);
        $requests_last_hour = $this->_get_requests_per_minutes(60, $mysqlConnection, $the_table_full_prefix);

        $this->_save_my_request($mysqlConnection, $requests_last_minute, $requests_last_hour, $the_table_full_prefix);

        // To block or not to block, that's the matter..
        $comments = '';
        $this->retry_time = 1;

        // If it achieves max requests per minute..
        if ($max_per_minute > 0 and $requests_last_minute > $max_per_minute) {
            $comments .= 'Reached max requests per minute: '.$max_per_minute.' ';
            $this->retry_time = 60;
        }

        // If it achieves max requests per hour..
        if ($max_per_hour > 0 and $requests_last_hour > $max_per_hour) {
            $comments .= 'Reached max requests per hour: '.$max_per_hour.' ';
            $this->retry_time = 3600;
        }

        // Regexes errors
        $regexesErrorsFile = __DIR__.'/waf-errors.log';
        $regexesErrors = file($regexesErrorsFile);

        //var_dump($regexesErrorsFile);

        $this->_check_block_list($comments, $regexesErrors);
        $this->_check_regexes_uri($comments, $regexesErrors);
        $this->_check_regexes_payload($comments, $regexesErrors);

        // Debug..
        if ($this->debug) {
            echo 'Regex errors:<br>';
            foreach ($regexesErrors as $item) {
                echo $item.'<br>';
            }
            echo '<br>';
        }

        // Save Regexes errors to review..
        file_put_contents($regexesErrorsFile, array_unique($regexesErrors));

        // If we are blocking..
        if (!empty($comments)) {
            $bypassed = $this->_check_allow_list($comments, $regexesErrors);

            // Debug..
            if ($this->debug) {
                echo 'Regex errors:<br>';
                foreach ($regexesErrors as $item) {
                    echo $item.'<br>';
                }
                echo '<br>';
            }

            $this->_save_the_blocking($mysqlConnection, $comments, $the_table_full_prefix);
            file_put_contents($regexesErrorsFile, array_unique($regexesErrors));

            if (!$bypassed) {
                header('HTTP/1.1 429 Too Many Requests');
                header('Retry-After: '.$this->retry_time);
                die('You are not allowed to access this file.');
            }
        }

        // Close database connection
        mysqli_close($mysqlConnection);
    }

    private function _load_configs(&$configsArray)
    {
        // Loading configs from wp-config.php file..
        $configFilePath = __DIR__.'/../../../wp-config.php';
        $configFileContent = file($configFilePath);
        foreach ($configFileContent as $line) {
            $matches = [];
            if (preg_match('/DEFINE\(\'(.*?)\',\s*\'(.*)\'\);/i', $line, $matches)) {
                $configsArray[$matches[1]] = $matches[2];
            }
            if (preg_match('/table_prefix.*\'(.*)\'/i', $line, $matches)) {
                $configsArray['TABLE_PREFIX'] = $matches[1];
            }
        }
    }

    private function _check_block_list(&$comments, &$regexesErrors)
    {
        // If it's in the block list..
        if (file_exists($this->block_list_file_path)) {
            $file_content = file($this->block_list_file_path);
            $to_block = false;
            for ($i = 1; $i < count($file_content); ++$i) {
                $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$value.'<br>';
                }

                if (!empty($value) and preg_match('/'.$value.'/i', $this->_current_remote_ips())) {
                    $to_block = true;
                }
                if (PREG_NO_ERROR != preg_last_error()) {
                    $regexesErrors[] = $value.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
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

    private function _check_regexes_uri(&$comments, &$regexesErrors)
    {
        // If hits a regex for query string..
        if (file_exists($this->block_regexes_uri_file_path)) {
            $file_content = file($this->block_regexes_uri_file_path);
            $to_block = false;
            for ($i = 1; $i < count($file_content); ++$i) {
                $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$value.'<br>';
                }

                if (!empty($value)) {
                    // Check query string..
                    if (!empty($_SERVER['REQUEST_URI'])
                    and preg_match($value, $_SERVER['REQUEST_URI'])) {
                        $to_block = true;
                    }
                    if (PREG_NO_ERROR != preg_last_error()) {
                        $regexesErrors[] = $value.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                    }
                    //var_dump(preg_last_error());
                }
            }

            // Debug..
            if ($this->debug) {
                echo '..with URI '.$_SERVER['REQUEST_URI'].'<br>=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }

            if ($to_block) {
                $comments .= 'Request blocking by regexes for query string. ';
                $this->retry_time = 86400;
            }
        }
    }

    private function _check_regexes_payload(&$comments, &$regexesErrors)
    {
        // If hits a regex for post data..
        if (file_exists($this->block_regexes_payload_file_path)) {
            $file_content = file($this->block_regexes_payload_file_path);
            $to_block = false;
            for ($i = 1; $i < count($file_content); ++$i) {
                $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$value.'<br>';
                }

                if (!empty($value)) {
                    // Check post data..
                    foreach ($_POST as $post_value) {
                        // Debug..
                        if ($this->debug) {
                            if (is_array($post_value)) {
                                foreach ($post_value as $post_sub_value) {
                                    echo '..with PAYLOAD '.$post_sub_value.'<br>';
                                }
                            } else {
                                echo '..with PAYLOAD '.$post_value.'<br>';
                            }
                        }

                        if (is_array($post_value)) {
                            foreach ($post_value as $post_sub_value) {
                                if (preg_match($value, $post_sub_value)) {
                                    $to_block = true;
                                }
                            }
                        } else {
                            if (preg_match($value, $post_value)) {
                                $to_block = true;
                            }
                        }

                        if (PREG_NO_ERROR != preg_last_error()) {
                            $regexesErrors[] = $value.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                        }
                        //var_dump(preg_last_error());
                    }
                }
            }

            // Debug..
            if ($this->debug) {
                echo '=> result $to_block='.($to_block ? 'true' : 'false').'<br><br>';
            }

            if ($to_block) {
                $comments .= 'Request blocking by regexes for payload. ';
                $this->retry_time = 86400;
            }
        }
    }

    private function _check_allow_list(&$comments, &$regexesErrors)
    {
        $bypassed = false;

        // If it's in the allow list..
        if (file_exists($this->allow_list_file_path)) {
            $file_content = file($this->allow_list_file_path);

            for ($i = 1; $i < count($file_content); ++$i) {
                $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));
                if (!empty($value) and preg_match('/'.$value.'/i', $this->_current_remote_ips())) {
                    $bypassed = true;
                }
                if (PREG_NO_ERROR != preg_last_error()) {
                    $regexesErrors[] = $value.' '.$this->regexes_errors_strings[preg_last_error()].PHP_EOL;
                }

                // Debug..
                if ($this->debug) {
                    echo 'Checking '.$value.'<br>';
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

    private function _save_the_blocking($mysqlConnection, $comments, $the_table_full_prefix)
    {
        $sql = 'INSERT INTO '.$the_table_full_prefix.'whats_going_on_block '
            .'(time, remote_ip, remote_port, user_agent, comments) '
            .'VALUES ('
            ."now(), '"
            .$this->_current_remote_ips()."', '"
            .$_SERVER['REMOTE_PORT']."', '"
            .(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')."','"
            .$comments."'"
            .');';
        $result = mysqli_query($mysqlConnection, $sql);
        //var_dump($result);
    }

    private function _save_my_request($mysqlConnection, $requests_last_minute, $requests_last_hour, $the_table_full_prefix)
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

        $result = mysqli_query($mysqlConnection, $sql);
        //var_dump($result);
    }

    private function _get_requests_per_minutes($minutes, $mysqlConnection, $the_table_full_prefix)
    {
        $return_value = -1;

        $sql = 'SELECT count(*) FROM '.$the_table_full_prefix.'whats_going_on '
            ."WHERE remote_ip = '".$this->_current_remote_ips()." '"
            .'AND time > NOW() - INTERVAL '.$minutes.' MINUTE;';
        if ($result = mysqli_query($mysqlConnection, $sql)) {
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

    private function _get_options($mysqlConnection, $the_table_full_prefix, &$max_per_minute, &$max_per_hour)
    {
        $sql = 'SELECT option_name, option_value FROM '.$the_table_full_prefix.'options '
            ."WHERE option_name IN ('wgojnj_limit_requests_per_minute', 'wgojnj_limit_requests_per_hour')";
        if ($result = mysqli_query($mysqlConnection, $sql)) {
            //var_dump($result);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    if ('wgojnj_limit_requests_per_minute' == $row[0]) {
                        $max_per_minute = $row[1];
                    }
                    if ('wgojnj_limit_requests_per_hour' == $row[0]) {
                        $max_per_hour = $row[1];
                    }
                }
                mysqli_free_result($result);
            }
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
