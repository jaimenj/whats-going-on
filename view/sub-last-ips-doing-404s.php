<?php
// Results for 404s..
$sql_404s = 'SELECT count(*) as times, remote_ip FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC';
$results = $wpdb->get_results($sql_404s);
?>

<div class="wrap-permanent-lists">
    <h2>Last IPs doing 404s (<?= count($results); ?>)</h2>

    <div class="wrap" id="wrap-block-404s">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>Remote IP</td>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $key => $result) {
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td><a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?><br>
                    <?php
                        wgojnj_print_countries($result->remote_ip, $reader); ?></a></td>
                </tr>

                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>