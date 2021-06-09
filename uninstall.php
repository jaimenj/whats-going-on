<?php

defined('ABSPATH') or exit('No no no');
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

include_once 'whats-going-on.php';

WhatsGoingOn::get_instance()->uninstall();
