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
        $countries_sql = 'SELECT country_code, count(*) times FROM '.$wpdb->prefix.'whats_going_on'
            .' WHERE country_code IS NOT NULL'
            .' GROUP BY country_code'
            .' ORDER BY times DESC';
        $countries_results = $wpdb->get_results($countries_sql);
        //var_dump($isoCountriesArray);
        ?>

        <script>
        function paintCountriesAndContinentsChart() {
            var ctxCountriesAndContinentsChart = document.getElementById('countriesAndContinentsChart').getContext('2d');
            var countriesAndContinentsChart = new Chart(ctxCountriesAndContinentsChart, {
                type: 'horizontalBar',
                data: {
                    labels: [<?php
                            if (count($countries_results) > 0) {
                                $code = $countries_results[0]->country_code;
                                echo "'".(isset($isoCountriesArray[$code]) ? $code.'::'.$isoCountriesArray[$code] : '')."'";
                                for ($i = 1; $i < count($countries_results); ++$i) {
                                    $code = $countries_results[$i]->country_code;
                                    echo ", '".(isset($isoCountriesArray[$code]) ? $code.'::'.$isoCountriesArray[$code] : '')."'";
                                }
                            }
                        ?>],
                    datasets: [{
                        label: '# of requests per country for all records',
                        data: [<?php
                            if (count($chart_results) > 0) {
                                echo $countries_results[0]->times;
                                for ($i = 1; $i < count($countries_results); ++$i) {
                                    echo ','.$countries_results[$i]->times;
                                }
                            }
                        ?>],
                        borderWidth: 1,
                        backgroundColor: 'rgba(255, 0, 0, 0.3)',
                        borderColor: 'rgba(255, 0, 0, 0.3)'
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                autoSkip: false
                            }
                        }]
                    }
                }
            });
        }
        </script>
        <canvas id="countriesAndContinentsChart"></canvas>
    </div>
</div>