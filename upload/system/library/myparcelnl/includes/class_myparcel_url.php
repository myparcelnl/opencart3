<?php

class MyParcel_Url
{
    private $_url;

    public function __construct()
    {
        $registry = MyParcel::$registry;
        $this->_url = $registry->get('url');
    }

    public function link($route, $args = '', $secure = true)
    {
        if (version_compare(VERSION, '2.2.0.0', '<')) {
            if (is_array($args)) {
                $args = '&amp;' . http_build_query($args);
            }
        }
        return $this->_url->link($route, $args, $secure);
    }
}

return new MyParcel_Url();