<?php

defined('ABSPATH') or die('No no no');
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option('wgojnj_limit_requests_per_minute');
delete_option('wgojnj_limit_requests_per_hour');
delete_option('wgojnj_items_per_page');
delete_option('wgojnj_days_to_store');
delete_option('wgojnj_im_behind_proxy');
delete_option('wgojnj_notification_email');

if (file_exists(ABSPATH.'.user.ini')) {
    unlink(ABSPATH.'.user.ini');
}