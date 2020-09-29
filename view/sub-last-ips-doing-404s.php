<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for 404s..
$sql_404s = 'SELECT count(*) as times, remote_ip, country_code FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC LIMIT 10';
$results = $wpdb->get_results($sql_404s);
$sql_ips_doing_404s = 'SELECT count(DISTINCT remote_ip) FROM '.$wpdb->prefix.'whats_going_on_404s;';
$total_ips_doing_404s = $wpdb->get_var($sql_ips_doing_404s);
?>

<div class="wrap-permanent-lists">
    <h2>Top 10 of IPs doing 404s, with a total of <?= $total_ips_doing_404s ?> IPs recorded <a href="javascript:doAjaxPopup('wgo_all_ips_404s')">see all</a></h2></h2>

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
                        <a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-ip=<?= urlencode($result->remote_ip); ?>"><?= $result->remote_ip; ?>
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