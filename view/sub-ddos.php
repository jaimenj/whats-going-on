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
</div>