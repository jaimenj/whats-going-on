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
        ?>

        <p>Average (A): <?= $average; ?><br>
        Standard deviation (SD): <?= $standard_deviation; ?><br>
        Variance (V): <?= $variance; ?>
        </p>
    </div>
</div>