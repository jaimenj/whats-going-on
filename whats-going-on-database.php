<?php

defined('ABSPATH') or exit('No no no');

class WhatsGoingOnDatabase
{
    private static $instance;
    private $tableNames;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->tableNames = [
            'whats_going_on',
            'whats_going_on_block',
            'whats_going_on_bans',
        ];
    }

    public function get_table_names()
    {
        return $this->tableNames;
    }

    public function create_initial_tables()
    {
        global $wpdb;
        $db_version = get_option('wgo_db_version');

        if ($db_version < 1) {
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
    }

    public function remove_all_data()
    {
        global $wpdb;

        foreach ($this->tableNames as $tableName) {
            $wpdb->get_results('TRUNCATE '.$wpdb->prefix.$tableName.';');
        }
    }

    public function remove_tables()
    {
        global $wpdb;

        foreach ($this->tableNames as $tableName) {
            $wpdb->get_results('DROP TABLE '.$wpdb->prefix.$tableName.';');
        }
    }

    public function update_if_needed()
    {
        global $wpdb;
        $db_version = get_option('wgo_db_version');

        if ($db_version < 1) {
            return;
        }

        // Updates for v2..
        if ($db_version < 2) {
            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on_block '
                .'ADD COLUMN block_until DATETIME'
                .';';
            $wpdb->get_results($sql);

            $db_version = 2;

            WhatsGoingOnMessages::get_instance()->add_message('Updated DB to v2.');
        }

        // Updates for v3..
        if ($db_version < 3) {
            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on '
                .'MODIFY COLUMN url VARCHAR(2048) NOT NULL'
                .';';
            $wpdb->get_results($sql);

            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on_block '
                .'MODIFY COLUMN url VARCHAR(2048) NOT NULL'
                .';';
            $wpdb->get_results($sql);

            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on_404s '
                .'MODIFY COLUMN url VARCHAR(2048) NOT NULL'
                .';';
            $wpdb->get_results($sql);

            $db_version = 3;

            WhatsGoingOnMessages::get_instance()->add_message('Updated DB to v3.');
        }

        // Updates for v4..
        if ($db_version < 4) {
            // Temporarily bans table..
            $sql = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'whats_going_on_bans ('
                .'time DATETIME NOT NULL,'
                .'time_until DATETIME NOT NULL,'
                .'remote_ip VARCHAR(64) NOT NULL,'
                .'country_code VARCHAR(2),'
                .'comments VARCHAR(256)'
                .');';
            $wpdb->get_results($sql);

            $db_version = 4;

            WhatsGoingOnMessages::get_instance()->add_message('Updated DB to v4.');
        }

        // Updates for v5..
        if ($db_version < 5) {
            // Drop 404s table..
            $sql = 'DROP TABLE IF EXISTS '.$wpdb->prefix.'whats_going_on_404s;';
            $wpdb->get_results($sql);

            // Drop 404s table..
            $sql = 'ALTER TABLE '.$wpdb->prefix.'whats_going_on '
                .'ADD COLUMN is_404 BOOL;';
            $wpdb->get_results($sql);

            $db_version = 5;

            WhatsGoingOnMessages::get_instance()->add_message('Updated DB to v5.');
        }

        update_option('wgo_db_version', $db_version);
    }
}
