<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}

// Results for 404s..
$sql_urls = 'SELECT count(*) as times, url, country_code FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY url ORDER BY times DESC LIMIT 10';
$results = $wpdb->get_results($sql_urls);
$sql_urls_doing_404s = 'SELECT count(DISTINCT url) FROM '.$wpdb->prefix.'whats_going_on_404s;';
$total_urls_doing_404s = $wpdb->get_var($sql_urls_doing_404s);
?>

<div class="wrap-permanent-lists">
    <h2>Top 10 of URLs doing 404s, with a total of <?= $total_urls_doing_404s ?> IPs recorded <a href="javascript:doAjaxPopup('wgo_all_urls_404s')">see all</a></h2></h2>

    <div class="wrap" id="wrap-block-404s">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Times</td>
                    <td>URL</td>
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
                        <a href="<?= admin_url('tools.php?page=whats-going-on'); ?>&filter-url=<?= urlencode($result->url); ?>"><?= urldecode($result->url); ?>
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