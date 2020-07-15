<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-ddos">
    <h2>Administration of Countries and Continents</h2>
    
    <div class="wrap">

        <?php
        $visits_per_country = [];
        /*foreach ($all_records as $key => $item) {
            if (isset($visits_per_country[$item->country_code])) {
                ++$visits_per_country[$item->country_code]['counter'];
            } else {
                $visits_per_country[$item->country_code]['counter'] = 1;
                $visits_per_country[$item->country_code]['name'] = $record->country->name;
            }
        }
        var_dump($visits_per_country);
        exit;*/
        ?>

        <script>
        function paintCountriesAndContinentsChart() {
            var ctxCountriesAndContinentsChart = document.getElementById('countriesAndContinentsChart').getContext('2d');
            var countriesAndContinentsChart = new Chart(ctxCountriesAndContinentsChart, {
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
        <canvas id="countriesAndContinentsChart" width="148" height="24"></canvas>
    </div>
</div>