<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>

<table class="wp-list-table widefat fixed striped posts">
    <thead>
        <tr>
            <td>Times 404s</td>
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
                <?= urldecode($result->url); ?>
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
