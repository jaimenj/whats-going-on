<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-countries">
    <h2>Administration of Countries and Continents</h2>
    
    <?php
    if (file_exists(WGOJNJ_PATH.'block-countries.php')) {
        $blocking_countries = explode(PHP_EOL, file_get_contents(WGOJNJ_PATH.'block-countries.php'));
        unset($blocking_countries[0]);
    } else {
        $blocking_countries = [];
    }

    $available_countries = file(WGOJNJ_PATH.'lib/isoCountriesCodes.csv');
    /*var_dump($blocking_countries);
    var_dump($available_countries);*/
    ?>

    <div class="wrap-block-and-allow-countries">
        <div class="wrap" id="wrap-countries-continents-allowed">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Allowed</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="select_block_countries[]" id="select_block_countries" multiple size="20" class="waf-select-area-config">
                                <?php
                                foreach ($available_countries as $country) {
                                    $country_code = explode(',', $country)[0];
                                    $country_name = str_replace('"', '', explode(',', $country)[1]);
                                    if (!in_array($country_code, $blocking_countries)) {
                                        echo '<option value="'.$country_code.'">'.$country_name.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="submit" name="submit-block-selected-countries" id="submit-block-selected-coutries" class="button button-red" value="Block selected countries">
        </div>
        <div class="wrap" id="wrap-countries-continents-blocked">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Blocked</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="select_unblock_countries[]" id="select_unblock_countries" multiple size="20" class="waf-select-area-config">
                                <?php
                                foreach ($blocking_countries as $country_code) {
                                    echo '<option value="'.$country_code.'">'.$isoCountriesArray[$country_code].'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="submit" name="submit-unblock-selected-countries" id="submit-unblock-selected-coutries" class="button button-green" value="Unblock selected countries">
        </div>
    </div>


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
    <div class="wrap">
        <table class="wp-list-table widefat fixed striped posts">
            <tbody>
                <tr>
                    <td>
                    <canvas id="countriesAndContinentsChart"></canvas>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>