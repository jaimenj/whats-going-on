<?php

defined('ABSPATH') or die('No no no');

class WhatsGoingOnDatabase
{
    private static $instance;

    // Last DB update version..
    private $current_version = 2;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->update_if_needed();
    }

    public function create_initial_tables()
    {
        global $wpdb;

        // Main table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on ('
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'method VARCHAR(8) NOT NULL,'
            .'last_minute INT NOT NULL,'
            .'last_hour INT NOT NULL'
            .');';
        $wpdb->get_results($sql);

        // Blocks table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on_block ('
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'comments VARCHAR(256)'
            .');';
        $wpdb->get_results($sql);

        // 404s table..
        $sql = 'CREATE TABLE '.$wpdb->prefix.'whats_going_on_404s ('
            .'time DATETIME NOT NULL,'
            .'url VARCHAR(256) NOT NULL,'
            .'remote_ip VARCHAR(64) NOT NULL,'
            .'remote_port INT NOT NULL,'
            .'country_code VARCHAR(2),'
            .'user_agent VARCHAR(128) NOT NULL,'
            .'method VARCHAR(8) NOT NULL'
            .');';
        $wpdb->get_results($sql);

        update_option('wgo_db_version', 1);
    }

    public function remove_tables()
    {
        global $wpdb;

        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_block;';
        $wpdb->get_results($sql);
        $sql = 'DROP TABLE '.$wpdb->prefix.'whats_going_on_404s;';
        $wpdb->get_results($sql);
    }

    public function update_if_needed()
    {
        global $wpdb;
        $db_version = get_option('wgo_db_version');

        // Updates for v2..
        if ($db_version < $this->current_version
        and 2 > $db_version) {
            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on_block '
                .'ADD COLUMN block_until DATETIME'
                .';';
            $wpdb->get_results($sql);

            ++$db_version;
        }

        update_option('wgo_db_version', $this->current_version);
    }
}
