<?php
// Results for blocks..
$block_sql = 'SELECT max(wgob.time) time, wgob.remote_ip, wgob.remote_port, wgob.user_agent, wgob.comments '
.' FROM '.$wpdb->prefix.'whats_going_on_block wgob'
.' GROUP BY remote_ip ORDER BY time DESC';
$results = $wpdb->get_results($block_sql);
?>

<div class="wrap-last-blocked">
    <h2>Last blocks (<?= count($results); ?>)</h2>

    <div class="wrap" id="block-last-blocks">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Time</td>
                    <td>Remote IP</td>
                    <td>Remote Port</td>
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
                    <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                    <?php
                        wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                    <td><?= $result->remote_port; ?></td></td>
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