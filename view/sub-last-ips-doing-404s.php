<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for 404s..
$sql_404s = 'SELECT count(*) as times, remote_ip, country_code FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC';
$results = $wpdb->get_results($sql_404s);
?>

<div class="wrap-permanent-lists">
    <h2>Last IPs doing 404s, with a total of <?= count($results); ?> IPs recorded</h2>

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
            $count = 0;
            foreach ($results as $key => $result) {
                $count++;
                ?>

                <tr>
                    <td><?= $result->times; ?></td>
                    <td>
                        <a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?>
                    </td>
                    <td><?= $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '') ?></td>
                </tr>

                <?php
                if($count >= 10) break;
            }
            ?>
            </tbody>
        </table>
    </div>
</div>