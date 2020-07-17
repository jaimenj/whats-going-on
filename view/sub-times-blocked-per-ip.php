<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

$block_sql = 'SELECT count(*) as times, remote_ip, country_code FROM '.$wpdb->prefix.'whats_going_on_block GROUP BY remote_ip ORDER BY times DESC';
$results = $wpdb->get_results($block_sql);
?>

<div class="wrap-ips-blocked">
    <h2>Times blocked per IP (<?= count($results); ?>)</h2>

    <div class="wrap" id="block-ips-blocked">
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
                    <td><?= $result->country_code.'::'.(isset($isoCountriesArray[$result->country_code]) ? $isoCountriesArray[$result->country_code] : '') ?></td>
                </tr>

            <?php
            }
            ?>
            </tbody>
        </table>
    </div>
</div>