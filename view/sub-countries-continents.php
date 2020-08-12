<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-countries">
    <h2>Administration of Countries and Continents</h2>
    
    <?php
    $countries_sql = 'SELECT country_code, count(*) times FROM '.$wpdb->prefix.'whats_going_on'
        .' WHERE country_code IS NOT NULL'
        .' GROUP BY country_code'
        .' ORDER BY times DESC';
    $countries_results = $wpdb->get_results($countries_sql);
    //var_dump($isoCountriesArray);
    ?>
    
    <p>More PHP requests from: 
    <?php
    $total_to_list = 10;
    for ($i = 0; $i < $total_to_list; ++$i) {
        if (!empty($countries_results[$i]->country_code)) {
            echo $isoCountriesArray[$countries_results[$i]->country_code]
                .': '.$countries_results[$i]->times.' requests';
            if ($i != $total_to_list - 1) {
                echo ' - ';
            } else {
                echo ' - etc..';
            }
        }
    }
    ?>
    </p>

    <div class="wrap">
        <table class="wp-list-table widefat fixed striped posts">
            <tbody>
                <tr>
                    <td>
                        <div id="the_svg_map"></div>

                        <script>
                        function paintCountriesAndContinents() {
                            new svgMap({
                                targetElementID: 'the_svg_map',
                                minZoom: 1,
                                maxZoom: 10,
                                mouseWheelZoomEnabled: false,
                                data: {
                                    data: {
                                        rpc: {
                                            name: '# of requests',
                                            //format: '{0} total',
                                            thousandSeparator: ''
                                        }
                                    },
                                    applyData: 'rpc',
                                    values: {
                                        <?php
                                        for ($i = 0; $i < count($countries_results) - 1; ++$i) {
                                            echo $countries_results[$i]->country_code
                                                .': {rpc: '.$countries_results[$i]->times.'},';
                                        }
                                        echo $countries_results[count($countries_results) - 1]->country_code
                                            .': {rpc: '.$countries_results[count($countries_results) - 1]->times.'}';
                                        ?>
                                    }
                                }
                            }); 
                        }
                        </script>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php
    if (file_exists(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php')) {
        $blocking_countries = explode(PHP_EOL, file_get_contents(ABSPATH.'/wp-content/uploads/wgo-things/block-countries.php'));
        unset($blocking_countries[0]);
    } else {
        $blocking_countries = [];
    }
    $blocking_countries_array_ordered = [];
    foreach ($blocking_countries as $code) {
        $blocking_countries_array_ordered[$code] = $isoCountriesArray[$code];
    }
    asort($blocking_countries_array_ordered);

    $available_countries = file(WGO_PATH.'lib/isoCountriesCodes.csv');
    $available_countries_array_ordered = [];
    foreach ($available_countries as $key => $country) {
        $country_code = explode(',', $country)[0];
        $country_name = str_replace('"', '', explode(',', $country)[1]);
        $available_countries_array_ordered[$country_code] = $country_name;
    }
    asort($available_countries_array_ordered);
    /*var_dump($blocking_countries);
    var_dump($available_countries);*/
    ?>

    <div class="wrap-block-and-allow-countries">
        <div class="wrap" id="wrap-countries-continents-allowed">
            <input type="submit" name="submit-block-selected-countries" id="submit-block-selected-coutries" class="button button-red" value="Block selected countries">
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
                                foreach ($available_countries_array_ordered as $country_code => $country_name) {
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
            <p>With this continent.. 
            <select name="select_block_continent[]" id="select_block_continent">
                <option value="NA">North America</option>
                <option value="SA">South America</option>
                <option value="EU">Europe</option>
                <option value="AF">Africa</option>
                <option value="AS">Asia</option>
                <option value="OC">Oceania</option>
                <option value="AN">Antarctica</option>
            </select>
            <input type="submit" name="submit-block-continent" id="submit-block-continent" class="button button-red" value="Block">
            </p>
        </div>
        <div class="wrap" id="wrap-countries-continents-blocked">
            <input type="submit" name="submit-unblock-selected-countries" id="submit-unblock-selected-coutries" class="button button-green" value="Unblock selected countries">
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
                                foreach ($blocking_countries_array_ordered as $country_code => $country_name) {
                                        echo '<option value="'.$country_code.'">'.$country_name.'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>With this continent.. 
            <select name="select_unblock_continent[]" id="select_block_continent">
                <option value="NA">North America</option>
                <option value="SA">South America</option>
                <option value="EU">Europe</option>
                <option value="AF">Africa</option>
                <option value="AS">Asia</option>
                <option value="OC">Oceania</option>
                <option value="AN">Antarctica</option>
            </select>
            <input type="submit" name="submit-unblock-continent" id="submit-unblock-continent" class="button button-green" value="Unblock">
            </p>
        </div>
    </div>

</div>