<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-regexes">
    <h2>Administration of Regex detections</h2>

    <p>This Regular Expresions are used for detecting requests with exploits, SQL injection, XSS attacks.. searching in full request uri and post data received.</p>

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
                        $file_path = WGOJNJ_PATH.'block-regexes-uri.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);

                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $the_file[$i].'<br>';
                                }
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
        <input type="file" name="file-regexes-uri" id="file-regexes-uri">
        <input type="submit" name="submit-save-regexes-uri" id="submit-save-regexes-uri" class="button button-green" value="Upload Regexes signatures only for request uri">
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
                        $file_path = WGOJNJ_PATH.'block-regexes-payload.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);

                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $the_file[$i].'<br>';
                                }
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
        <input type="file" name="file-regexes-payload" id="file-regexes-payload">
        <input type="submit" name="submit-save-regexes-payload" id="submit-save-regexes-payload" class="button button-green" value="Upload Regexes signatures only for payload">
    </div>

</div>