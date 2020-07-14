<?php
class MyParcel
{
    /**
     * @var MyParcel_Helper $helper
     */
    protected static $_instance = null;
    public static $registry;
    public $session;
    public $notice;
    public $api;
    public $view;
    public $helper;
    public $log;
    public $settings;
    public $lang;

    const PACKAGE_TYPE_STANDARD = 1;
    const PACKAGE_TYPE_MAILBOX = 2;
    const PACKAGE_TYPE_LETTER = 3;
    const PACKAGE_TYPE_DIGITAL_STAMP = 4;
    /**
     * MyParcel constructor.
     * @param null $registry
     * @throws Exception
     */
    private function __construct($registry = null)
    {
        if (is_null(self::$registry )) {

            if (empty($registry)) {
                throw new Exception('Registry was not declared for MyParcel.');
            }

            self::$registry = $registry;

            if (empty($this->lang)) {
                $this->lang = $registry->get('language');
                //prevent overriding heading_title
                $this->loadMyparcelLang($this->lang);
            }
            $this->shipment = require_once (dirname(__FILE__) . '/includes/class_myparcel_shipment.php');
            $this->notice = require_once (dirname(__FILE__) . '/includes/class_myparcel_notice.php');
            $this->session = require_once (dirname(__FILE__) . '/includes/class_myparcel_session.php');
            $this->view = require_once (dirname(__FILE__) . '/includes/class_myparcel_view.php');
            $this->helper = require_once (dirname(__FILE__) . '/includes/class_myparcel_helper.php');
            $this->log = require_once (dirname(__FILE__) . '/includes/class_myparcel_log.php');
            $this->settings = require_once (dirname(__FILE__) . '/includes/class_myparcel_setting.php');
            $this->api = require_once (dirname(__FILE__) . '/includes/class_myparcel_api.php');
            $this->url = require_once (dirname(__FILE__) . '/includes/class_myparcel_url.php');
        }
    }

    public static function instance($registry = null)
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self($registry);
        }
        return self::$_instance;
    }

    function getMyparcelModulePath($action = null)
    {
        return $this->getModulePath() . 'myparcelnl' . ($action ? ('/' . $action) : '');
    }

    function getMyparcelControllerPath($controller, $action = null)
    {
        return $this->getModulePath() . $controller . ($action ? ('/' . $action) : '');
    }

    function getModulePath()
    {
        if(version_compare(VERSION, '2.3.0.0', '>=')) {
            return 'extension/module/';
        } else {
            return 'module/';
        }
    }

    function getMyparcelXtensionViewPath(){
        return 'extension/myparcelnl/';
    }

    function getMyparcelXtensionControllerPath(){
        return 'extension/myparcelnl/myparcel_xtension_delivery';
    }

    function getModuleListPath()
    {
        if(version_compare(VERSION, '3.0.0.0', '>=')) {
            return 'marketplace/extension';
        } else if(version_compare(VERSION, '2.3.0.0', '>=')) {
            return 'extension/extension';
        } else {
            return 'extension/module';
        }
    }

    function getTotalPath()
    {
        if(version_compare(VERSION, '2.3.0.0', '>=')) {
            return 'extension/total/';
        } else {
            return 'total/';
        }
    }

    function getShippingPath()
    {
        if(version_compare(VERSION, '2.3.0.0', '>=')) {
            return 'extension/shipping/';
        } else {
            return 'shipping/';
        }
    }

    function getModelPath($suffix)
    {
        if(version_compare(VERSION, '3.0.0.0', '>=')) {
            return 'extension/myparcelnl' . ($suffix ? ('/' . $suffix ) : '/');
        } else {
            return 'myparcelnl' . ($suffix ? ('/' . $suffix ) : '/');
        }
    }

    function getRootUrl()
    {
        return MyParcel()->helper->trailingslashit(str_replace('/' . $this->getAdminFolderName(), '', $this->getHomeUrl()));
    }

    function getHomeUrl()
    {
        $registry = self::$registry;
        $config = $registry->get('config');
        $url = $config->get('config_url');
        $ssl = $config->get('config_ssl');

        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            return $this->helper->trailingslashit($ssl ? $ssl : HTTPS_SERVER);
        }
        return $this->helper->trailingslashit($url ? $url : HTTP_SERVER);
    }

    function getImageUrl()
    {
        return $this->getRootUrl() . 'system/library/myparcelnl/assets/img/';
    }

    function getJsUrl()
    {
        return $this->getRootUrl() . 'system/library/myparcelnl/assets/js/';
    }

    function getCssUrl()
    {
        return $this->getRootUrl() . 'system/library/myparcelnl/assets/css/';
    }

    function getPluginUrl()
    {
        return $this->getHomeUrl() . $this->getAdminFolderName() . '/index.php?route=' . $this->getModulePath() . '/';
    }

    function getRouteUrl($route)
    {
        return $this->getHomeUrl() . $this->getAdminFolderName() . '/index.php?route=' . $route . '/';
    }

    function getLogsUrl($filename = 'myparcel_log.txt')
    {
        return $this->getRootUrl() . 'system/library/myparcelnl/logs/' . $filename;
    }

    function getCoreDir()
    {
        /** @var MyParcel_Helper $helper **/
        $helper = $this->helper;
        return $helper->trailingslashit(DIR_SYSTEM) . '/library/myparcelnl/';
    }

    function getRootDir()
    {
        $root_dir = str_replace('/' . $this->getAdminFolderName(), '', DIR_APPLICATION);
        /** @var MyParcel_Helper $helper **/
        $helper = $this->helper;
        return $helper->trailingslashit($root_dir);
    }

    function getAdminFolderName()
    {
        $admin_folder = MyParcel()->settings->general->admin_folder;

        if ($admin_folder) {
            return MyParcel()->settings->general->admin_folder;
        }
        return 'admin';
    }

    function getViewDir($view = null)
    {
        return $this->getCoreDir() . 'views/' . ($view ? ($view . '.php') : '');
    }

    function getLogsDir($file = 'myparcel_log.txt')
    {
        return $this->getCoreDir() . 'logs/' . ($file ? ($file) : '');
    }

    function addCompatibleScript($script_name, $document)
    {
        switch ($script_name) {
            case 'delivery_iframe':
                if (MyParcel()->helper->isModuleExist('d_quickcheckout', true)) {
                    $document->addScript($this->getJsUrl() . 'checkout.aqc.js');
                } else {
                    $document->addScript($this->getJsUrl() . 'checkout.js');
                }
                break;
            default:
                $document->addScript($this->getJsUrl() . $script_name . '.js');
        }
    }

    function loadMyparcelLang($lang = null)
    {
        if ($lang instanceof Language) {
            $old_heading_title = $lang->get('heading_title');
            $lang->load(MyParcel()->getMyparcelModulePath());
            if (!empty($old_heading_title) && $old_heading_title != 'heading_title') {
                $lang->set('heading_title', $old_heading_title);
            }
        }
    }
}

function MyParcel($registry = null)
{
    return MyParcel::instance($registry);
}
