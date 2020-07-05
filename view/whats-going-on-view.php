<?php

defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
} else {
    if ('127.0.0.1' != wgojnj_current_remote_ips()) {
        // Remove administrator IP from records..
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
            ."WHERE remote_ip = '".wgojnj_current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
            ."WHERE remote_ip = '".wgojnj_current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
            ."WHERE remote_ip = '".wgojnj_current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
    }
}

// GEOIP
require WGOJNJ_PATH.'lib/geoip2.phar';
use GeoIp2\Database\Reader;

$reader = new Reader(WGOJNJ_PATH.'lib/GeoLite2-City.mmdb');

$limit_requests_per_minute = get_option('wgojnj_limit_requests_per_minute');
$limit_requests_per_hour = get_option('wgojnj_limit_requests_per_hour');
$items_per_page = get_option('wgojnj_items_per_page');

/*
 * Listing registers..
 */
global $wpdb;
global $current_page;
$total_sql = 'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on';
$main_sql = 'SELECT * FROM '.$wpdb->prefix.'whats_going_on ';
$maxs_reached_sql = 'SELECT max(last_minute) max_hits_minute_reached, max(last_hour) max_hits_hour_reached FROM '.$wpdb->prefix.'whats_going_on';

$add_sql = '';
if (isset($_GET['filter-url'])) {
    $add_sql .= " WHERE url = '".urldecode($_GET['filter-url'])."'";
} elseif (isset($_GET['filter-ip'])) {
    $add_sql .= " WHERE remote_ip = '".urldecode($_GET['filter-ip'])."'";
} elseif (isset($_GET['filter-uagent'])) {
    $add_sql .= " WHERE user_agent = '".urldecode($_GET['filter-uagent'])."'";
} elseif (isset($_GET['filter-method'])) {
    $add_sql .= " WHERE method = '".urldecode($_GET['filter-method'])."'";
}
$total_sql .= $add_sql;
$main_sql .= $add_sql;
$maxs_reached_sql .= $add_sql;

$total_registers = $wpdb->get_var($total_sql);
$offset = ($current_page - 1) * $items_per_page;

$main_sql .= 'ORDER BY time DESC LIMIT '.$items_per_page.' OFFSET '.$offset;
$results = $wpdb->get_results($main_sql);
//var_export($result);

$maxs_reached = $wpdb->get_results(
    $maxs_reached_sql
);
//var_dump($maxs_reached);
//$current_page = (isset($_POST['current_page']) ? $_POST['current_page'] : 1);

?>

<form method="post" action="<?php
//echo admin_url('tools.php?page=whats-going-on');
echo $_SERVER['REQUEST_URI'];
?>" id="this_form" name="this_form">

<div class="wrap">
    <span style="float: right">
        Support the project, please donate <a href="https://paypal.me/jaimeninoles" target="_blank"><b>here</b></a>.<br>
        Need help? Ask <a href="https://jnjsite.com/whats-going-on-for-wordpress/" target="_blank"><b>here</b></a>.
    </span>

    <h1><span class="dashicons dashicons-shield-alt wgo-icon"></span> What's going on, a simplified WAF</h1>
    
    <?php
    if (isset($wgojnjSms)) {
        echo $wgojnjSms;
    }

    ////////////////
    /////////////////////////////// START CHART
    $chart_sql = 'SELECT count(*) hits FROM '.$wpdb->prefix.'whats_going_on wgo'
        .' GROUP BY year(wgo.time), month(wgo.time), day(wgo.time), hour(wgo.time)';
    $chart_results = $wpdb->get_results($chart_sql);
    //var_dump($chart_results);
    ?>

    <script>
    function paintMainChart() {
        var ctx = document.getElementById('mainChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [<?php
                        if (count($chart_results) > 0) {
                            echo "'0'";
                            for ($i = 1; $i < count($chart_results); ++$i) {
                                echo ", '".$i."'";
                            }
                        }
                    ?>],
                datasets: [{
                    label: '# of requests per hour in the last week',
                    data: [<?php
                        if (count($chart_results) > 0) {
                            echo $chart_results[0]->hits;
                            for ($i = 1; $i < count($chart_results); ++$i) {
                                echo ','.$chart_results[$i]->hits;
                            }
                        }
                    ?>],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    }
    </script>
    <canvas id="mainChart" width="148" height="24"></canvas>
    <?php
    /////////////////////// END CHART
    ////////////////////////////////////////////////////////
    ?>

    <?php settings_fields('wgojnj_options_group'); ?>
    <?php do_settings_sections('wgojnj_options_group'); ?>

    <?php wp_nonce_field('wgojnj', 'wgojnj_nonce'); ?>

    <p>
        <input type="submit" name="btn-submit" id="btn-submit" class="button button-primary" value="Save config">

        <label for="limit_requests_per_minute">Max requests per minute</label>
        <select name="limit_requests_per_minute" id="limit_requests_per_minute">
            <option value="5"<?= (5 == $limit_requests_per_minute ? ' selected' : ''); ?>>5</option>
            <option value="10"<?= (10 == $limit_requests_per_minute ? ' selected' : ''); ?>>10</option>
            <option value="25"<?= (25 == $limit_requests_per_minute ? ' selected' : ''); ?>>25</option>
            <option value="50"<?= (50 == $limit_requests_per_minute ? ' selected' : ''); ?>>50</option>
            <option value="100"<?= (100 == $limit_requests_per_minute ? ' selected' : ''); ?>>100</option>
            <option value="200"<?= (200 == $limit_requests_per_minute ? ' selected' : ''); ?>>200</option>
            <option value="300"<?= (300 == $limit_requests_per_minute ? ' selected' : ''); ?>>300</option>
            <option value="500"<?= (500 == $limit_requests_per_minute ? ' selected' : ''); ?>>500</option>
            <option value="1000"<?= (1000 == $limit_requests_per_minute ? ' selected' : ''); ?>>1000</option>
            <option value="-1"<?= (-1 == $limit_requests_per_minute ? ' selected' : ''); ?>>Unlimited</option>
        </select>

        <label for="limit_requests_per_hour">Max requests per hour</label>
        <select name="limit_requests_per_hour" id="limit_requests_per_hour">
            <option value="50"<?= (50 == $limit_requests_per_hour ? ' selected' : ''); ?>>50</option>
            <option value="100"<?= (100 == $limit_requests_per_hour ? ' selected' : ''); ?>>100</option>
            <option value="250"<?= (250 == $limit_requests_per_hour ? ' selected' : ''); ?>>250</option>
            <option value="500"<?= (500 == $limit_requests_per_hour ? ' selected' : ''); ?>>500</option>
            <option value="1000"<?= (1000 == $limit_requests_per_hour ? ' selected' : ''); ?>>1000</option>
            <option value="2000"<?= (2000 == $limit_requests_per_hour ? ' selected' : ''); ?>>2000</option>
            <option value="3000"<?= (3000 == $limit_requests_per_hour ? ' selected' : ''); ?>>3000</option>
            <option value="5000"<?= (5000 == $limit_requests_per_hour ? ' selected' : ''); ?>>5000</option>
            <option value="10000"<?= (10000 == $limit_requests_per_hour ? ' selected' : ''); ?>>10000</option>
            <option value="-1"<?= (-1 == $limit_requests_per_hour ? ' selected' : ''); ?>>Unlimited</option>
        </select>

        <label for="items_per_page">Items per page</label>
        <select name="items_per_page" id="items_per_page">
            <option value="10"<?= (10 == $items_per_page ? ' selected' : ''); ?>>10</option>
            <option value="20"<?= (20 == $items_per_page ? ' selected' : ''); ?>>20</option>
            <option value="50"<?= (50 == $items_per_page ? ' selected' : ''); ?>>50</option>
            <option value="100"<?= (100 == $items_per_page ? ' selected' : ''); ?>>100</option>
            <option value="250"<?= (250 == $items_per_page ? ' selected' : ''); ?>>250</option>
            <option value="500"<?= (500 == $items_per_page ? ' selected' : ''); ?>>500</option>
            <option value="1000"<?= (1000 == $items_per_page ? ' selected' : ''); ?>>1000</option>
        </select>

        <span class="span-pagination"><?php

        if ($current_page > 1) {
            ?>
            <input type="submit" name="submit-previous-page" id="submit-previous-page" class="button button-primary" value="<<">
            <?php
        }

        ?>
        <a href="<?= admin_url('tools.php?page=whats-going-on'); ?>">Page <?= $current_page; ?> with total <?= $total_registers; ?> items</a>
        <?php

        if ($current_page * $items_per_page < $total_registers) {
            ?>
            <input type="submit" name="submit-next-page" id="submit-next-page" class="button button-primary" value=">>">
            <?php
        }

        ?>
        </span>
        <input type="hidden" name="current-page" id="current-page" value="<?= $current_page; ?>">
    </p>

    <table class="wp-list-table widefat fixed striped posts">
        <thead>
            <tr>
                <td>Time</td>
                <td>URL</td>
                <td>Remote IP</td>
                <td>Remote Port</td>
                <td>User Agent</td>
                <td>Method</td>
                <td>Hits minute (max <?= $maxs_reached[0]->max_hits_minute_reached; ?>)</td>
                <td>Hits hour (max <?= $maxs_reached[0]->max_hits_hour_reached; ?>)</td>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($results as $key => $result) {
            ?>

            <tr>
                <td><?= $result->time; ?></td>
                <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-url=<?= urlencode($result->url); ?>"><?= $result->url; ?></a></td>
                <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                <?php
                    wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                <td><?= $result->remote_port; ?></td>
                <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-uagent=<?= urlencode($result->user_agent); ?>"><?= $result->user_agent; ?></a></td>
                <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-method=<?= urlencode($result->method); ?>"><?= $result->method; ?></a></td>
                <td><?= $result->last_minute; ?></td></td>
                <td><?= $result->last_hour; ?></td></td>
            </tr>

            <?php
        }
        ?>
        </tbody>
    </table>

    <p>
        <input type="submit" name="submit-remove-old" id="submit-remove-old" class="button button-primary" value="Remove records of more than one week">
        <input type="submit" name="submit-remove-all" id="submit-remove-all" class="button" value="Remove all records">
        
        <span class="span-install">
            <?php
            if (!file_exists(ABSPATH.'.user.ini')) {
                ?>
                <input type="submit" name="submit-install-full-waf" id="submit-install-full-waf" class="button" value="Install .user.ini">
            <?php
            } else {
                ?>
                <input type="submit" name="submit-uninstall-full-waf" id="submit-uninstall-full-waf" class="button" value="Uninstall .user.ini">
            <?php
            }
            ?>
        </span>

    </p>
    
</div>

<div class="wrap-permanent-lists">
    <h2>Administration of unique IPs</h2>

    <p>The format of IPs is: HTTP_X_FORWARDED_FOR-HTTP_CLIENT_IP-REMOTE_ADDR
    If you see something like --127.0.0.1 it's because the web is not behind a proxy.
    You can use a Regular Expresions to check IPs.</p>

    <p>
        With this IP.. <input type="text" name="txt_this_ip" id="txt_this_ip" class="regular-text">
        <input type="submit" name="submit-remove-this-ip" id="submit-remove-this-ip" class="button button-green" value="Remove all records">
    </p>

    <div class="wrap-block-and-allow-lists">
        <div class="wrap" id="wrap-block-list">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Block list, one per line</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <textarea name="txt-block-list" id="txt-block-list" class="waf-textarea-config"><?php
                            $file_path = WGOJNJ_PATH.'block-list.php';
                            if (file_exists($file_path)) {
                                $the_file = file($file_path);
                                if (count($the_file) > 1) {
                                    for ($i = 1; $i < count($the_file); ++$i) {
                                        echo $the_file[$i];
                                    }
                                }
                            }
                            ?></textarea>
                            <?php
                            if (empty($the_file)) {
                                echo '<p>No IPs found.</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>

        <div class="wrap" id="wrap-allow-list">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Allow list, one per line</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <textarea name="txt-allow-list" id="txt-allow-list" class="waf-textarea-config"><?php
                            $file_path = WGOJNJ_PATH.'allow-list.php';
                            if (file_exists($file_path)) {
                                $the_file = file($file_path);
                                if (count($the_file) > 1) {
                                    for ($i = 1; $i < count($the_file); ++$i) {
                                        echo $the_file[$i];
                                    }
                                }
                            }
                            ?></textarea>
                            <?php
                            if (empty($the_file)) {
                                echo '<p>No IPs found.</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>
    </div>

    <p>
        <input type="submit" name="submit-save-ips-lists" id="submit-save-ips-lists" class="button button-red" value="Save IPs">
    </p>
</div>

<div class="wrap-permanent-regexes">
    <h2>Administration of Regex detections</h2>

    <p>This Regular Expresions are user for detecting requests with exploits, SQL injection.. searching in query strings and post data.</p>

    <div class="wrap" id="wrap-block-regexes">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Block Regexes, one per line</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <textarea name="txt-regexes-block" id="txt-regexes-block" class="waf-textarea-config"><?php
                        $file_path = WGOJNJ_PATH.'block-regexes.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);
                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $the_file[$i];
                                }
                            }
                        }
                        ?></textarea>
                        <?php
                        if (empty($the_file)) {
                            echo '<p>No Regexes found.</p>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table> 
    </div>

    <p>
        <input type="submit" name="submit-save-regexes" id="submit-save-regexes" class="button button-red" value="Save Regexes">
    </p>
</div>

<div class="wrap-permanent-lists">
    <h2>Administration of DDoS detections</h2>

    <?php
    /**
     * 500 errors
     * TTL or processor usage
     * spikes in traffic.
     */
    ?>

    <p>Under contruction..</p>

    <?php
    // Apply mathematics..
    $average_hits = 0;
    $variance_hits = 0;
    if (count($chart_results) > 0) {
        foreach ($chart_results as $key => $item) {
            $average_hits += $item->hits;
        }
        $average_hits = $average_hits / count($chart_results);
        foreach ($chart_results as $key => $item) {
            $variance_hits += ($item->hits - $average_hits) ^ 2;
        }
        $variance_hits = $variance_hits / count($chart_results);
    }
    ?>

    <script>
    function paintSpikesChart() {
        var ctxSpikesChart = document.getElementById('spikesChart').getContext('2d');
        var spikesChart = new Chart(ctxSpikesChart, {
            type: 'line',
            data: {
                labels: [<?php
                        if (count($chart_results) > 0) {
                            echo "'0'";
                            for ($i = 1; $i < count($chart_results); ++$i) {
                                echo ", '".$i."'";
                            }
                        }
                    ?>],
                datasets: [{
                    label: '# of requests per hour in the last week',
                    data: [<?php
                        if (count($chart_results) > 0) {
                            echo $chart_results[0]->hits;
                            for ($i = 1; $i < count($chart_results); ++$i) {
                                echo ','.$chart_results[$i]->hits;
                            }
                        }
                    ?>],
                    borderWidth: 1,
                    backgroundColor: 'rgba(255, 0, 0, 0.3)',
                    borderColor: 'rgba(255, 0, 0, 0.3)'
                },{
                    label: '# average hits per hour',
                    data: [<?php
                        if (count($chart_results) > 0) {
                            echo $average_hits;
                            for ($i = 1; $i < count($chart_results); ++$i) {
                                echo ','.$average_hits;
                            }
                        }
                    ?>],
                    borderWidth: 1,
                    borderColor: 'rgba(0, 0, 0, 1)',
                    fill: false
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    }
    </script>
    <canvas id="spikesChart" width="148" height="24"></canvas>

    <p>Average hits per hour: <?= $average_hits; ?> Variance hits per hour: <?= $variance_hits; ?></p>
</div>

<?php
// Results for blocks..
$block_sql = 'SELECT max(wgob.time) time, wgob.remote_ip, wgob.remote_port, wgob.user_agent, wgob.comments '
.' FROM '.$wpdb->prefix.'whats_going_on_block wgob'
.' GROUP BY remote_ip ORDER BY time DESC';
$results = $wpdb->get_results($block_sql);
?>

<div class="wrap-last-blocked">
    <h2>Last blocks (<?= count($results); ?>)</h2>

    <div class="wrap" id="block-last-blocks">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Time</td>
                    <td>Remote IP</td>
                    <td>Remote Port</td>
                    <td>User Agent</td>
                    <td>Comments</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->time; ?></td>
                    <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                    <?php
                        wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                    <td><?= $result->remote_port; ?></td></td>
                    <td><?= $result->user_agent; ?></td></td>
                    <td><?= $result->comments; ?></td></td>
                </tr>

            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$block_sql = 'SELECT count(*) as times, remote_ip FROM '.$wpdb->prefix.'whats_going_on_block GROUP BY remote_ip ORDER BY times DESC';
$results = $wpdb->get_results($block_sql);
?>

<div class="wrap-ips-blocked">
    <h2>Last IPs blocked (<?= count($results); ?>)</h2>

    <div class="wrap" id="block-ips-blocked">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>Remote IP</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                    <?php
                        wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                </tr>

            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Results for 404s..
$sql_404s = 'SELECT count(*) as times, remote_ip FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC';
$results = $wpdb->get_results($sql_404s);
?>

<div class="wrap-permanent-lists">
    <h2>Last IPs doing 404s (<?= count($results); ?>)</h2>

    <div class="wrap" id="wrap-block-404s">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>Remote IP</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                    <?php
                        wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                </tr>

                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

</form>

<p>This plugin includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" target="_blank">https://www.maxmind.com</a>.</p>

<script>
window.onload = () => {
    paintMainChart();
    paintSpikesChart();
}
</script>