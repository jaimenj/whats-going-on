<?php

defined('ABSPATH') or exit('No no no');

class WhatsGoingOnAjaxController
{
    private static $instance;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_wgo_show_payloads', [$this, 'wgo_show_payloads']);
        add_action('wp_ajax_wgo_main_server_processing', [$this, 'wgo_main_server_processing']);
        add_action('wp_ajax_wgo_all_ips_and_counters', [$this, 'wgo_all_ips_and_counters']);
        add_action('wp_ajax_wgo_all_ips_404s', [$this, 'wgo_all_ips_404s']);
        add_action('wp_ajax_wgo_all_urls_404s', [$this, 'wgo_all_urls_404s']);
        add_action('wp_ajax_wgo_all_blocks', [$this, 'wgo_all_blocks']);
        add_action('wp_ajax_wgo_main_chart', [$this, 'wgo_main_chart']);
    }

    // Main Datatables server processing..
    public function wgo_main_server_processing()
    {
        // Request data to the DB..
        global $wpdb;

        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        } else {
            if ('--127.0.0.1' != WhatsGoingOn::get_instance()->current_remote_ips()) {
                // Remove administrator IP from records to prevent auto-blocking..
                $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on '
                    ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
                $results = $wpdb->get_results($sql);
                $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_block '
                    ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
                $results = $wpdb->get_results($sql);
                $sql = 'DELETE FROM '.$wpdb->prefix.'whats_going_on_404s '
                    ."WHERE remote_ip = '".WhatsGoingOn::get_instance()->current_remote_ips()."';";
                $results = $wpdb->get_results($sql);
            }
        }

        // Main query..
        $sql = 'SELECT * ';
        $sql_filtered = 'SELECT count(*) ';
        $from_sentence = ' FROM '.$wpdb->prefix.'whats_going_on ';
        $sql .= $from_sentence;
        $sql_filtered .= $from_sentence;

        // Where filtering..
        $where_clauses_or = [];
        $where_clauses_and = [];
        // ..main search..
        if (!empty($_POST['search']['value'])) {
            foreach ($_POST['columns'] as $column) {
                if (in_array($column['name'], ['remote_port', 'last_minute', 'last_hour'])) {
                    if (is_numeric($_POST['search']['value'])) {
                        $where_clauses_or[] = sanitize_text_field($column['name']).' = '.floatval($_POST['search']['value']);
                    }
                } elseif (in_array($column['name'], ['url'])) {
                    $where_clauses_or[] = sanitize_text_field($column['name'])." LIKE '%".urlencode(sanitize_text_field($_POST['search']['value']))."%'";
                } else {
                    $where_clauses_or[] = sanitize_text_field($column['name'])." LIKE '%".sanitize_text_field($_POST['search']['value'])."%'";
                }
            }
        }
        // ..column search..
        foreach ($_POST['columns'] as $column) {
            if (!empty($column['search']['value'])) {
                if (in_array($column['name'], ['remote_port', 'last_minute', 'last_hour'])) {
                    if (is_numeric($column['search']['value'])) {
                        $where_clauses_and[] = sanitize_text_field($column['name']).' = '.floatval($column['search']['value']);
                    }
                } elseif (in_array($column['name'], ['url'])) {
                    $where_clauses_and[] = sanitize_text_field($column['name'])." LIKE '%".urlencode(sanitize_text_field($column['search']['value']))."%'";
                } else {
                    $where_clauses_and[] = sanitize_text_field($column['name'])." LIKE '%".sanitize_text_field($column['search']['value'])."%'";
                }
            }
        }

        // Ordering data..
        $order_by_clauses = [];
        if (!empty($_POST['order'])) {
            foreach ($_POST['order'] as $order) {
                $order_by_clauses[] = sanitize_text_field($_POST['columns'][$order['column']]['name']).' '.sanitize_text_field($order['dir']);
            }
        }

        // Main results..
        $where_filtered = implode(' AND ', $where_clauses_and);
        if (empty($where_filtered)) {
            if (!empty($where_clauses_or)) {
                $where_filtered = implode(' OR ', $where_clauses_or);
            }
        } else {
            if (!empty($where_clauses_or)) {
                $where_filtered .= ' AND ('.implode(' OR ', $where_clauses_or).')';
            }
        }
        if (!empty($where_filtered)) {
            $sql .= 'WHERE '.$where_filtered;
            $sql_filtered .= 'WHERE '.$where_filtered;
        }
        if (!empty($order_by_clauses)) {
            $sql .= ' ORDER BY '.implode(', ', $order_by_clauses);
        }
        $sql .= ' LIMIT '.intval($_POST['length']).' OFFSET '.intval($_POST['start']);
        $results = $wpdb->get_results($sql);

        // Totals..
        $sql_total = 'SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on ';
        $records_total = $wpdb->get_var($sql_total);
        $records_total_filtered = $wpdb->get_var($sql_filtered);

        // Return data..
        $data = [];
        foreach ($results as $key => $value) {
            //var_dump($key); var_dump($value);
            $tempItem = [];
            foreach ($value as $valueKey => $valueValue) {
                if ('url' == $valueKey) {
                    $tempItem[] = urldecode($valueValue);
                } else {
                    $tempItem[] = $valueValue;
                }
            }
            $data[] = $tempItem;
        }
        header('Content-type: application/json');
        echo json_encode([
            'draw' => intval($_POST['draw']),
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_total_filtered,
            'data' => $data,
            'sql' => $sql,
            'sqlFiltered' => $sql_filtered,
        ]);

        wp_die();
    }

    public function wgo_all_ips_and_counters()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGO_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        $sql_most_visited_from = 'SELECT count(*) as times, remote_ip, country_code '
            .'FROM '.$wpdb->prefix.'whats_going_on '
            .'GROUP BY remote_ip ORDER BY times DESC';
        $results = $wpdb->get_results($sql_most_visited_from);

        // Paints the view..
        include WGO_PATH.'view/ajax-most-visited-from.php';

        wp_die();
    }

    public function wgo_all_ips_404s()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGO_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for 404s..
        $sql_404s = 'SELECT count(*) as times, remote_ip, country_code '
            .'FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY remote_ip ORDER BY times DESC';
        $results = $wpdb->get_results($sql_404s);
        $sql_ips_doing_404s = 'SELECT count(DISTINCT remote_ip) FROM '.$wpdb->prefix.'whats_going_on_404s;';
        $total_ips_doing_404s = $wpdb->get_var($sql_ips_doing_404s);

        // Paints the view..
        include WGO_PATH.'view/ajax-last-ips-doing-404s.php';

        wp_die();
    }

    public function wgo_all_urls_404s()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGO_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for 404s..
        $sql_urls = 'SELECT count(*) as times, url, country_code FROM '.$wpdb->prefix.'whats_going_on_404s GROUP BY url ORDER BY times DESC';
        $results = $wpdb->get_results($sql_urls);
        $sql_urls_doing_404s = 'SELECT count(DISTINCT url) FROM '.$wpdb->prefix.'whats_going_on_404s;';
        $total_urls_doing_404s = $wpdb->get_var($sql_urls_doing_404s);

        // Paints the view..
        include WGO_PATH.'view/ajax-last-urls-doing-404s.php';

        wp_die();
    }

    public function wgo_all_blocks()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        global $wpdb;

        // GEOIP
        $isoCountriesFile = file(WGO_PATH.'lib/isoCountriesCodes.csv');
        $isoCountriesArray = [];
        foreach ($isoCountriesFile as $isoItem) {
            $isoCountriesArray[explode(',', $isoItem)[0]] = str_replace(['"', PHP_EOL], '', explode(',', $isoItem)[1]);
        }

        // Results for blocks..
        $block_sql = 'SELECT * '
            .' FROM '.$wpdb->prefix.'whats_going_on_block wgob'
            .' ORDER BY time DESC';
        $results = $wpdb->get_results($block_sql);

        // Total blocks
        $total_blocks = $wpdb->get_var('SELECT count(*) FROM '.$wpdb->prefix.'whats_going_on_block');

        // Paints the view..
        include WGO_PATH.'view/ajax-last-blocks.php';

        wp_die();
    }

    public function wgo_show_payloads()
    {
        if (!current_user_can('administrator')) {
            wp_die(__('Sorry, you are not allowed to manage options for this site.'));
        }

        if (file_exists(wp_upload_dir()['basedir'].'/wgo-things/waf-payloads.log')) {
            $waf_payloads_log = file_get_contents(wp_upload_dir()['basedir'].'/wgo-things/waf-payloads.log');
            $waf_payloads_log = str_replace(PHP_EOL, '<br>', $waf_payloads_log);
        } else {
            $waf_payloads_log = 'EMPTY FILE';
        }

        echo '<pre>'.$waf_payloads_log.'</pre>';

        wp_die();
    }

    public function wgo_main_chart()
    {
        include WGO_PATH.'view/sub-main-chart.php';

        wp_die();
    }
}
