<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for blocks..
$block_sql = 'SELECT * '
.' FROM '.$wpdb->prefix.'whats_going_on_bans wgob'
.' ORDER BY time DESC';
$results = $wpdb->get_results($block_sql);

?>

<div class="wrap-last-blocked">
    <h2>Current banned IPs (<?= count($results) ?>)</h2>

    <div class="wrap" id="block-last-blocks">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Time</td>
                    <td>Time until</td>
                    <td>Remote IP</td>
                    <td>Country</td>
                    <td>Comments</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->time; ?></td>
                    <td><?= $result->time_until; ?></td>
                    <td><?= $result->remote_ip; ?></td>
                    <td>
                        <?php
                        if (!empty($result->country_code)) {
                            echo $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '');
                        }
                        ?>
                    </td>
                    <td><?= $result->comments; ?></td></td>
                </tr>

            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>