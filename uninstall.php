<?php

defined('ABSPATH') or die('No no no');
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('wgojnj_limit_requests_per_minute');
delete_option('wgojnj_limit_requests_per_hour');
delete_option('wgojnj_items_per_page');
