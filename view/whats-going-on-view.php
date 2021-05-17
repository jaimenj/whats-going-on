<style>hr{margin-top: 30px;}</style>
<?php

defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
} else {
    // Remove administrator IP from records to prevent auto-blocking..
    if ('--127.0.0.1' != WhatsGoingOn::get_instance()->current_remote_ips()) {
        foreach (WhatsGoingOnDatabase::get_instance()->get_table_names() as $tableName) {
            $sql = 'DELETE FROM '.$wpdb->prefix.$tableName.' '
                ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
            $wpdb->get_results($sql);
        }
    }
}

/*
 * Listing registers..
 */
global $wpdb;
$total_sql = 'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on';
$maxs_reached_sql = 'SELECT max(last_minute) max_hits_minute_reached, max(last_hour) max_hits_hour_reached FROM '.$wpdb->prefix.'whats_going_on';

$add_sql = '';
if (isset($_GET['filter-url'])) {
    $add_sql .= " WHERE url = '".sanitize_text_field($_GET['filter-url'])."'";
} elseif (isset($_GET['filter-ip'])) {
    $add_sql .= " WHERE remote_ip = '".sanitize_text_field($_GET['filter-ip'])."'";
} elseif (isset($_GET['filter-method'])) {
    $add_sql .= " WHERE method = '".sanitize_text_field($_GET['filter-method'])."'";
}
$total_sql .= $add_sql;
$maxs_reached_sql .= $add_sql;

$total_registers = $wpdb->get_var($total_sql);

$maxs_reached = $wpdb->get_results(
    $maxs_reached_sql
);

// Current total blocks
$total_blocks = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on_block');
$total_block_ips = $wpdb->get_var('SELECT count(DISTINCT remote_ip) FROM '.$wpdb->prefix.'whats_going_on_block');
?>

<form method="post" enctype="multipart/form-data" action="<?php
//echo admin_url('tools.php?page=whats-going-on');
echo $_SERVER['REQUEST_URI'];
?>"
id="wgo_form" 
name="wgo_form"
data-wgo_ajax_url="<?= admin_url('admin-ajax.php') ?>">

    <div class="wrap">
        <span style="float: right">
            Support the project, please donate <a href="https://paypal.me/jaimeninoles" target="_blank"><b>here</b></a>.<br>
            Need help? Ask <a href="https://jnjsite.com/whats-going-on-for-wordpress/" target="_blank"><b>here</b></a>.
        </span>

        <h1><span class="dashicons dashicons-shield-alt wgo-icon"></span> What's going on, a simple firewall</h1>
        
        <?php
        if (isset($wgoSms)) {
            echo $wgoSms;
        }

        foreach(WhatsGoingOnMessages::get_instance()->get_messages() as $message) {
            echo '<div id="message" class="notice notice-success is-dismissible"><p>'.$message.'</p></div>';
        }
        ?>

        <div class="wgo-box-main-chart">
            <?php include WGO_PATH.'view/sub-main-chart.php'; ?>
        </div>

        <?php settings_fields('wgo_options_group'); ?>
        <?php do_settings_sections('wgo_options_group'); ?>

        <?php wp_nonce_field('wgojnj', 'wgo_nonce'); ?>

        <p>
            <input type="submit" name="btn-submit" id="btn-submit" class="button button-green" value="Save this configs">

            <label for="autoreload_datatables">Auto-reload</label>
            <select name="autoreload_datatables" id="autoreload_datatables">
                <option value="-1"<?= (-1 == $autoreload_datatables ? ' selected' : ''); ?>>No</option>
                <option value="5"<?= (5 == $autoreload_datatables ? ' selected' : ''); ?>>5s</option>
                <option value="10"<?= (10 == $autoreload_datatables ? ' selected' : ''); ?>>10s</option>
                <option value="30"<?= (30 == $autoreload_datatables ? ' selected' : ''); ?>>30s</option>
                <option value="60"<?= (60 == $autoreload_datatables ? ' selected' : ''); ?>>60s</option>
                <option value="120"<?= (120 == $autoreload_datatables ? ' selected' : ''); ?>>120s</option>
            </select>

            <label for="days_to_store">Days to store</label>
            <select name="days_to_store" id="days_to_store">
                <option value="1"<?= (1 == $days_to_store ? ' selected' : ''); ?>>1</option>
                <option value="2"<?= (2 == $days_to_store ? ' selected' : ''); ?>>2</option>
                <option value="3"<?= (3 == $days_to_store ? ' selected' : ''); ?>>3</option>
                <option value="7"<?= (7 == $days_to_store ? ' selected' : ''); ?>>7</option>
                <option value="14"<?= (14 == $days_to_store ? ' selected' : ''); ?>>14</option>
                <option value="28"<?= (28 == $days_to_store ? ' selected' : ''); ?>>28</option>
            </select>

            <label for="im_behind_proxy">The website is behind a proxy</label>
            <select name="im_behind_proxy" id="im_behind_proxy">
                <option value="0"<?= (0 == $im_behind_proxy ? ' selected' : ''); ?>>No</option>
                <option value="1"<?= (1 == $im_behind_proxy ? ' selected' : ''); ?>>Yes</option>
            </select>

            <label for="notification_email">Notification email</label>
            <input type="text" name="notification_email" id="notification_email" class="regular-text" value="<?= $notification_email; ?>">
            <input type="submit" name="submit-check-email" id="submit-check-email" class="button button-green" value="Check email">
        </p>

        <div class="table-responsive" id="wgo-datatable-container">
            <table 
            class="records_list table table-striped table-bordered table-hover" 
            id="wgo-datatable" 
            width="100%">
                <thead>
                    <tr>
                        <td>Time</td>
                        <td>URL method</td>
                        <td>Remote IP</td>
                        <td>Port</td>
                        <td>Country</td>
                        <td>User Agent</td>
                        <td>Method</td>
                        <td>Hits minute (max <?= $maxs_reached[0]->max_hits_minute_reached; ?>)</td>
                        <td>Hits hour (max <?= $maxs_reached[0]->max_hits_hour_reached; ?>)</td>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                        <th>Filter..</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p>
            <input type="submit" name="submit-remove-old" id="submit-remove-old" class="button button-primary" value="Remove records of more than one week">
            <input type="submit" name="submit-remove-all" id="submit-remove-all" class="button" value="Remove all records">
            
            <span class="span-install">
                <?php
                if (!WhatsGoingOn::get_instance()->is_waf_installed()) {
                    ?>
                    <input type="submit" name="submit-install-full-waf" id="submit-install-full-waf" class="button" value="Install .user.ini">
                <?php
                } else {
                    ?>
                    <input type="submit" name="submit-uninstall-full-waf" id="submit-uninstall-full-waf" class="button" value="Uninstall .user.ini">
                <?php
                }
                ?>
            </span>

        </p>
        
    </div>

    <hr>

    <h2>Administration Zone!</h2> 
    <button type="button" class="button button-green" id="wgo-btn-show-ban-rules">Ban rules</button>
    <button type="button" class="button button-green" id="wgo-btn-show-ips">IPs</button>
    <button type="button" class="button button-green" id="wgo-btn-show-dos-and-ddos">DoS and DDoS</button>
    <button type="button" class="button button-green" id="wgo-btn-show-regexes">Regexes</button>
    <button type="button" class="button button-green" id="wgo-btn-show-countries">Countries</button>
    <button type="button" class="button" id="wgo-btn-show-last-blocks">Last blocks</button>
    <button type="button" class="button" id="wgo-btn-show-suspicious-behaviors">Suspicious behaviors</button>

    <div class="wgo-box wgo-box-ban-rules wgo-d-none">
        <?php include WGO_PATH.'view/sub-ban-rules.php'; ?>
    </div>
    <div class="wgo-box wgo-box-ips wgo-d-none">
        <?php include WGO_PATH.'view/sub-unique-ips.php'; ?>
    </div>
    <div class="wgo-box wgo-box-dos-and-ddos wgo-d-none">
        <?php include WGO_PATH.'view/sub-dos.php'; ?>
        <?php include WGO_PATH.'view/sub-ddos.php'; ?>
    </div>
    <div class="wgo-box wgo-box-regexes wgo-d-none">
        <?php include WGO_PATH.'view/sub-regexes.php'; ?>
        <?php include WGO_PATH.'view/sub-regexes-errors.php'; ?>
    </div>
    <div class="wgo-box wgo-box-countries wgo-d-none">
        <?php include WGO_PATH.'view/sub-countries-continents.php'; ?>
        <?php include WGO_PATH.'view/sub-most-visited-from.php'; ?>
    </div>
    <div class="wgo-box wgo-box-last-blocks wgo-d-none">
        <?php include WGO_PATH.'view/sub-last-blocks.php'; ?>
    </div>
    <div class="wgo-box wgo-box-suspicious-behaviors wgo-d-none">
        <?php include WGO_PATH.'view/sub-suspicious-behaviors.php'; ?>
    </div>

</form>
<hr>

<p>This plugin includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" target="_blank">https://www.maxmind.com</a>.</p>

<?php include WGO_PATH.'view/sub-popup-info.php'; ?>

<script>
    let weAreInWhatsGoingOn = true
</script>