<?php

defined('ABSPATH') or die('No no no');

class WhatsGoingOnMessages
{
    private static $instance;
    private $messages;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->messages = [];
    }

    public function get_messages()
    {
        return $this->messages;
    }

    public function add_message($new)
    {
        $this->messages[] = $new;
    }
}
