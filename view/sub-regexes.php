<div class="wrap-permanent-regexes">
    <h2>Administration of Regex detections</h2>

    <p>This Regular Expresions are user for detecting requests with exploits, SQL injection, XSS attacks.. searching in query strings and post data received.</p>

    <div class="wrap" id="wrap-block-regexes">
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>Block Regexes, one per line, use full Regex here like /something/ix for example</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <textarea name="txt-regexes-block" id="txt-regexes-block" class="waf-textarea-config"><?php
                        $file_path = WGOJNJ_PATH.'block-regexes.php';
                        if (file_exists($file_path)) {
                            $the_file = file($file_path);
                            if (count($the_file) > 1) {
                                for ($i = 1; $i < count($the_file); ++$i) {
                                    echo $the_file[$i];
                                }
                            }
                        }
                        ?></textarea>
                        <?php
                        if (empty($the_file)) {
                            echo '<p>No Regexes found.</p>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table> 
    </div>

    <p>
        <input type="submit" name="submit-save-regexes" id="submit-save-regexes" class="button button-red" value="Save Regexes">
    </p>
</div>