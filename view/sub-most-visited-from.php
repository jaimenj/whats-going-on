<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for most visited from..
$sql_most_visited_from = 'SELECT count(*) as times, remote_ip, country_code '
    .'FROM '.$wpdb->prefix.'whats_going_on '
    .'GROUP BY remote_ip ORDER BY times DESC LIMIT 10';
$results = $wpdb->get_results($sql_most_visited_from);
?>

<div class="wrap-permanent-lists">
    <h2>Most visited from, top 10 of IPs and countries <a href="javascript:doAjaxPopup('wgo_all_ips_and_counters')">see all</a></h2>

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
            foreach ($results as $key => $result) {
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