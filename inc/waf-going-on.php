<?php

$timeStart = microtime(true);

// Loading configs from wp-config.php file..
$configFilePath = __DIR__.'/../../../../wp-config.php';
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
$allowRegexesFilePath = __DIR__.'/../allow-regexes.php';
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
if ($max_per_minute > 0 and $requests_last_minute > $max_per_minute) {
    $comments .= 'Reached max requests per minute: '.$max_per_minute.' ';
    $retry_time = 60;
}
if ($max_per_hour > 0 and $request_last_hour > $max_per_hour) {
    $comments .= 'Reached max requests per hour: '.$max_per_hour.' ';
    $retry_time = 3600;
}
if (file_exists($blockListFilePath)) {
    if (in_array($_SERVER['REMOTE_ADDR'].PHP_EOL, file($blockListFilePath))) {
        $comments .= 'IP in the block-list. ';
        $retry_time = 86400;
    }
}
if (!empty($comments)) {
    $bypassed = false;

    if (file_exists($allowListFilePath)) {
        if (in_array($_SERVER['REMOTE_ADDR'].PHP_EOL, file($allowListFilePath))) {
            $comments .= 'IP in the allow-list. Bypassed..';
            $bypassed = true;
        }
    }
    waf_save_the_blocking($mysqlConnection, $comments, $the_table_full_prefix);

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
        .$_SERVER['REMOTE_ADDR']."', '"
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
        .$_SERVER['REMOTE_ADDR']."', '"
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
        ."WHERE remote_ip = '".$_SERVER['REMOTE_ADDR']." '"
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

//die;
