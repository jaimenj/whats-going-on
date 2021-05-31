<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-lists">
    <h2>Ban rules</h2>

    <div class="wrap" id="wrap-block-rules">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Ban Rules, one per line, use this format: &lt;criteria&gt; =&gt; &lt;timeToBanInSeconds&gt;</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        $file_path = wp_upload_dir()['basedir'].'/wgo-things/ban-rules.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);

                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $i.'.- '.$the_file[$i].'<br>';
                                }
                                echo '<input type="submit" name="wgo-submit-download-ban-rules" id="wgo-submit-download-ban-rules" class="button-download-ban-rules" value="Download ban rules">';
                            } else {
                                echo '<p>No ban rules found.</p>';
                            }
                        } else {
                            echo '<p>No Regexes found.</p>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="file" name="file_ban_rules" id="file_ban_rules">
        <input type="submit" name="submit-save-ban-rules" id="submit-save-ban-rules" class="button button-green" value="Upload ban rules">
        <input type="submit" name="submit-set-default-ban-rules" id="submit-set-default-ban-rules" class="button button-green" value="Set default ban rules">
    </div>

</div>