<div class="wrap-permanent-lists">
    <h2>Administration of unique IPs</h2>

    <p>The format of IPs is: HTTP_X_FORWARDED_FOR-HTTP_CLIENT_IP-REMOTE_ADDR
    If you see something like --127.0.0.1 it's because the web is not behind a proxy.
    You can use a Regular Expresions to check IPs.</p>

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
                            <textarea name="txt-block-list" id="txt-block-list" class="waf-textarea-config"><?php
                            $file_path = WGOJNJ_PATH.'block-list.php';
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
                            <textarea name="txt-allow-list" id="txt-allow-list" class="waf-textarea-config"><?php
                            $file_path = WGOJNJ_PATH.'allow-list.php';
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