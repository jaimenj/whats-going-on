<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

global $wpdb;

$notify_requests_less_than_x_percent = get_option('wgo_notify_requests_less_than_x_percent');
$days_to_store = get_option('wgo_days_to_store');

// Remove no tracking IPs..
$no_track_list_file_path = WGO_WP_UPLOAD_DIR.'/wgo-things/no-track-list.php';
if (file_exists($no_track_list_file_path)) {
    $file_content = file($no_track_list_file_path);
    for ($i = 1; $i < count($file_content); ++$i) {
        $ip_regex = trim(str_replace(PHP_EOL, '', $file_content[$i]));

        foreach (WhatsGoingOnDatabase::get_instance()->get_table_names() as $tableName) {
            $sql = 'DELETE FROM '.$wpdb->prefix.$tableName.' '
                ."WHERE remote_ip like '%".$ip_regex."%';";
            $wpdb->get_results($sql);
        }
    }
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
<?php

    $total_requests = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on');
    $total_requests_blocked = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on_block');
    $total_percent_blocked = $total_requests_blocked / ($total_requests_blocked + $total_requests) * 100;

?>
<div class="wgo-progress-bar-border">
    <span class="wgo-progress-queue-text" id="wgo-progress-queue-text">
        Total <?= $total_requests ?> requests, <?= $total_requests_blocked ?> blocked (<?= number_format($total_percent_blocked, 4) ?>%)..
    </span>
    <div class="wgo-progress-queue-content" 
    id="wgo-progress-queue-content" 
    style="width:<?= 100 - $total_percent_blocked ?>%;"></div>
</div>
<p>A: Average. SD: Stardard Deviation. 2SD: Twice the Standard Deviation. 3SD.. (DB v<?= get_option('wgo_db_version') ?>)</p>
<?php
/////////////////////// END CHART
////////////////////////////////////////////////////////
