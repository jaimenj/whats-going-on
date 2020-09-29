<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-regexes">
    <h2>Administration of Regex detections</h2>

    <p>This Regular Expresions are used for detecting requests with exploits, SQL injection, XSS attacks.. searching in full request uri and post data received. To upload your Regexes use text files with one Regex per line.</p>

    <div class="wrap" id="wrap-block-regexes">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Block Regexes <strong>only for request uri</strong>, one per line, use full Regex here like /something/ix for example</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        $file_path = wp_upload_dir()['basedir'].'/wgo-things/block-regexes-uri.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);

                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $i.'.- '.$the_file[$i].'<br>';
                                }
                                echo '<input type="submit" name="wgo-submit-download-regexes-uri" id="wgo-submit-download-regexes-uri" class="button-download-regexes" value="Download these Regexes">';
                            } else {
                                echo '<p>No Regexes found.</p>';
                            }
                        } else {
                            echo '<p>No Regexes found.</p>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="file" name="file_regexes_uri" id="file_regexes_uri">
        <input type="submit" name="submit-save-regexes-uri" id="submit-save-regexes-uri" class="button button-green" value="Upload Regexes signatures only for request uris">
        <input type="submit" name="submit-set-default-regexes-uri" id="submit-set-default-regexes-uri" class="button button-green" value="Set default Regexes for request uris">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Block Regexes <strong>only for payload data</strong>, one per line, use full Regex here like /something/ix for example</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        $file_path = wp_upload_dir()['basedir'].'/wgo-things/block-regexes-payload.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);

                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $i.'.- '.$the_file[$i].'<br>';
                                }
                                echo '<input type="submit" name="wgo-submit-download-regexes-payload" id="wgo-submit-download-regexes-payload" class="button-download-regexes" value="Download these Regexes">';
                            } else {
                                echo '<p>No Regexes found.</p>';
                            }
                        } else {
                            echo '<p>No Regexes found.</p>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="file" name="file_regexes_payload" id="file_regexes_payload">
        <input type="submit" name="submit-save-regexes-payload" id="submit-save-regexes-payload" class="button button-green" value="Upload Regexes signatures only for payloads">
        <input type="submit" name="submit-set-default-regexes-payload" id="submit-set-default-regexes-payload" class="button button-green" value="Set default Regexes for payloads">
    </div>

    <p>
        <a href="javascript:doAjaxPopup('wgo_show_payloads')" class="button button-primary">Show payloads log</a>
        <input type="submit" name="submit-truncate-payloads-log" id="submit-truncate-payloads-log" class="button button-red" value="Truncate payloads log (current <?php
        if (file_exists(wp_upload_dir()['basedir'].'/wgo-things/waf-payloads.log')) {
            echo number_format(filesize(wp_upload_dir()['basedir'].'/wgo-things/waf-payloads.log') / 1024, 2);
        } else {
            echo '0';
        }
        ?> KB)">
        <label for="save_payloads">Save payloads (be careful)</label>
        <select name="save_payloads" id="save_payloads">
            <option value="0"<?= (0 == $save_payloads ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $save_payloads ? ' selected' : ''); ?>>Yes</option>
        </select>
        <label for="save_payloads_matching_uri_regex">..when matching a URI regex</label>
        <select name="save_payloads_matching_uri_regex" id="save_payloads_matching_uri_regex">
            <option value="0"<?= (0 == $save_payloads_matching_uri_regex ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $save_payloads_matching_uri_regex ? ' selected' : ''); ?>>Yes</option>
        </select>
        <label for="save_payloads_matching_payload_regex">..when matching a payload regex</label>
        <select name="save_payloads_matching_payload_regex" id="save_payloads_matching_payload_regex">
            <option value="0"<?= (0 == $save_payloads_matching_payload_regex ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $save_payloads_matching_payload_regex ? ' selected' : ''); ?>>Yes</option>
        </select>
        <input type="submit" name="submit-regexes-configs" id="submit-regexes-configs" class="button button-green" value="Save Regexes configs">
    </p>

</div>