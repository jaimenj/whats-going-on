<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-dos">

    <h2>DoS detections</h2>

    <label for="limit_requests_per_minute">Max requests per minute</label>
    <select name="limit_requests_per_minute" id="limit_requests_per_minute">
        <option value="5"<?= (5 == $limit_requests_per_minute ? ' selected' : ''); ?>>5</option>
        <option value="10"<?= (10 == $limit_requests_per_minute ? ' selected' : ''); ?>>10</option>
        <option value="25"<?= (25 == $limit_requests_per_minute ? ' selected' : ''); ?>>25</option>
        <option value="50"<?= (50 == $limit_requests_per_minute ? ' selected' : ''); ?>>50</option>
        <option value="100"<?= (100 == $limit_requests_per_minute ? ' selected' : ''); ?>>100</option>
        <option value="200"<?= (200 == $limit_requests_per_minute ? ' selected' : ''); ?>>200</option>
        <option value="300"<?= (300 == $limit_requests_per_minute ? ' selected' : ''); ?>>300</option>
        <option value="500"<?= (500 == $limit_requests_per_minute ? ' selected' : ''); ?>>500</option>
        <option value="1000"<?= (1000 == $limit_requests_per_minute ? ' selected' : ''); ?>>1000</option>
        <option value="-1"<?= (-1 == $limit_requests_per_minute ? ' selected' : ''); ?>>Unlimited</option>
    </select>

    <label for="limit_requests_per_hour">Max requests per hour</label>
    <select name="limit_requests_per_hour" id="limit_requests_per_hour">
        <option value="50"<?= (50 == $limit_requests_per_hour ? ' selected' : ''); ?>>50</option>
        <option value="100"<?= (100 == $limit_requests_per_hour ? ' selected' : ''); ?>>100</option>
        <option value="250"<?= (250 == $limit_requests_per_hour ? ' selected' : ''); ?>>250</option>
        <option value="500"<?= (500 == $limit_requests_per_hour ? ' selected' : ''); ?>>500</option>
        <option value="1000"<?= (1000 == $limit_requests_per_hour ? ' selected' : ''); ?>>1000</option>
        <option value="2000"<?= (2000 == $limit_requests_per_hour ? ' selected' : ''); ?>>2000</option>
        <option value="3000"<?= (3000 == $limit_requests_per_hour ? ' selected' : ''); ?>>3000</option>
        <option value="5000"<?= (5000 == $limit_requests_per_hour ? ' selected' : ''); ?>>5000</option>
        <option value="10000"<?= (10000 == $limit_requests_per_hour ? ' selected' : ''); ?>>10000</option>
        <option value="-1"<?= (-1 == $limit_requests_per_hour ? ' selected' : ''); ?>>Unlimited</option>
    </select>

    <input type="submit" name="submit-dos-configs" id="submit-dos-configs" class="button button-green" value="Save DoS configs">

</div>