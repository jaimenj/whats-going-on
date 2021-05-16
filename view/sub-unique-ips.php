<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-lists">
    <h2>Unique IPs</h2>

    <p>The format of IPs recorded is: HTTP_X_FORWARDED_FOR-HTTP_CLIENT_IP-REMOTE_ADDR
    If you see something like --123.123.123.123 it's because the web is not behind a proxy.<br>
    You can use . + * like in Regular Expresions to check ranges or masks of IPs, for example: 123.123.123.*.</p>

    <p>
        With this IP.. <input type="text" name="txt_this_ip" id="txt_this_ip" class="regular-text">
        <input type="submit" name="submit-remove-this-ip" id="submit-remove-this-ip" class="button button-red" value="Remove all records">
    </p>

    <div class="wrap-block-and-allow-lists">
        <div class="wrap" id="wrap-block-list">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Block list, one per line</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <textarea name="txt_block_list" id="txt_block_list" class="waf-textarea-config"><?php
                            $file_path = wp_upload_dir()['basedir'].'/wgo-things/block-list.php';
                            if (file_exists($file_path)) {
                                $the_file = file($file_path);
                                if (count($the_file) > 1) {
                                    for ($i = 1; $i < count($the_file); ++$i) {
                                        echo esc_textarea($the_file[$i]);
                                    }
                                }
                            }
                            ?></textarea>
                            <?php
                            if (empty($the_file)) {
                                echo '<p>No IPs found.</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>

        <div class="wrap" id="wrap-allow-list">
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <td>Allow list, one per line</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <textarea name="txt_allow_list" id="txt_allow_list" class="waf-textarea-config"><?php
                            $file_path = wp_upload_dir()['basedir'].'/wgo-things/allow-list.php';
                            if (file_exists($file_path)) {
                                $the_file = file($file_path);
                                if (count($the_file) > 1) {
                                    for ($i = 1; $i < count($the_file); ++$i) {
                                        echo esc_textarea($the_file[$i]);
                                    }
                                }
                            }
                            ?></textarea>
                            <?php
                            if (empty($the_file)) {
                                echo '<p>No IPs found.</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>
    </div>

    <p>
        <input type="submit" name="submit-save-ips-lists" id="submit-save-ips-lists" class="button button-red" value="Save IPs">
    </p>
</div>