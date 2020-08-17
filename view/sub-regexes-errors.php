<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-regexes">
    <h2>Regexes errors to review</h2>

    <div class="wrap" id="wrap-block-regexes">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>The preg_match function to check the incoming data failed because of these Regexes</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        if (file_exists(wp_upload_dir()['basedir'].'/wgo-things/waf-errors.log')) {
                            $regexesErrors = file(wp_upload_dir()['basedir'].'/wgo-things/waf-errors.log');

                            if (!empty($regexesErrors)) {
                                ?>
                                <ul>
                                    <?php
                                    foreach ($regexesErrors as $regexError) {
                                        echo '<li>'.$regexError.'</li>';
                                    } ?>
                                </ul>
                                <input type="submit" name="submit-remove-regexes-errors" id="submit-remove-regexes-errors" class="button button-green" value="Remove regexes errors">
                                <?php
                            } else {
                                ?><p>There are no errors in Regexes saved.</p><?php
                            }
                        } else {
                            ?><p>There are no errors in Regexes saved.</p><?php
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>