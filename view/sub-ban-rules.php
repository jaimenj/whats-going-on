<?php
defined('ABSPATH') or exit('No no no');
if (!current_user_can('administrator')) {
    wp_die(__('Sorry, you are not allowed to manage options for this site.'));
}
?>
<div class="wrap-permanent-lists">
    <h2>Ban rules @TODO</h2>

    <p><input type="checkbox" name="honeypot_mode_enabled"> Honeypot mode (only notify by email).</p>
    
    <p><input type="checkbox" name="ban_404s_enabled"> Ban by 
    <select name="ban_404s_min" id="ban_404s_min">
        <option value="1"<?= (1 == $ban_404s_min ? ' selected' : ''); ?>>1</option>
        <option value="2"<?= (2 == $ban_404s_min ? ' selected' : ''); ?>>2</option>
        <option value="3"<?= (3 == $ban_404s_min ? ' selected' : ''); ?>>3</option>
        <option value="5"<?= (5 == $ban_404s_min ? ' selected' : ''); ?>>5</option>
        <option value="10"<?= (10 == $ban_404s_min ? ' selected' : ''); ?>>10</option>
        <option value="25"<?= (25 == $ban_404s_min ? ' selected' : ''); ?>>25</option>
        <option value="50"<?= (50 == $ban_404s_min ? ' selected' : ''); ?>>50</option>
        <option value="100"<?= (100 == $ban_404s_min ? ' selected' : ''); ?>>100</option>
        <option value="500"<?= (500 == $ban_404s_min ? ' selected' : ''); ?>>500</option>
        <option value="1000"<?= (1000 == $ban_404s_min ? ' selected' : ''); ?>>1000</option>
    </select> 
    404s if bad behaviour ir more than 
    <select name="ban_404s_percent" id="ban_404s_percent">
        <option value="0"<?= (0 == $ban_404s_percent ? ' selected' : ''); ?>>0</option>
        <option value="10"<?= (10 == $ban_404s_percent ? ' selected' : ''); ?>>10</option>
        <option value="20"<?= (20 == $ban_404s_percent ? ' selected' : ''); ?>>20</option>
        <option value="30"<?= (30 == $ban_404s_percent ? ' selected' : ''); ?>>30</option>
        <option value="40"<?= (40 == $ban_404s_percent ? ' selected' : ''); ?>>40</option>
        <option value="50"<?= (50 == $ban_404s_percent ? ' selected' : ''); ?>>50</option>
        <option value="60"<?= (60 == $ban_404s_percent ? ' selected' : ''); ?>>60</option>
        <option value="70"<?= (70 == $ban_404s_percent ? ' selected' : ''); ?>>70</option>
        <option value="80"<?= (80 == $ban_404s_percent ? ' selected' : ''); ?>>80</option>
        <option value="90"<?= (90 == $ban_404s_percent ? ' selected' : ''); ?>>90</option>
        <option value="100"<?= (100 == $ban_404s_percent ? ' selected' : ''); ?>>100</option>
    </select></p>
    
    <p><input type="checkbox" name="ban_query_string_enabled"> Ban by 
    <select name="ban_query_string_min" id="ban_query_string_min">
        <option value="1"<?= (1 == $ban_query_string_min ? ' selected' : ''); ?>>1</option>
        <option value="2"<?= (2 == $ban_query_string_min ? ' selected' : ''); ?>>2</option>
        <option value="3"<?= (3 == $ban_query_string_min ? ' selected' : ''); ?>>3</option>
        <option value="5"<?= (5 == $ban_query_string_min ? ' selected' : ''); ?>>5</option>
        <option value="10"<?= (10 == $ban_query_string_min ? ' selected' : ''); ?>>10</option>
        <option value="25"<?= (25 == $ban_query_string_min ? ' selected' : ''); ?>>25</option>
        <option value="50"<?= (50 == $ban_query_string_min ? ' selected' : ''); ?>>50</option>
        <option value="100"<?= (100 == $ban_query_string_min ? ' selected' : ''); ?>>100</option>
        <option value="500"<?= (500 == $ban_query_string_min ? ' selected' : ''); ?>>500</option>
        <option value="1000"<?= (1000 == $ban_query_string_min ? ' selected' : ''); ?>>1000</option>
    </select> 
    regex query strings if bad behaviour ir more than 
    <select name="ban_query_string_percent" id="ban_query_string_percent">
        <option value="0"<?= (0 == $ban_query_string_percent ? ' selected' : ''); ?>>0</option>
        <option value="10"<?= (10 == $ban_query_string_percent ? ' selected' : ''); ?>>10</option>
        <option value="20"<?= (20 == $ban_query_string_percent ? ' selected' : ''); ?>>20</option>
        <option value="30"<?= (30 == $ban_query_string_percent ? ' selected' : ''); ?>>30</option>
        <option value="40"<?= (40 == $ban_query_string_percent ? ' selected' : ''); ?>>40</option>
        <option value="50"<?= (50 == $ban_query_string_percent ? ' selected' : ''); ?>>50</option>
        <option value="60"<?= (60 == $ban_query_string_percent ? ' selected' : ''); ?>>60</option>
        <option value="70"<?= (70 == $ban_query_string_percent ? ' selected' : ''); ?>>70</option>
        <option value="80"<?= (80 == $ban_query_string_percent ? ' selected' : ''); ?>>80</option>
        <option value="90"<?= (90 == $ban_query_string_percent ? ' selected' : ''); ?>>90</option>
        <option value="100"<?= (100 == $ban_query_string_percent ? ' selected' : ''); ?>>100</option>
    </select></p>
    
    <p><input type="checkbox" name="ban_payload_enabled"> Ban by 
    <select name="ban_payload_min" id="ban_payload_min">
        <option value="1"<?= (1 == $ban_payload_min ? ' selected' : ''); ?>>1</option>
        <option value="2"<?= (2 == $ban_payload_min ? ' selected' : ''); ?>>2</option>
        <option value="3"<?= (3 == $ban_payload_min ? ' selected' : ''); ?>>3</option>
        <option value="5"<?= (5 == $ban_payload_min ? ' selected' : ''); ?>>5</option>
        <option value="10"<?= (10 == $ban_payload_min ? ' selected' : ''); ?>>10</option>
        <option value="25"<?= (25 == $ban_payload_min ? ' selected' : ''); ?>>25</option>
        <option value="50"<?= (50 == $ban_payload_min ? ' selected' : ''); ?>>50</option>
        <option value="100"<?= (100 == $ban_payload_min ? ' selected' : ''); ?>>100</option>
        <option value="500"<?= (500 == $ban_payload_min ? ' selected' : ''); ?>>500</option>
        <option value="1000"<?= (1000 == $ban_payload_min ? ' selected' : ''); ?>>1000</option>
    </select> regex payload if bad behaviour ir more than 
    <select name="ban_payload_percent" id="ban_payload_percent">
        <option value="0"<?= (0 == $ban_payload_percent ? ' selected' : ''); ?>>0</option>
        <option value="10"<?= (10 == $ban_payload_percent ? ' selected' : ''); ?>>10</option>
        <option value="20"<?= (20 == $ban_payload_percent ? ' selected' : ''); ?>>20</option>
        <option value="30"<?= (30 == $ban_payload_percent ? ' selected' : ''); ?>>30</option>
        <option value="40"<?= (40 == $ban_payload_percent ? ' selected' : ''); ?>>40</option>
        <option value="50"<?= (50 == $ban_payload_percent ? ' selected' : ''); ?>>50</option>
        <option value="60"<?= (60 == $ban_payload_percent ? ' selected' : ''); ?>>60</option>
        <option value="70"<?= (70 == $ban_payload_percent ? ' selected' : ''); ?>>70</option>
        <option value="80"<?= (80 == $ban_payload_percent ? ' selected' : ''); ?>>80</option>
        <option value="90"<?= (90 == $ban_payload_percent ? ' selected' : ''); ?>>90</option>
        <option value="100"<?= (100 == $ban_payload_percent ? ' selected' : ''); ?>>100</option>
    </select></p>

    <p>
        <input type="submit" name="submit-save-ban-rules" id="submit-save-ban-rules" class="button button-red" value="Save Ban Rules">
    </p>
</div>