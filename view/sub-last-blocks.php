<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for blocks..
$block_sql = 'SELECT max(wgob.time) time, count(*) times, wgob.remote_ip, wgob.country_code, wgob.remote_port, wgob.user_agent, wgob.comments '
.' FROM '.$wpdb->prefix.'whats_going_on_block wgob'
.' GROUP BY remote_ip ORDER BY time DESC';
$results = $wpdb->get_results($block_sql);

// Total blocks
$total_blocks = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on_block');
?>

<div class="wrap-last-blocked">
    <h2>Last blocks reasons and times blocked, <?= $total_blocks ?> total blocks, with a total of <?= count($results); ?> IPs recorded <a href="javascript:showAllBlocks()">see all</a></h2>

    <div class="wrap" id="block-last-blocks">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Last time</td>
                    <td>Times blocked</td>
                    <td>Remote IP</td>
                    <td>Country</td>
                    <td>User Agent</td>
                    <td>Comments</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->time; ?></td>
                    <td><?= $result->times; ?></td>
                    <td>
                        <a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?></a>
                    </td>
                    <td>
                        <?php
                        if (!empty($result->country_code)) {
                            echo $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '');
                        }
                        ?>
                    </td>
                    <td><?= $result->user_agent; ?></td></td>
                    <td><?= $result->comments; ?></td></td>
                </tr>

            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>