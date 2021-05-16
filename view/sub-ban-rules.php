<?php
defined('ABSPATH') or die('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-lists">
    <h2>Ban rules</h2>

    <p>TODO..</p>

    

    <p>
        <input type="submit" name="submit-save-ban-rules" id="submit-save-ban-rules" class="button button-red" value="Save Ban Rules">
    </p>
</div>