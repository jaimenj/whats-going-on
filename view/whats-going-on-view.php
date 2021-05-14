<style>hr{margin-top: 30px;}</style>
<?php

defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
} else {
    if ('--127.0.0.1' != WhatsGoingOn::get_instance()->current_remote_ips()) {
        // Remove administrator IP from records to prevent auto-blocking..
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
            ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
            ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
        $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
            ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
        $results = $wpdb->get_results($sql);
    }
}

/*
 * Listing registers..
 */
global $wpdb;
$total_sql = 'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on';
$maxs_reached_sql = 'SELECT max(last_minute) max_hits_minute_reached, max(last_hour) max_hits_hour_reached FROM '.$wpdb->prefix.'whats_going_on';

$add_sql = '';
if (isset($_GET['filter-url'])) {
    $add_sql .= " WHERE url = '".sanitize_text_field($_GET['filter-url'])."'";
} elseif (isset($_GET['filter-ip'])) {
    $add_sql .= " WHERE remote_ip = '".sanitize_text_field($_GET['filter-ip'])."'";
} elseif (isset($_GET['filter-method'])) {
    $add_sql .= " WHERE method = '".sanitize_text_field($_GET['filter-method'])."'";
}
$total_sql .= $add_sql;
$maxs_reached_sql .= $add_sql;

$total_registers = $wpdb->get_var($total_sql);

$maxs_reached = $wpdb->get_results(
    $maxs_reached_sql
);
?>

<form method="post" enctype="multipart/form-data" action="<?php
//echo admin_url('tools.php?page=whats-going-on');
echo $_SERVER['REQUEST_URI'];
?>"
id="wgo_form" 
name="wgo_form"
data-wgo_ajax_url="<?= admin_url('admin-ajax.php') ?>">

    <div class="wrap">
        <span style="float: right">
            Support the project, please donate <a href="https://paypal.me/jaimeninoles" target="_blank"><b>here</b></a>.<br>
            Need help? Ask <a href="https://jnjsite.com/whats-going-on-for-wordpress/" target="_blank"><b>here</b></a>.
        </span>

        <h1><span class="dashicons dashicons-shield-alt wgo-icon"></span> What's going on, a simple firewall</h1>
        
        <?php
        if (isset($wgoSms)) {
            echo $wgoSms;
        }

        foreach(WhatsGoingOnMessages::get_instance()->get_messages() as $message) {
            echo '<div id="message" class="notice notice-success is-dismissible"><p>'.$message.'</p></div>';
        }

        ////////////////
        /////////////////////////////// START CHART
        $chart_sql = "SELECT count(*) hits, DATE_FORMAT(wgo.time, '%Hh') the_hour FROM ".$wpdb->prefix.'whats_going_on wgo'
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
        $variance = pow($standard_deviation, 2);
        ?>

        <script>
        function paintMainChart() {
            var ctx = document.getElementById('mainChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php
                            if (count($chart_results) > 0) {
                                echo "'".$chart_results[0]->the_hour."'";
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ", '".$chart_results[$i]->the_hour."'";
                                }
                            }
                        ?>],
                    datasets: [{
                        type: 'bar',
                        label: '# of requests per hour in the last <?php
                            if ($days_to_store > 1) {
                                echo $days_to_store.' days';
                            } else {
                                echo 'day';
                            }
                            echo ' (~'.($days_to_store * 24).' hours)';
                            ?>',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                echo $chart_results[0]->hits;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$chart_results[$i]->hits;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        backgroundColor: [<?php
                            if (count($chart_results) > 0) {
                                $i = 0;
                                if (abs($chart_results[0]->hits - $average) > 3 * $standard_deviation) {
                                    echo "'rgba(255, 0, 0, 1)'";
                                } elseif ($chart_results[0]->hits < ($average * $notify_requests_less_than_x_percent) / 100 ) {
                                    echo "'rgba(255, 0, 0, 1)'";
                                } elseif (abs($chart_results[0]->hits - $average) > 2 * $standard_deviation) {
                                    echo "'rgba(255, 0, 0, 0.7)'";
                                } elseif (abs($chart_results[0]->hits - $average) > $standard_deviation) {
                                    echo "'rgba(255, 0, 0, 0.5)'";
                                } else {
                                    echo "'rgba(20, 20, 20, 0.3)'";
                                }
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    if (abs($chart_results[$i]->hits - $average) > 3 * $standard_deviation) {
                                        echo ", 'rgba(255, 0, 0, 1)'";
                                    } elseif ($chart_results[$i]->hits < ($average * $notify_requests_less_than_x_percent) / 100 ) {
                                        echo ", 'rgba(255, 0, 0, 1)'";
                                    } elseif (abs($chart_results[$i]->hits - $average) > 2 * $standard_deviation) {
                                        echo ", 'rgba(255, 0, 0, 0.7)'";
                                    } elseif (abs($chart_results[$i]->hits - $average) > $standard_deviation) {
                                        echo ", 'rgba(255, 0, 0, 0.5)'";
                                    } else {
                                        echo ", 'rgba(20, 20, 20, 0.3)'";
                                    }
                                }
                            }
                        ?>],
                        borderColor: 'rgba(255, 0, 0, 0.3)'
                    },{
                        label: '# average hits per hour',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                echo $average;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$average;
                                }
                            }
                        ?>],
                        borderWidth: 1.5,
                        borderColor: 'rgba(0, 0, 0, 1)',
                        fill: false
                    },{
                        label: '# A+SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_plus_sd = $average + $standard_deviation;
                                echo $a_plus_sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_plus_sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(100, 100, 100, 1)',
                        fill: false
                    },{
                        label: '# A+2SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_plus_2sd = $average + $standard_deviation * 2;
                                echo $a_plus_2sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_plus_2sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(150, 150, 150, 1)',
                        fill: false
                    },{
                        label: '# A+3SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_plus_3sd = $average + $standard_deviation * 3;
                                echo $a_plus_3sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_plus_3sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(200, 200, 200, 1)',
                        fill: false
                    },{
                        label: '# A-SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_minus_sd = $average - $standard_deviation;
                                echo $a_minus_sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_minus_sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(100, 100, 100, 1)',
                        fill: false
                    },{
                        label: '# A-2SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_minus_2sd = $average - $standard_deviation * 2;
                                echo $a_minus_2sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_minus_2sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(150, 150, 150, 1)',
                        fill: false
                    },{
                        label: '# A-3SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_minus_3sd = $average - $standard_deviation * 3;
                                echo $a_minus_3sd;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_minus_3sd;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(200, 200, 200, 1)',
                        fill: false
                    }<?php if($notify_requests_less_than_x_percent > 0) { ?>,{
                        label: '# <?= $notify_requests_less_than_x_percent ?>%A',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                $a_min_percent = $average * $notify_requests_less_than_x_percent / 100;
                                echo $a_min_percent;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.$a_min_percent;
                                }
                            }
                        ?>],
                        borderWidth: 1.5,
                        borderColor: 'rgba(255, 0, 0, 1)',
                        fill: false
                    }<?php } ?>]
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
        <p>A: Average. SD: Stardard Deviation. 2SD: Twice the Standard Deviation. 3SD.. (DB v<?= get_option('wgo_db_version') ?>)</p>
        <?php
        /////////////////////// END CHART
        ////////////////////////////////////////////////////////
        ?>

        <?php settings_fields('wgo_options_group'); ?>
        <?php do_settings_sections('wgo_options_group'); ?>

        <?php wp_nonce_field('wgojnj', 'wgo_nonce'); ?>

        <p>
            <input type="submit" name="btn-submit" id="btn-submit" class="button button-green" value="Save this configs">

            <label for="days_to_store">Days to store</label>
            <select name="days_to_store" id="days_to_store">
                <option value="1"<?= (1 == $days_to_store ? ' selected' : ''); ?>>1</option>
                <option value="2"<?= (2 == $days_to_store ? ' selected' : ''); ?>>2</option>
                <option value="3"<?= (3 == $days_to_store ? ' selected' : ''); ?>>3</option>
                <option value="7"<?= (7 == $days_to_store ? ' selected' : ''); ?>>7</option>
                <option value="14"<?= (14 == $days_to_store ? ' selected' : ''); ?>>14</option>
                <option value="28"<?= (28 == $days_to_store ? ' selected' : ''); ?>>28</option>
            </select>

            <label for="im_behind_proxy">The website is behind a proxy</label>
            <select name="im_behind_proxy" id="im_behind_proxy">
                <option value="0"<?= (0 == $im_behind_proxy ? ' selected' : ''); ?>>No</option>
                <option value="1"<?= (1 == $im_behind_proxy ? ' selected' : ''); ?>>Yes</option>
            </select>

            <label for="notification_email">Notification email</label>
            <input type="text" name="notification_email" id="notification_email" class="regular-text" value="<?= $notification_email; ?>">
            <input type="submit" name="submit-check-email" id="submit-check-email" class="button button-green" value="Check email">
        </p>

        <div class="table-responsive" id="wgo-datatable-container">
            <table 
            class="records_list table table-striped table-bordered table-hover" 
            id="wgo-datatable" 
            width="100%">
                <thead>
                    <tr>
                        <td>Time</td>
                        <td>URL method</td>
                        <td>Remote IP</td>
                        <td>Port</td>
                        <td>Country</td>
                        <td>User Agent</td>
                        <td>Method</td>
                        <td>Hits minute (max <?= $maxs_reached[0]->max_hits_minute_reached; ?>)</td>
                        <td>Hits hour (max <?= $maxs_reached[0]->max_hits_hour_reached; ?>)</td>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p>
            <input type="submit" name="submit-remove-old" id="submit-remove-old" class="button button-primary" value="Remove records of more than one week">
            <input type="submit" name="submit-remove-all" id="submit-remove-all" class="button" value="Remove all records">
            
            <span class="span-install">
                <?php
                if (!WhatsGoingOn::get_instance()->is_waf_installed()) {
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

    <hr>
    <?php include WGO_PATH.'view/sub-unique-ips.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-dos.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-ddos.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-regexes.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-regexes-errors.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-countries-continents.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-last-blocks.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-last-ips-doing-404s.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-last-urls-doing-404s.php'; ?>
    <hr>
    <?php include WGO_PATH.'view/sub-most-visited-from.php'; ?>

</form>
<hr>

<p>This plugin includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" target="_blank">https://www.maxmind.com</a>.</p>

<?php include WGO_PATH.'view/sub-popup-info.php'; ?>

<script>
    let weAreInWhatsGoingOn = true
</script>