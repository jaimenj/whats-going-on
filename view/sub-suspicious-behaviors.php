<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Suspicious IPs doing blocks..
$sql_suspicious_blocks = 'SELECT count(*) times, remote_ip, country_code '
    .'FROM '.$wpdb->prefix.'whats_going_on_block '
    .'group by remote_ip, country_code '
    .'order by times desc;';
$results_blocks = $wpdb->get_results($sql_suspicious_blocks);

// Suspicious IPs doing 404s..
$sql_suspicious_404s = 'SELECT count(*) times, remote_ip, country_code '
    .'FROM '.$wpdb->prefix.'whats_going_on_404s '
    .'group by remote_ip, country_code '
    .'order by times desc;';
$results_404s = $wpdb->get_results($sql_suspicious_404s);

$max_items_to_show = 10;

?>
<div class="wrap-permanent-lists">
    <h2>Suspicious behaviours blocked <a href="javascript:doAjaxPopup('wgo_all_blocks')">see all</a></h2>

    <div class="wrap" id="wrap-block-404s">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>Remote IP</td>
                    <td>Country</td>
                </tr>
            </thead>
            <tbody>
            <?php
            
            for ($i = 0; $i < $max_items_to_show; $i++) {
                $result = $results_blocks[$i];
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td>
                        <?= $result->remote_ip; ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($result->country_code)) {
                            echo $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '');
                        }
                        ?>
                    </td>
                </tr>

                <?php
            }
            ?>
            </tbody>
        </table>
    </div>

    <h2>Suspicious behaviours doing 404s <a href="javascript:doAjaxPopup('wgo_all_ips_404s')">all IPs</a> <a href="javascript:doAjaxPopup('wgo_all_urls_404s')">all URLs</a></h2>

    <div class="wrap" id="wrap-block-404s">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>Remote IP</td>
                    <td>Country</td>
                </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 0; $i < $max_items_to_show; $i++) {
                $result = $results_404s[$i];
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td>
                        <?= $result->remote_ip; ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($result->country_code)) {
                            echo $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '');
                        }
                        ?>
                    </td>
                </tr>

                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>