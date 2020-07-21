<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-ddos">
    <h2>Administration of DDoS detections</h2>
    
    <div class="wrap">
        <?php
        /**
         * 500 errors
         * TTL or processor usage
         * spikes in traffic.
         */
        $notify_requests_more_than_sd = get_option('wgojnj_notify_requests_more_than_sd');
        $notify_requests_more_than_2sd = get_option('wgojnj_notify_requests_more_than_2sd');
        $notify_requests_more_than_3sd = get_option('wgojnj_notify_requests_more_than_3sd');
        ?>

        <p>Average (A): <?= $average; ?> - Standard deviation (SD): <?= $standard_deviation; ?> - Variance (V): <?= $variance; ?></p>

        <label for="notify_requests_more_than_sd">Notify by email if requests are gone out of A+-SD</label>
        <select name="notify_requests_more_than_sd" id="notify_requests_more_than_sd">
            <option value="0"<?= (0 == $notify_requests_more_than_sd ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $notify_requests_more_than_sd ? ' selected' : ''); ?>>Yes</option>
        </select>

        <label for="notify_requests_more_than_2sd">or out of A+-2SD</label>
        <select name="notify_requests_more_than_2sd" id="notify_requests_more_than_2sd">
            <option value="0"<?= (0 == $notify_requests_more_than_2sd ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $notify_requests_more_than_2sd ? ' selected' : ''); ?>>Yes</option>
        </select>

        <label for="notify_requests_more_than_3sd">or out of A+-3SD</label>
        <select name="notify_requests_more_than_3sd" id="notify_requests_more_than_3sd">
            <option value="0"<?= (0 == $notify_requests_more_than_3sd ? ' selected' : ''); ?>>No</option>
            <option value="1"<?= (1 == $notify_requests_more_than_3sd ? ' selected' : ''); ?>>Yes</option>
        </select>

        <input type="submit" name="submit-ddos-configs" id="submit-ddos-configs" class="button button-green" value="Save DDoS configs">
    </div>
</div>