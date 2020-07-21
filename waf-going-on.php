<?php

$timeStart = microtime(true);

// Loading configs from wp-config.php file..
$configFilePath = __DIR__.'/../../../wp-config.php';
$configFileContent = file($configFilePath);
$configsArray = [];
foreach ($configFileContent as $line) {
    $matches = [];
    if (preg_match('/DEFINE\(\'(.*?)\',\s*\'(.*)\'\);/i', $line, $matches)) {
        $configsArray[$matches[1]] = $matches[2];
    }
    if (preg_match('/table_prefix.*\'(.*)\'/i', $line, $matches)) {
        $configsArray['TABLE_PREFIX'] = $matches[1];
    }
}
$blockListFilePath = __DIR__.'/../block-list.php';
$allowListFilePath = __DIR__.'/../allow-list.php';
$blockRegexesFilePath = __DIR__.'/../block-regexes.php';
//var_dump($configsArray); die;

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

$url = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
//echo $url.PHP_EOL;

waf_get_options($mysqlConnection, $the_table_full_prefix, $max_per_minute, $max_per_hour);
//echo 'MAX MINUTE: '.$max_per_minute.PHP_EOL;
//echo 'MAX_HOUR: '.$max_per_hour.PHP_EOL;

$requests_last_minute = waf_get_requests_per_minutes(1, $mysqlConnection, $the_table_full_prefix);
$requests_last_hour = waf_get_requests_per_minutes(60, $mysqlConnection, $the_table_full_prefix);
//echo 'REQ MINUTE: '.$requests_last_minute.PHP_EOL;
//echo 'REQ_HOUR: '.$requests_last_hour.PHP_EOL;

waf_save_my_request($mysqlConnection, $url, $requests_last_minute, $requests_last_hour, $the_table_full_prefix);

// To block or not to block, that's the matter..
$comments = '';
$retry = 30;

// If it achieves max requests per minute..
if ($max_per_minute > 0 and $requests_last_minute > $max_per_minute) {
    $comments .= 'Reached max requests per minute: '.$max_per_minute.' ';
    $retry_time = 60;
}

// If it achieves max requests per hour..
if ($max_per_hour > 0 and $requests_last_hour > $max_per_hour) {
    $comments .= 'Reached max requests per hour: '.$max_per_hour.' ';
    $retry_time = 3600;
}

// Regexes errors
$regexesErrorsFile = __DIR__.'/waf-errors.log';
$regexesErrors = file($regexesErrorsFile);
$regexesErrorsStrings = [
    0 => 'PREG_NO_ERROR',
    1 => 'PREG_INTERNAL_ERROR',
    2 => 'PREG_BACKTRACK_LIMIT_ERROR',
    3 => 'PREG_RECURSION_LIMIT_ERROR',
    4 => 'PREG_BAD_UTF8_ERROR',
    5 => 'PREG_BAD_UTF8_OFFSET_ERROR',
    6 => 'PREG_JIT_STACKLIMIT_ERROR',
];

// If it's in the block list..
if (file_exists($blockListFilePath)) {
    $file_content = file($blockListFilePath);
    $to_block = false;
    for ($i = 1; $i < count($file_content); ++$i) {
        $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));
        if (!empty($value) and preg_match('/'.$value.'/', waf_current_remote_ips())) {
            $to_block = true;
        }
        if (PREG_NO_ERROR != preg_last_error()) {
            $regexesErrors[] = $value.' '.$regexesErrorsStrings[preg_last_error()].PHP_EOL;
        }
    }
    if ($to_block) {
        $comments .= 'IP in the block-list. ';
        $retry_time = 86400;
    }
}

// If hits a regex for query string or post data..
if (file_exists($blockRegexesFilePath)) {
    $file_content = file($blockRegexesFilePath);
    $to_block = false;
    for ($i = 1; $i < count($file_content); ++$i) {
        $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));
        if (!empty($value)) {
            // Check query string..
            if (!empty($_SERVER['QUERY_STRING'])
            and preg_match($value, $_SERVER['QUERY_STRING'])) {
                $to_block = true;
            }
            if (PREG_NO_ERROR != preg_last_error()) {
                $regexesErrors[] = $value.' '.$regexesErrorsStrings[preg_last_error()].PHP_EOL;
            }

            // Check post data..
            foreach ($_POST as $post_key => $post_value) {
                if (preg_match($value, $post_value)) {
                    $to_block = true;
                }
                if (PREG_NO_ERROR != preg_last_error()) {
                    $regexesErrors[] = $value.' '.$regexesErrorsStrings[preg_last_error()].PHP_EOL;
                }
            }
        }
    }
    if ($to_block) {
        $comments .= 'Request blocking by regexes. ';
        $retry_time = 86400;
    }
}

// Save Regexes errors to review..
file_put_contents($regexesErrorsFile, array_unique($regexesErrors));

// If we are blocking..
if (!empty($comments)) {
    $bypassed = false;

    // If it's in the allow list..
    if (file_exists($allowListFilePath)) {
        $file_content = file($allowListFilePath);
        for ($i = 1; $i < count($file_content); ++$i) {
            $value = trim(str_replace(PHP_EOL, '', $file_content[$i]));
            if (!empty($value) and preg_match('/'.$value.'/', waf_current_remote_ips())) {
                $bypassed = true;
            }
            if (PREG_NO_ERROR != preg_last_error()) {
                $regexesErrors[] = $value.' '.$regexesErrorsStrings[preg_last_error()].PHP_EOL;
            }
        }
        if ($bypassed) {
            $comments .= 'IP in the allow-list. Bypassed..';
        }
    }

    waf_save_the_blocking($mysqlConnection, $comments, $the_table_full_prefix);
    file_put_contents($regexesErrorsFile, array_unique($regexesErrors));

    if (!$bypassed) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Retry-After: '.$retry_time);
        die('You are not allowed to access this file.');
    }
}

// Close database connection
mysqli_close($mysqlConnection);

$timeEnd = microtime(true);
$timeConsumed = $timeEnd - $timeStart;
//echo 'Time consumed: '.$timeConsumed.' secs';

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////
///////////////

function waf_save_the_blocking($mysqlConnection, $comments, $the_table_full_prefix)
{
    $sql = 'INSERT INTO '.$the_table_full_prefix.'whats_going_on_block '
        .'(time, remote_ip, remote_port, user_agent, comments) '
        .'VALUES ('
        ."now(), '"
        .waf_current_remote_ips()."', '"
        .$_SERVER['REMOTE_PORT']."', '"
        .$_SERVER['HTTP_USER_AGENT']."','"
        .$comments."'"
        .');';
    $result = mysqli_query($mysqlConnection, $sql);
    //var_dump($result);
}

function waf_save_my_request($mysqlConnection, $url, $requests_last_minute, $requests_last_hour, $the_table_full_prefix)
{
    $sql = 'INSERT INTO '.$the_table_full_prefix.'whats_going_on '
        .'(time, url, remote_ip, remote_port, user_agent, method, last_minute, last_hour) '
        .'VALUES ('
        ."now(), '"
        .$url."', '"
        .waf_current_remote_ips()."', '"
        .$_SERVER['REMOTE_PORT']."', '"
        .$_SERVER['HTTP_USER_AGENT']."', '"
        .$_SERVER['REQUEST_METHOD']."', "
        .$requests_last_minute.', '
        .$requests_last_hour
        .');';

    $result = mysqli_query($mysqlConnection, $sql);
    //var_dump($result);
}

function waf_get_requests_per_minutes($minutes, $mysqlConnection, $the_table_full_prefix)
{
    $return_value = -1;

    $sql = 'SELECT count(*) FROM '.$the_table_full_prefix.'whats_going_on '
        ."WHERE remote_ip = '".waf_current_remote_ips()." '"
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

function waf_get_options($mysqlConnection, $the_table_full_prefix, &$max_per_minute, &$max_per_hour)
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

function waf_current_remote_ips()
{
    return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '').'-'
        .(isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '').'-'
        .$_SERVER['REMOTE_ADDR'];
}

//die;