<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-ddos">
    <h2>Administration of DDoS detections</h2>
    
    <div class="wrap">
        <?php
        /**
         * 500 errors
         * TTL or processor usage
         * spikes in traffic.
         */
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
                        type: 'bar',
                        label: '# of requests per hour',
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
                        borderWidth: 1,
                        borderColor: 'rgba(0, 0, 0, 1)',
                        fill: false
                    },{
                        label: '# A+SD',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                echo $average + $standard_deviation;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average + $standard_deviation);
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
                                echo $average + $standard_deviation * 2;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average + $standard_deviation * 2);
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
                                echo $average + $standard_deviation * 3;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average + $standard_deviation * 3);
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
                                echo $average - $standard_deviation;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average - $standard_deviation);
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
                                echo $average - $standard_deviation * 2;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average - $standard_deviation * 2);
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
                                echo $average - $standard_deviation * 3;
                                for ($i = 1; $i < count($chart_results); ++$i) {
                                    echo ','.($average - $standard_deviation * 3);
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        borderColor: 'rgba(200, 200, 200, 1)',
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

        <p>Average (A): <?= $average; ?><br>
        Standard deviation (SD): <?= $standard_deviation; ?><br>
        Variance (V): <?= $variance; ?>
        </p>
    </div>
</div>