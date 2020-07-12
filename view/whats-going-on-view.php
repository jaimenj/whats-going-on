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

if (!empty(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0]) and 2 == strlen(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])) {
    $reader = new Reader(WGOJNJ_PATH.'lib/GeoLite2-City.mmdb', [explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0], 'en']);
} else {
    $reader = new Reader(WGOJNJ_PATH.'lib/GeoLite2-City.mmdb');
}

$limit_requests_per_minute = get_option('wgojnj_limit_requests_per_minute');
$limit_requests_per_hour = get_option('wgojnj_limit_requests_per_hour');
$items_per_page = get_option('wgojnj_items_per_page');
$days_to_store = get_option('wgojnj_days_to_store');

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

    <h1><span class="dashicons dashicons-shield-alt wgo-icon"></span> What's going on, a simple WAF</h1>
    
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
                    label: '# of requests per hour in the last <?php
                        if ($days_to_store > 1) {
                            echo $days_to_store.' days';
                        } else {
                            echo 'day';
                        }
                        ?>',
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
        <input type="submit" name="btn-submit" id="btn-submit" class="button button-green" value="Save all configs">

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

        <label for="days_to_store">Days to store</label>
        <select name="days_to_store" id="days_to_store">
            <option value="1"<?= (1 == $days_to_store ? ' selected' : ''); ?>>1</option>
            <option value="2"<?= (2 == $days_to_store ? ' selected' : ''); ?>>2</option>
            <option value="3"<?= (3 == $days_to_store ? ' selected' : ''); ?>>3</option>
            <option value="7"<?= (7 == $days_to_store ? ' selected' : ''); ?>>7</option>
            <option value="14"<?= (14 == $days_to_store ? ' selected' : ''); ?>>14</option>
            <option value="28"<?= (28 == $days_to_store ? ' selected' : ''); ?>>28</option>
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

    <?php include(WGOJNJ_PATH.'view/sub-unique-ips.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-regexes.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-dos.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-ddos.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-last-blocks.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-last-ips-blocked.php') ?>
    <?php include(WGOJNJ_PATH.'view/sub-last-ips-doing-404s.php') ?>

</form>

<p>This plugin includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" target="_blank">https://www.maxmind.com</a>.</p>

<script>
window.onload = () => {
    paintMainChart();
    paintSpikesChart();
}
</script>