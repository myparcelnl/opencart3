<?php

class MyParcel_Helper
{
    public function filterEUOrders( $order_ids )
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('sale/order');
        $model_order = $registry->get('model_sale_order');

        foreach ($order_ids as $key => $order_id) {
            $order_data = $model_order->getOrder($order_id);
            $shipping_country = $order_data['shipping_iso_code_2'];
            if ( !$this->isEUCountry( $shipping_country ) ) {
                unset($order_ids[$key]);
            }
        }

        return $order_ids;
    }

    function isEUCountry($country_code)
    {
//        $eu_countries = array( 'GB', 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );
        $eu_countries = array( 'NL',  'BE', 'AT','BG', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'XK' );
        return in_array( $country_code, $eu_countries);
    }

    function trailingslashit( $string ) {
        return $this->untrailingslashit( $string ) . '/';
    }

    function untrailingslashit( $string ) {
        return rtrim( $string, '/\\' );
    }

    function maybe_unserialize( $original )
    {
        if ( $this->is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
            return @unserialize( $original );
        return $original;
    }

    function maybe_serialize( $data )
    {
        if ( is_array( $data ) || is_object( $data ) )
            return serialize( $data );

        if ( $this->is_serialized( $data, false ) )
            return serialize( $data );

        return $data;
    }

    function is_serialized( $data, $strict = true ) {
        // if it isn't a string, it isn't serialized.
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );
        if ( 'N;' == $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 ) {
            return false;
        }
        if ( ':' !== $data[1] ) {
            return false;
        }
        if ( $strict ) {
            $lastc = substr( $data, -1 );
            if ( ';' !== $lastc && '}' !== $lastc ) {
                return false;
            }
        } else {
            $semicolon = strpos( $data, ';' );
            $brace     = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace )
                return false;
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 )
                return false;
            if ( false !== $brace && $brace < 4 )
                return false;
        }
        $token = $data[0];
        switch ( $token ) {
            case 's' :
                if ( $strict ) {
                    if ( '"' !== substr( $data, -2, 1 ) ) {
                        return false;
                    }
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
            // or else fall through
            case 'a' :
            case 'O' :
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
        }
        return false;
    }

    function declareRegistry($module = 'catalog')
    {
        if (is_file(dirname(DIR_CATALOG) . '/config.php')) {

            require_once(dirname(DIR_CATALOG) . '/config.php');
        }

        // Registry
        $registry = new Registry();

        // Config
        $config = new Config();
        $config->load('default');
        $config->load($module);
        $registry->set('config', $config);

        // Event
        $event = new Event($registry);
        $registry->set('event', $event);

        // Event Register
        if ($config->has('action_event')) {
            foreach ($config->get('action_event') as $key => $value) {
                $event->register($key, new Action($value));
            }
        }

        // Loader
        $loader = new Loader($registry);
        $registry->set('load', $loader);


        $registry->set('db', new DB($config->get('db_type'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));

        $registry->set('url', new Url($config->get('site_base'), $config->get('site_ssl')));

        // Document
        $registry->set('document', new Document());


        return $registry;
    }

    function add_query_arg()
    {
        $args = func_get_args();
        if ( is_array( $args[0] ) ) {
            if ( count( $args ) < 2 || false === $args[1] )
                $uri = $_SERVER['REQUEST_URI'];
            else
                $uri = $args[1];
        } else {
            if ( count( $args ) < 3 || false === $args[2] )
                $uri = $_SERVER['REQUEST_URI'];
            else
                $uri = $args[2];
        }

        if ( $frag = strstr( $uri, '#' ) )
            $uri = substr( $uri, 0, -strlen( $frag ) );
        else
            $frag = '';

        if ( 0 === stripos( $uri, 'http://' ) ) {
            $protocol = 'http://';
            $uri = substr( $uri, 7 );
        } elseif ( 0 === stripos( $uri, 'https://' ) ) {
            $protocol = 'https://';
            $uri = substr( $uri, 8 );
        } else {
            $protocol = '';
        }

        if ( strpos( $uri, '?' ) !== false ) {
            list( $base, $query ) = explode( '?', $uri, 2 );
            $base .= '?';
        } elseif ( $protocol || strpos( $uri, '=' ) === false ) {
            $base = $uri . '?';
            $query = '';
        } else {
            $base = '';
            $query = $uri;
        }

        $qs = '';
        $this->mp_parse_str( $query, $qs );
        $qs = $this->urlencode_deep( $qs ); // this re-URL-encodes things that were already in the query string
        if ( is_array( $args[0] ) ) {
            foreach ( $args[0] as $k => $v ) {
                $qs[ $k ] = $v;
            }
        } else {
            $qs[ $args[0] ] = $args[1];
        }

        foreach ( $qs as $k => $v ) {
            if ( $v === false )
                unset( $qs[$k] );
        }

        $ret = $this->build_query( $qs );
        $ret = trim( $ret, '?' );
        $ret = preg_replace( '#=(&|$)#', '$1', $ret );
        $ret = $protocol . $base . $ret . $frag;
        $ret = rtrim( $ret, '?' );
        return $ret;
    }

    function mp_parse_str( $string, &$array ) {
        parse_str( $string, $array );
        if(version_compare (phpversion(), '7.4.0', '<') && version_compare (phpversion(), '5.4', '!=')){
            if ( get_magic_quotes_gpc() )
                $array = $this->stripslashes_deep( $array );
        }
        else{
            $array = $this->stripslashes_deep( $array );
        }
    }

    function stripslashes_deep( $value ) {
        return $this->map_deep( $value, 'stripslashes_from_strings_only' );
    }

    function map_deep( $value, $callback ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $index => $item ) {
                $value[ $index ] = $this->map_deep( $item, $callback );
            }
        } elseif ( is_object( $value ) ) {
            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->$property_name = $this->map_deep( $property_value, $callback );
            }
        } else {
            $value = $this->stripslashes_from_strings_only($value );
        }

        return $value;
    }

    function stripslashes_from_strings_only( $value ) {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }

    function urlencode_deep( $value ) {
        return $this->map_deep( $value, 'urlencode' );
    }

    function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
        $ret = array();

        foreach ( (array) $data as $k => $v ) {
            if ( $urlencode)
                $k = urlencode($k);
            if ( is_int($k) && $prefix != null )
                $k = $prefix.$k;
            if ( !empty($key) )
                $k = $key . '%5B' . $k . '%5D';
            if ( $v === null )
                continue;
            elseif ( $v === false )
                $v = '0';

            if ( is_array($v) || is_object($v) )
                array_push($ret,$this->_http_build_query($v, '', $sep, $k, $urlencode));
            elseif ( $urlencode )
                array_push($ret, $k.'='.urlencode($v));
            else
                array_push($ret, $k.'='.$v);
        }

        if ( null === $sep )
            $sep = ini_get('arg_separator.output');

        return implode($sep, $ret);
    }

    function build_query( $data ) {
        return $this->_http_build_query( $data, null, '&', '', false );
    }

    function _splitStreet($fullStreet)
    {
        //$split_street_regex = '~(?P<street>.*?)\s?(?P<street_suffix>(?P<number>[\d]+)-?(?P<number_suffix>[a-zA-Z/\s]{0,5}$|-?[0-9/]{0,5}$|\s[a-zA-Z]{1}-?[0-9]{0,3}$))$~';
        $split_street_regex =  '~(?P<street>.*?)'.                  // The rest belongs to the street

            '\s?'.                               // Separator between street and number

            '(?P<number>\d{1,4})'.               // Number can contain a maximum of 4 numbers

            '[\/\s\-]{0,2}'.                      // Separators between number and addition

            '(?P<number_suffix>'.

            '[a-zA-Z]{1}\d{1,3}|'.           // Numbers suffix starts with a letter followed by numbers or

            '-\d{1,4}|'.                     // starts with - and has up to 4 numbers or

            '\d{2}\w{1,2}|'.                 // starts with 2 numbers followed by letters or

            '[a-zA-Z]{1}[a-zA-Z\s]{0,3}'.    // has up to 4 letters with a space

            ')?$~';


        $fullStreet = preg_replace("/[\n\r]/", "", $fullStreet);
        $result = preg_match($split_street_regex, $fullStreet, $matches);

        if (!$result || !is_array($matches) || $fullStreet != $matches[0]) {
            return $fullStreet;
        }

        return $matches;
    }

    /** START @Since the fix for negative house number (64-69)
     * 64 is house number and 69 is additional number
     **/
    function _splitMultipleHouseNumberStreet($address, $number_addition = '', $force=true)
    {
        $ret = array();
        $ret['house_number']    = '';
        $ret['number_addition'] = '';

        $address = str_replace(array('?', '*', '[', ']', ',', '!'), ' ', $address);
        $address = preg_replace('/\s\s+/', ' ', $address);

        preg_match('/^([0-9]*)(.*?)([0-9]+)(.*)/', $address, $matches);

        if (!empty($matches[2]))
        {
            $ret['street']          = trim($matches[1] . $matches[2]);
            $ret['house_number']    = trim($matches[3]);
            $ret['number_addition'] = trim($matches[4]);
            if($number_addition != ''){
                $ret['number_addition'] = str_replace('-','',$ret['number_addition']);
                $ret['number_addition'].= ' ' . $number_addition;
            }
        }
        else // no street part
        {
            $ret['street'] = $address;
        }

        if ($force) {
            $ret['force_addition_number'] = true;
        }

        return $ret;
    }
    /** END @Since the fix for negative house number (64-69) **/

    function getAddressComponents($address)
    {
        $ret = array();

        $address = trim($address);
        $is_single_word = (strpos($address, ' ') === false) ? true : false;


        $arrExclude = [' bus ', '/bus ', '/ bus ', ' bte ', '/bte ', '/ bte ', ' box ', '/box ', '/ box ', ' boîte ','/boîte ', '/ boîte '];
        foreach ($arrExclude as $value){
            if(strpos($address,$value) !== false){
                $address = explode($value,$address)[0];
                break;
            }
        }

        if ($is_single_word) {
            $ret['street']          = $address;
            $ret['house_number']    = '';
            $ret['number_addition'] = '';

            return $ret;
        }

//        $parts = explode(' ', $address);
//        if (!empty($parts) && !is_numeric($parts[count($parts) - 1])) {
//            $ret['street']          = $address;
//            $ret['house_number']    = '';
//            $ret['number_addition'] = '';
//
//            return $ret;
//        }

        $matches = $this->_splitStreet($address);

        if (isset($matches['street'])) {
            $ret['street'] = $matches['street'];
        }

        if (isset($matches['number'])) {
            $ret['house_number']    = trim($matches['number']);
        }

        if (isset($matches['number_suffix'])) {
            $ret['number_addition']    = trim($matches['number_suffix']);
        }

        if (empty($ret['street'])) {
            $ret['street'] = $address;
        }

        /** START @Since the fix for negative house number (64-69) **/
        if (strlen($ret['street']) && substr($ret['street'], -1) == '-') {
            $ret['street'] = str_replace(' -', '', $ret['street']);
            $ret['street'] = str_replace('-', '', $ret['street']);
            $ret['street'] .= ' -' . $ret['house_number'];
            $ret['force_addition_number'] = true;
            $number_addition = (isset($ret['number_addition'])) ? $ret['number_addition'] : '';
            return $this->_splitMultipleHouseNumberStreet( $ret['street'] ,$number_addition);
        }
        /** END @Since the fix for negative house number (64-69) **/

        return $ret;
    }

    public function getShippingOrder($order_id)
    {
        $registry = MyParcel::$registry;
        $db = $registry->get('db');
        $config = $registry->get('config');

        $order_query = $db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $country_query = $db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            return array(
                'order_id'                => $order_query->row['order_id'],
                'shipping_postcode'       => $order_query->row['shipping_postcode'],
                'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
                'shipping_zone'           => $order_query->row['shipping_zone'],
                'shipping_country_id'     => $order_query->row['shipping_country_id'],
                'shipping_country'        => $order_query->row['shipping_country'],
                'shipping_iso_code_2'     => $shipping_iso_code_2,
                'shipping_iso_code_3'     => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_method'         => $order_query->row['shipping_method'],
                'shipping_code'           => $order_query->row['shipping_code'],
                'payment_postcode'        => $order_query->row['payment_postcode'],
                'comment'                 => $order_query->row['comment'],
            );
        } else {
            return;
        }
    }

    /**
     * Render html bootstrap notice from an array of errors
     * @param Array $errors
     * @return HTML content of errors
     *
     **/
    function renderErrors($errors)
    {
        if (empty($errors)) {
            return '';
        }
        ob_start();
        ?>
        <div id="myparcel-error-messages-wrapper" class="alert alert-danger">
            <ul>
                <?php
                foreach ($errors as $error)
                {
                    ?>
                    <li>

                        <?php echo $error ?>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    function install_ocmod($file)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('setting/modification');
        $loader->model('extension/myparcelnl/init');
        $model_setting_modification = $registry->get('model_setting_modification');
        $model_extension_myparcelnl_init = $registry->get('model_extension_myparcelnl_init');

        $lang = $registry->get('language');

        // If xml file just put it straight into the DB
        $xml = file_get_contents($file);

        $json = array();

        if ($xml) {
            try {
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->loadXml($xml);

                $name = $dom->getElementsByTagName('name')->item(0);

                if ($name) {
                    $name = $name->nodeValue;
                } else {
                    $name = '';
                }

                $code = $dom->getElementsByTagName('code')->item(0);

                if ($code) {
                    $code = $code->nodeValue;

                    // Check to see if the modification is already installed or not.
                    $modification_info = $model_setting_modification->getModificationByCode($code);

                    if ($modification_info) {
                        $json['warning'] = sprintf($lang->get('error_exists'), $modification_info['name']);
                    }
                } else {
                    $json['error'] = $lang->get('error_code');
                }

                $author = $dom->getElementsByTagName('author')->item(0);

                if ($author) {
                    $author = $author->nodeValue;
                } else {
                    $author = '';
                }

                $version = $dom->getElementsByTagName('version')->item(0);

                if ($version) {
                    $version = $version->nodeValue;
                } else {
                    $version = '';
                }

                $link = $dom->getElementsByTagName('link')->item(0);

                if ($link) {
                    $link = $link->nodeValue;
                } else {
                    $link = '';
                }
                if (isset($this->request->get['extension_install_id'])) {
                    $extension_install_id = $this->request->get['extension_install_id'];
                } else {
                    $extension_install_id = 0;
                }
                $modification_data = array(
                    'extension_install_id' => $extension_install_id,
                    'name'    => $name,
                    'code'    => $code,
                    'author'  => $author,
                    'version' => $version,
                    'link'    => $link,
                    'xml'     => $xml,
                    'status'  => 1
                );

                if (!$json) {
                    $model_setting_modification->addModification($modification_data);
                }
            } catch(Exception $exception) {
                $json['error'] = sprintf($lang->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
            }
        }

        $model_extension_myparcelnl_init->refreshOcmod();

        return $json;
    }

    function uninstall_ocmod()
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('setting/modification');
        $loader->model('extension/myparcelnl/init');
        $model_setting_modification = $registry->get('model_setting_modification');
        $model_extension_myparcelnl_init = $registry->get('model_extension_myparcelnl_init');

        $results = $model_setting_modification->getModifications();
        foreach ($results as $result) {
            if ($result['code'] == 'MyParcelNL_default_checkout' || $result['code'] == 'MyParcelNL') {
                $model_setting_modification->deleteModification($result['modification_id']);
            }
        }

        $model_extension_myparcelnl_init->refreshOcmod();
    }

    function set_module_text($language)
    {
        /*Assign the language data for parsing it to view*/
        $data = array();
        $data['heading_title'] = $language->get('heading_title');

        $data['text_enabled'] = $language->get('text_enabled');
        $data['text_disabled'] = $language->get('text_disabled');
        $data['text_content_top'] = $language->get('text_content_top');
        $data['text_content_bottom'] = $language->get('text_content_bottom');
        $data['text_column_left'] = $language->get('text_column_left');
        $data['text_column_right'] = $language->get('text_column_right');

        $data['name'] = $language->get('text_heading_title');
        $data['entry_name'] = $language->get('heading_title');
        $data['entry_title_package_types'] = $language->get('entry_title_package_types');
        $data['entry_status'] = $language->get('heading_title');
        $data['text_edit'] = $language->get('heading_title');
        $data['entry_description'] = $language->get('heading_title');
        $data['error_name'] = $language->get('heading_title');
        $data['entry_tab_1_title'] = $language->get('entry_tab_1_title');
        $data['entry_tab_2_title'] = $language->get('entry_tab_2_title');
        $data['entry_tab_3_title'] = $language->get('entry_tab_3_title');

        $data['entry_tab_general_title']  = $language->get('entry_tab_general_title');
        $data['entry_tab_export_title']   = $language->get('entry_tab_export_title');
        $data['entry_tab_checkout_title'] = $language->get('entry_tab_checkout_title');

        /*tab generel setting*/
        $data['entry_tab_1_api'] = $language->get('entry_tab_1_api');
        $data['entry_tab_1_api_setting'] = $language->get('entry_tab_1_api_setting');
        $data['entry_tab_1_system_setting'] = $language->get('entry_tab_1_system_setting');
        $data['entry_tab_1_admin_folder'] = $language->get('entry_tab_1_admin_folder');
        $data['entry_tab_1_generel_setting'] = $language->get('entry_tab_1_generel_setting');
        $data['entry_tab_1_label_display'] = $language->get('entry_tab_1_label_display');
        $data['entry_tab_1_radio_download_pdf'] = $language->get('entry_tab_1_radio_download_pdf');
        $data['entry_tab_1_radio_open_pdf'] = $language->get('entry_tab_1_radio_open_pdf');
        $data['entry_tab_1_trackandtrace_email'] = $language->get('entry_tab_1_trackandtrace_email');
        $data['entry_tab_1_checkbox_trackandtrace_email'] = $language->get('entry_tab_1_checkbox_trackandtrace_email');
        $data['entry_tab_1_add_trackandtrace_account'] = $language->get('entry_tab_1_add_trackandtrace_account');
        $data['entry_tab_1_checkbox_trackandtrace_myaccount'] = $language->get('entry_tab_1_checkbox_trackandtrace_myaccount');
        $data['entry_tab_1_show_shipment_directly'] = $language->get('entry_tab_1_show_shipment_directly');
        $data['entry_tab_1_checkbox_shipment_directly'] = $language->get('entry_tab_1_checkbox_shipment_directly');
        $data['entry_tab_1_order_status_automation'] = $language->get('entry_tab_1_order_status_automation');
        $data['entry_tab_1_checkbox_order_status_automation'] = $language->get('entry_tab_1_checkbox_order_status_automation');
        $data['entry_tab_1_automatic_order_status'] = $language->get('entry_tab_1_automatic_order_status');
        $data['entry_tab_1_keep_old_shipments'] = $language->get('entry_tab_1_keep_old_shipments');
        $data['entry_tab_1_checkbox_keep_old_shipments'] = $language->get('entry_tab_1_checkbox_keep_old_shipments');
        $data['entry_tab_1_label_use_addition_address_as_number_suffix'] = $language->get('entry_tab_1_label_use_addition_address_as_number_suffix');
        $data['entry_tab_1_checkbox_use_address1_and_address2'] = $language->get('entry_tab_1_checkbox_use_address1_and_address2');
        $data['entry_tab_1_checkbox_use_address2_as_number_suffix'] = $language->get('entry_tab_1_checkbox_use_address2_as_number_suffix');
        $data['entry_tab_1_checkbox_use_address3_as_number_suffix'] = $language->get('entry_tab_1_checkbox_use_address3_as_number_suffix');
        $data['entry_tab_1_diagnostic_tools'] = $language->get('entry_tab_1_diagnostic_tools');
        $data['entry_tab_1_log_api_communication'] = $language->get('entry_tab_1_log_api_communication');
        $data['entry_tab_1_checkbox_log_api_communication'] = $language->get('entry_tab_1_checkbox_log_api_communication');
        $data['entry_tab_1_download_log_file'] = $language->get('entry_tab_1_download_log_file');
        $data['entry_tab_1_title_paper_format']              = $language->get('entry_tab_1_title_paper_format');

        $data['entry_tab_2_title_export_settings']           = $language->get('entry_tab_2_title_export_settings');
        $data['entry_tab_2_title_package_types']             = $language->get('entry_tab_2_title_package_types');
        $data['entry_tab_2_title_default_hs_code']           = $language->get('entry_tab_2_title_default_hs_code');
        $data['entry_tab_2_title_default_country_of_origin'] = $language->get('entry_tab_2_title_default_country_of_origin');
        $data['entry_tab_2_title_connect_customer_email']    = $language->get('entry_tab_2_title_connect_customer_email');
        $data['entry_tab_2_checkbox_connect_customer_email'] = sprintf($language->get('entry_tab_2_checkbox_connect_customer_email'), '<a href="https://backoffice.myparcel.nl/ttsettingstable">MyParcel backend</a>');
        $data['entry_tab_2_title_connect_customer_phone']    = $language->get('entry_tab_2_title_connect_customer_phone');
        $data['entry_tab_2_checkbox_connect_customer_phone'] = $language->get('entry_tab_2_checkbox_connect_customer_phone');
        $data['entry_tab_2_title_extra_large_size']          = sprintf($language->get('entry_tab_2_title_extra_large_size'), '2.45');
        $data['entry_tab_2_checkbox_extra_large_size']       = $language->get('entry_tab_2_checkbox_extra_large_size');
        $data['entry_tab_2_title_address_only']              = sprintf($language->get('entry_tab_2_title_address_only'), '0.29');
        $data['entry_tab_2_checkbox_address_only']           = $language->get('entry_tab_2_checkbox_address_only');
        $data['entry_tab_2_title_signature_delivery']        = sprintf($language->get('entry_tab_2_title_signature_delivery'), '0.36');
        $data['entry_tab_2_checkbox_signature_delivery']     = $language->get('entry_tab_2_checkbox_signature_delivery');
        $data['entry_tab_2_title_return_no_answer']          = $language->get('entry_tab_2_title_return_no_answer');
        $data['entry_tab_2_checkbox_return_no_answer']       = $language->get('entry_tab_2_checkbox_return_no_answer');
        $data['entry_tab_2_title_insured_shipment']          = $language->get('entry_tab_2_title_insured_shipment');
        $data['entry_tab_2_title_insured_amount']            = $language->get('entry_tab_2_title_insured_amount');
        $data['entry_tab_2_title_age_check']                 = $language->get('entry_tab_2_title_age_check');
        $data['entry_tab_2_title_age_check_desrition']       = $language->get('entry_tab_2_title_age_check_desrition');
        $data['entry_tab_2_title_insured_amount_custom']     = $language->get('entry_tab_2_title_insured_amount_custom');
        $data['entry_tab_2_checkbox_insured_shipment']       = $language->get('entry_tab_2_checkbox_insured_shipment');
        $data['entry_tab_2_title_label_description']         = $language->get('entry_tab_2_title_label_description');
        $data['entry_tab_2_textbox_label_description']       = $language->get('entry_tab_2_textbox_label_description');
        $data['entry_tab_2_title_empty_parcel_weight']       = $language->get('entry_tab_2_title_empty_parcel_weight');
        $data['entry_tab_2_textbox_empty_parcel_weight']     = $language->get('entry_tab_2_textbox_empty_parcel_weight');
        $data['entry_tab_2_select_package_types']            = $language->get('entry_tab_2_select_package_types');
        $data['entry_tab_2_default_weight']                  = $language->get('entry_tab_2_default_weight');

        $data['entry_tab_2_select_insured_up_to_50']  = $language->get('entry_tab_2_select_insured_up_to_50');
        $data['entry_tab_2_select_insured_up_to_250'] = $language->get('entry_tab_2_select_insured_up_to_250');
        $data['entry_tab_2_select_insured_up_to_500'] = $language->get('entry_tab_2_select_insured_up_to_500');
        $data['entry_tab_2_select_insured_500']       = $language->get('entry_tab_2_select_insured_500');

        $data['entry_subtotal'] = $language->get('entry_subtotal');
        $data['entry_tab_3_enable_myparcel_shipping_message'] = $language->get('entry_tab_3_enable_myparcel_shipping_message');
        $data['entry_tab_3_title_delivery_option'] = $language->get('entry_tab_3_title_delivery_option');
        $data['entry_tab_3_label_enable_delivery'] = $language->get('entry_tab_3_label_enable_delivery');
        $data['entry_tab_3_label_home_address_only'] = $language->get('entry_tab_3_label_home_address_only');
        $data['entry_tab_3_label_signature_on_delivery'] = $language->get('entry_tab_3_label_signature_on_delivery');
        $data['entry_tab_3_label_evening_delivery'] = $language->get('entry_tab_3_label_evening_delivery');
        $data['entry_tab_3_label_morning_delivery'] = $language->get('entry_tab_3_label_morning_delivery');
        $data['entry_tab_3_label_postnl_pickup'] = $language->get('entry_tab_3_label_postnl_pickup');
        $data['entry_tab_3_label_early_postnl_pickup'] = $language->get('entry_tab_3_label_early_postnl_pickup');
        $data['entry_tab_3_title_shipment_processing_parameters'] = $language->get('entry_tab_3_title_shipment_processing_parameters');
        $data['entry_tab_3_label_dropoff_days'] = $language->get('entry_tab_3_label_dropoff_days');
        $data['entry_tab_3_label_cut_off_time'] = $language->get('entry_tab_3_label_cut_off_time');
        $data['entry_tab_3_label_cut_off_time'] = $language->get('entry_tab_3_label_cut_off_time');
        $data['entry_tab_3_label_dropoff_delay'] = $language->get('entry_tab_3_label_dropoff_delay');
        $data['entry_tab_3_label_delivery_days_window'] = $language->get('entry_tab_3_label_delivery_days_window');
        $data['entry_tab_3_label_additional_fee'] = $language->get('entry_tab_3_label_additional_fee');
        $data['entry_tab_3_textbox_cut_off_time'] = $language->get('entry_tab_3_textbox_cut_off_time');
        $data['entry_tab_3_textbox_dropoff_delay'] = $language->get('entry_tab_3_textbox_dropoff_delay');
        $data['entry_tab_3_textbox_delivery_days_window'] = $language->get('entry_tab_3_textbox_delivery_days_window');
        $data['entry_tab_3_select_dropoff_days'] = $language->get('entry_tab_3_select_dropoff_days');
        $data['entry_tab_3_label_mailbox_settings'] = $language->get('entry_tab_3_label_mailbox_settings');
        $data['entry_tab_3_label_mailbox_title'] = $language->get('entry_tab_3_label_mailbox_title');
        $data['entry_tab_3_label_mailbox_fee'] = $language->get('entry_tab_3_label_mailbox_fee');
        $data['entry_tab_3_label_mailbox_accept_weight'] = $language->get('entry_tab_3_label_mailbox_accept_weight');
        $data['entry_tab_3_label_base_color'] = $language->get('entry_tab_3_label_base_color');
        $data['entry_tab_3_label_highlight_color'] = $language->get('entry_tab_3_label_highlight_color');
        $data['entry_tab_3_label_custom_style'] = $language->get('entry_tab_3_label_custom_style');
        $data['entry_tab_3_label_auto_google_fronts'] = $language->get('entry_tab_3_label_auto_google_fronts');
        $data['entry_tab_3_title_customizations'] = $language->get('entry_tab_3_title_customizations');
        $data['entry_tab_3_label_standard_delivery'] = $language->get('entry_tab_3_label_standard_delivery');
        $data['entry_tab_3_label_belgium_settings'] = $language->get('entry_tab_3_label_belgium_settings');
        $data['entry_tab_3_label_belgium_default_fee'] = $language->get('entry_tab_3_label_belgium_default_fee');
        $data['entry_tab_3_label_belgium_pickup_fee'] = $language->get('entry_tab_3_label_belgium_pickup_fee');
        $data['entry_tab_3_label_cut_off_weekday'] = $language->get('entry_tab_3_label_cut_off_weekday');
        $data['error_cut_off_not_correct_format'] = $language->get('error_cut_off_not_correct_format');
        $data['entry_tab_3_label_only_apply_for_xtension_checkout'] = $language->get('entry_tab_3_label_only_apply_for_xtension_checkout');
        $data['entry_tab_3_label_time_format'] = $language->get('entry_tab_3_label_time_format');
        $data['entry_tab_3_label_time_format_description'] = $language->get('entry_tab_3_label_time_format_description');
        $data['entry_tab_3_label_distance'] = $language->get('entry_tab_3_label_distance');
        $data['entry_tab_3_label_rounding_distance'] = $language->get('entry_tab_3_label_rounding_distance');
        $data['entry_tab_3_label_rounding_distance_description'] = $language->get('entry_tab_3_label_rounding_distance_description');
        $data['entry_tab_3_distance_format_kilometer'] = $language->get('entry_tab_3_distance_format_kilometer');
        $data['entry_tab_3_distance_format_meter'] = $language->get('entry_tab_3_distance_format_meter');
        $data['entry_tab_3_label_default_price_0_text'] = $language->get('entry_tab_3_label_default_price_0_text');
        $data['entry_tab_3_label_default_price_0_text_description'] = $language->get('entry_tab_3_label_default_price_0_text_description');



        $data['days_of_the_week'] = array(
            '0' => $language->get('Sunday'),
            '1' => $language->get('Monday'),
            '2' => $language->get('Tuesday'),
            '3' => $language->get('Wednesday'),
            '4' => $language->get('Thursday'),
            '5' => $language->get('Friday'),
            '6' => $language->get('Saturday'),
        );

        $data['insured_amounts'] = array(
            '49' => $language->get('entry_tab_2_select_insured_up_to_50'),
            '249' => $language->get('entry_tab_2_select_insured_up_to_250'),
            '499' => $language->get('entry_tab_2_select_insured_up_to_500'),
            '' => $language->get('entry_tab_2_select_insured_500'),
        );
        $data['distance_format'] = array(
            'm' => $language->get('entry_tab_3_distance_format_meter'),
            'km' => $language->get('entry_tab_3_distance_format_kilometer'),
        );

        $data['button_save'] = $language->get('button_save');
        $data['button_cancel'] = $language->get('button_cancel');

        // add digital_stamp default weight
        $data['digital_stamp_default_weight'] = $this->_getDigitalStampDefaultWeight();
        $data['digital_stamp_code'] = $this->_getDigitalStampCode();

        //paper format
        $data['paper_format'] = array(
            'A4' => 'A4',
            'A6' => 'A6',
        );
        return $data;
    }

    /**
     * Retrieve Digital Stamp Default Weight
     * @return array of weight key - value pairs
     **/
    public function _getDigitalStampDefaultWeight()
    {
        return array(
            20 => '0 - 20 gram',
            50 => '20 - 50 gram',
            100 => '50 - 100 gram',
            350 => '100 - 350 gram',
            2000 => '350 - 2000 gram',
        );
    }

    /**
     * Retrieve Digital Stamp code
     * @return int Digital Stamp code
     **/
    public function _getDigitalStampCode()
    {
        return 4;
    }

    /**
     * Validate cut off time format hh:mm
     **/
    function validate_cutoff_time($cutOffTime='')
    {
        if (empty($cutOffTime)) {
            return true;
        }else{
            if (preg_match('/^\d{2}:\d{2}$/', $cutOffTime)) {
                if (preg_match("/^([01]?[0-9]|2[0-3])\:+[0-5][0-9]$/", $cutOffTime)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a module is installed
     * @param string $module
     * @param boolean $check_status
     * @return boolean
     **/

    function isModuleExist($module, $check_status = false)
    {
        // TODO remove the right below line
        //return true;
        $registry = $db = MyParcel::$registry;
        $db = $registry->get('db');
        $result = $db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `code` = '" . $module . "'");

        if ($check_status) {
            if ($result->num_rows) {
                $config = $registry->get('config');
                if ($config->get('module_' . $module . '_status')) {
                    return true;
                }
            }
            return false;
        } else {
            return ($result->num_rows);
        }
    }

    /**
     * Get Address 2 or say address number if xtensions netherlands postcode module is installed!
     **/
    function getAddressNumberXNLPostcode($shipping_address, $prefix = ''){
        $registry = $db = MyParcel::$registry;
        if(version_compare(VERSION, '3.0.0.0')>=0){
            $field = $registry->get('config')->get('module_xnlpostcode_nl_housenumber');
        }else{
            $field = $registry->get('config')->get('xnlpostcode_nl_housenumber');
        }
        if($field){
            if(in_array($field, array('address_2','company'))){
                return (isset($shipping_address[$prefix . $field])) ? $shipping_address[$prefix . $field] : '';
            }else{
                return (isset($shipping_address[$prefix . 'custom_field'][$field])) ? $shipping_address[$prefix . 'custom_field'][$field] : '';
            }
        }
        return $shipping_address[$prefix . 'address_2'];
    }

    public function getAddressCustomFields(){
        $registry = $db = MyParcel::$registry;
        $db = $registry->get('db');
        $query = $db->query("SELECT cfd.custom_field_id AS id, cfd.name  FROM `" . DB_PREFIX . "custom_field` cf JOIN `" . DB_PREFIX . "custom_field_description` cfd ON cf.custom_field_id = cfd.custom_field_id WHERE cf.status = 1 AND cf.location = 'address' ");
        return array_merge($query->rows);
    }

    public function getDeliveryParams($shipping_address, $prefix = ''){

        $shipment_class = MyParcel()->shipment;
        $registry = MyParcel::$registry;
        $checkout_helper = $shipment_class->checkout;
        $config = $registry->get('config');

        $myparcelnl_checkout_settings = $config->get('module_myparcelnl_fields_checkout');
        $myparcelnl_general_settings = $config->get('module_myparcelnl_fields_general');

        $deliverydays_window = isset($myparcelnl_checkout_settings['delivery_days_window']) ? $myparcelnl_checkout_settings['delivery_days_window'] : 4;

        $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
        if (!empty($shipping_address[$prefix.'address_2']) && !$use_addition_address_as_number_suffix) {
            $shipping_address[$prefix.'address_1'] = $shipping_address[$prefix.'address_1'] . ' ' . $shipping_address[$prefix.'address_2'];
        }
        $address_parts = $this->getAddressComponents($shipping_address[$prefix.'address_1']);
        if($this->isModuleExist('xnlpostcode', true)){
            $house_number = $this->getAddressNumberXNLPostcode($shipping_address,$prefix);
        }else{
            $house_number = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
        }

        $exclude_delivery_types = array();
        foreach ($checkout_helper::$delivery_types_as_value as $delivery_type => $key) {
            // JS API correction
            if ($delivery_type == 'standard' || $delivery_type == 'mailbox' || $delivery_type == 'avond') {
                continue;
            }
            if (!isset($myparcelnl_checkout_settings[$delivery_type.'_enabled']) || (isset($myparcelnl_checkout_settings[$delivery_type.'_enabled']) && empty($myparcelnl_checkout_settings[$delivery_type.'_enabled']) ) ) {
                $exclude_delivery_types[] = $key;
            }
        }
        $exclude_delivery_types = implode(';', $exclude_delivery_types);
        /**
         * Get cut_off_time based on current day
         **/
        $cut_off_time = !empty($myparcelnl_checkout_settings['cut_off_time']) ? $myparcelnl_checkout_settings['cut_off_time'] : '';
        // If cut_off_time by weekdays enabled
        $cut_off_time_weekdays_enabled = !empty($myparcelnl_checkout_settings['cut_off_weekday']) ? $myparcelnl_checkout_settings['cut_off_weekday'] : '';
        if ($cut_off_time_weekdays_enabled) {
            $day_of_week = date('w');
            $cut_off_time = (!empty($myparcelnl_checkout_settings['cut_off_time_weekdays'][$day_of_week]) ? $myparcelnl_checkout_settings['cut_off_time_weekdays'][$day_of_week] : '');
        }
        //get dropoff days
        $dropoff_day = isset($myparcelnl_checkout_settings['dropoff_days']) ? $myparcelnl_checkout_settings['dropoff_days'] : '';

        $delivery_params = array(
            'cc' => $shipping_address[$prefix.'iso_code_2'],
            'postal_code'   => str_replace(' ', '',$shipping_address[$prefix.'postcode']),
            'street'        => isset($address_parts['street']) ? strtolower($address_parts['street']) :"",
            'number'        => $house_number,
            'carrier'       => 1,
            'cutoff_time'   => $cut_off_time,
            'exclude_delivery_type' => $exclude_delivery_types,
            'deliverydays_window' => $deliverydays_window,
            'monday_delivery' => '1',
            'dropoff_delay' => (isset($myparcelnl_checkout_settings['dropoff_delay']) && $myparcelnl_checkout_settings['dropoff_delay'] != '') ? $myparcelnl_checkout_settings['dropoff_delay'] : 0,

        );
        if(!empty($dropoff_day)){
            $delivery_params['dropoff_days'] = implode(';',$dropoff_day);
        }
        if(isset($myparcelnl_general_settings['use_addition_address_as_number_suffix'])){
            switch ((int)$myparcelnl_general_settings['use_addition_address_as_number_suffix']){
                case 1:
                    if($house_number == ''){
                        $delivery_params['number'] = $shipping_address[$prefix.'address_2'];
                    }
                    break;
                case 2:
                    if(isset($shipping_address[$prefix.'custom_field'][$myparcelnl_general_settings['general_custom_field_homenumber_suffix']])){
                        $delivery_params['number_suffix'] = $shipping_address[$prefix.'custom_field'][$myparcelnl_general_settings['general_custom_field_homenumber_suffix']];
                    }
                    if($house_number == ''){
                        $delivery_params['number'] = $shipping_address[$prefix.'address_2'];
                    }
                    break;
                default:
                    break;
            }
        }
        return $delivery_params;
    }

    public function formatDeliveryParamsEditOrder($delivery_params, $order_id){
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $checkout_helper = MyParcel()->shipment->checkout;

        // Load delivery options stored from Frontend Checkout
        $myparcel_delivery_options = $model_shipment->getMyParcelDeliveryOptions($order_id);
        if($myparcel_delivery_options != NULL){
            $delivery_type = $checkout_helper->getDeliveryTypeFromSavedData($myparcel_delivery_options);
            if(isset($delivery_params['exclude_delivery_type'])){
                $delivery_params['exclude_delivery_type'] = explode(';',$delivery_params['exclude_delivery_type']);
                if(in_array($delivery_type,$delivery_params['exclude_delivery_type'])){
                    $delivery_params['exclude_delivery_type'] = array_diff( $delivery_params['exclude_delivery_type'], [$delivery_type] );
                }
                $delivery_params['exclude_delivery_type'] = implode(';',$delivery_params['exclude_delivery_type']);
            }
        }
        return $delivery_params;
    }

    public function getDeliveryOptionChange($myparcel_delivery,$order_data){
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $checkout_helper = MyParcel()->shipment->checkout;
        $shipping_code = null;
        // Load delivery options stored from Frontend Checkout
        $myparcel_delivery_options = $model_shipment->getMyParcelDeliveryOptions($order_data['order_id']);
        if(!empty($myparcel_delivery_options)){
            $delivery_type = $checkout_helper->getDeliveryTypeFromSavedData($myparcel_delivery_options);

            $is_next_day = false;
            foreach ($myparcel_delivery as $key => $item ){
                foreach ($item as $value){
                    foreach ($value['time'] as $time){
                        if($time['type'] == $delivery_type){
                            if((date('Y-m-d',strtotime($myparcel_delivery_options['date'])) <= date('Y-m-d'))){
                                $myparcel_delivery_options['date'] = $value['date'];
                            }
                            $is_next_day = true;
                            $shipping_code = $key;
                            break;
                        }
                    }
                    if($is_next_day){
                        break;
                    }
                }
                if($is_next_day){
                    break;
                }
            }
        }
        $myparcel_delivery_options['shipping_code'] = $shipping_code;
        return $myparcel_delivery_options;
    }

    public function formatDeliveryPrice($myparcel_delivery,$shipping_address,$sub_total,$language, $current_currency_code){
        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $myparcelnl_checkout_settings = $config->get('module_myparcelnl_fields_checkout');

        $rounding_distance = (isset($myparcelnl_checkout_settings['rounding_distance']) && $myparcelnl_checkout_settings['rounding_distance']) ? $myparcelnl_checkout_settings['rounding_distance'] : 1;
        $distance_format = (isset($myparcelnl_checkout_settings['distance_format']) && $myparcelnl_checkout_settings['distance_format']) ? $myparcelnl_checkout_settings['distance_format'] : "m";
        $default_price_0_text = (isset($myparcelnl_checkout_settings['default_price_0_text'])&& $myparcelnl_checkout_settings['default_price_0_text']) ? $myparcelnl_checkout_settings['default_price_0_text'] : '';
        $time_format = (isset($myparcelnl_checkout_settings['time_format']) && $myparcelnl_checkout_settings['time_format']) ? $myparcelnl_checkout_settings['time_format'] : "H:i:s";

        $shipment_class = MyParcel()->shipment;
        /** @var MyParcel_Shipment_Checkout $checkout_helper **/
        $checkout_helper = $shipment_class->checkout;
        $delivery_prices = $checkout_helper->getDeliveryPrices(false, true, '', false, 0,$sub_total);
        $delivery_prices_format = $checkout_helper->getDeliveryPrices(false, true, '', true, 0,$sub_total);


        $currency = $registry->get('currency');

        if(isset($delivery_prices[$shipping_address['iso_code_2']])){
            foreach($myparcel_delivery['body']['data'] as $key => $value){
                if(count($value)){
                    foreach($value as $k => $v){
                        foreach($v['time'] as $index => $time){
                            $price = null;
                            $price_convert = null;
                            if(isset($time['price_comment'])){
                                switch($time['price_comment']){
                                    case 'morning':
                                        $price = isset($delivery_prices[$shipping_address['iso_code_2']]['morning']) ? $delivery_prices[$shipping_address['iso_code_2']]['morning'] : null;
                                        $price_convert = isset($delivery_prices_format[$shipping_address['iso_code_2']]['morning']) ? $delivery_prices_format[$shipping_address['iso_code_2']]['morning'] : null;
                                        break;
                                    case 'standard':
                                        $price = isset($delivery_prices[$shipping_address['iso_code_2']]['default']) ? $delivery_prices[$shipping_address['iso_code_2']]['default'] : null;
                                        $price_convert = isset($delivery_prices_format[$shipping_address['iso_code_2']]['default']) ? $delivery_prices_format[$shipping_address['iso_code_2']]['default'] : null;
                                        break;
                                    case 'night':
                                    case 'avond':
                                        $price = isset($delivery_prices[$shipping_address['iso_code_2']]['night']) ? $delivery_prices[$shipping_address['iso_code_2']]['night'] : null;
                                        $price_convert = isset($delivery_prices_format[$shipping_address['iso_code_2']]['night']) ? $delivery_prices_format[$shipping_address['iso_code_2']]['night'] : null;
                                        break;
                                }
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price_comment_text'] = $language->get('entry_delivery_' . $time['price_comment']);
                            }
                            else{
                                if(isset($v['price_comment'])){
                                    switch($v['price_comment']){
                                        case 'retail':
                                            $price = isset($delivery_prices[$shipping_address['iso_code_2']]['pickup']) ? $delivery_prices[$shipping_address['iso_code_2']]['pickup'] : null;
                                            $price_convert = isset($delivery_prices_format[$shipping_address['iso_code_2']]['pickup']) ? $delivery_prices_format[$shipping_address['iso_code_2']]['pickup'] : null;
                                            break;
                                        case 'retailexpress':
                                            $price = isset($delivery_prices[$shipping_address['iso_code_2']]['pickup_express']) ? $delivery_prices[$shipping_address['iso_code_2']]['pickup_express'] : null;
                                            $price_convert = isset($delivery_prices_format[$shipping_address['iso_code_2']]['pickup_express']) ? $delivery_prices_format[$shipping_address['iso_code_2']]['pickup_express'] : null;
                                            break;
                                    }
                                }
                                if(isset($v['location'])){
                                    $myparcel_delivery['body']['data'][$key][$k]['location'] = htmlspecialchars($v['location']);
                                }
                            }
                            if($price !== null){
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['amount'] = $price;
                            }
                            if($price_convert != null){
                                $price = $price_convert;
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['amount_with_tax'] = $price_convert;
                            }
                            else{
                                $price = $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['amount'];
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['amount_with_tax'] = $price;
                            }

                            //format price
                            $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['text_amount'] = ($price == 0 && $default_price_0_text != '') ? $default_price_0_text : $currency->format($currency->convert($price, $time['price']['currency'], $current_currency_code), $current_currency_code);
                            $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['price']['current_currency_amount'] = round($currency->convert($price, $time['price']['currency'], $current_currency_code), 2);

                            //format time
                            if(isset($myparcel_delivery['body']['data'][$key][$k]['time'][$index]['start'])){
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['start'] = date($time_format,strtotime($myparcel_delivery['body']['data'][$key][$k]['time'][$index]['start']));
                            }
                            if(isset($myparcel_delivery['body']['data'][$key][$k]['time'][$index]['end'])){
                                $myparcel_delivery['body']['data'][$key][$k]['time'][$index]['end'] = date($time_format,strtotime($myparcel_delivery['body']['data'][$key][$k]['time'][$index]['end']));
                            }
                        }
                        //format distance
                        if(isset($v['distance'])){
                            switch ($distance_format){
                                case "km":
                                    $myparcel_delivery['body']['data'][$key][$k]['distance'] = round($v['distance'] / 1000, $rounding_distance);
                                    break;
                                case "m":
                                    break;
                            }
                            $myparcel_delivery['body']['data'][$key][$k]['distance_format'] = $distance_format;
                        }
                    }
                }
            }
        }

        return $myparcel_delivery;
    }

    function getDisableDropoffDays($myparcel_delivery){
//        $registry = MyParcel::$registry;
//        $config = $registry->get('config');
//        $myparcelnl_checkout_settings = $config->get('module_myparcelnl_fields_checkout');
//
//        if(!isset($myparcelnl_checkout_settings['dropoff_days'])){
//            return [0,6];
//        }
//        $dropoff_days = $myparcelnl_checkout_settings['dropoff_days'];

        $enable_days = [];
        if(isset($myparcel_delivery['delivery'])){
            foreach ($myparcel_delivery['delivery'] as $delivery){
                $day_num = date('w', strtotime($delivery['date']));
                if(!in_array($day_num,$enable_days)){
                    $enable_days[] = $day_num;
                }
            }
        }
        return array_diff(range(0,6),$enable_days);
    }

    public function saveDefaultMyparcelMethodXtension($options, $code){
        if(!count($options)){
            return;
        }

        $registry = MyParcel::$registry;
        $session = $registry->get('session');
        // set the first option to the default option
        $delivery_option = $options[0];

        switch ($code){
            case "delivery":
                foreach ($delivery_option['time'] as $time){
                    if($time['price_comment'] == 'standard'){
                        $time['price']['shipping_method_code'] = $code;
                        $time['price']['text_myparcel_price_delivery'] = date('d-m-Y',strtotime($delivery_option['date'])) . ' - ' . date('H:i',strtotime($time['start'])) . ' - ' .date('H:i',strtotime($time['end']));
                        $session->data['myparcel_price_delivery'] = $time['price'];

                        $myparcel_shipping_choosed['date'] = date('Y-m-d',strtotime($delivery_option['date']));
                        $myparcel_shipping_choosed['time'][] = $time;
                        $myparcel_shipping_choosed['code'] = 'myparcel_'. $code;
                        $myparcel_shipping_choosed['additional_service'] = (isset($session->data['additional_service_checked'][$code])) ? $session->data['additional_service_checked'][$code] : [];
                        $session->data['myparcel_price_delivery']['myparcel_shipping_choosed'] = $myparcel_shipping_choosed;
                        if(!isset($session->data['myparcel_shipping_choosed'])){
                            $session->data['myparcel_shipping_choosed'] = $myparcel_shipping_choosed;
                        }
                        break;
                    }
                }
                break;
            case "pickup":
                $pickup_detail = $delivery_option;
                $time = $pickup_detail['time'][0];
                $time['price']['shipping_method_code'] = $code;
                $time['price']['text_myparcel_price_pickup'] =  $pickup_detail['location'] . ' - ' .date('d-m-Y',strtotime($pickup_detail['date'])) . ' - ' . date('H:i',strtotime($time['start']));
                $session->data['myparcel_price_pickup'] = $time['price'];

                $pickup_detail['code'] = 'myparcel_'. $code;
                $pickup_detail['additional_service'] = [];
                $pickup_detail['time'] = [];
                $pickup_detail['time'][] = $time;
                $session->data['myparcel_price_pickup']['myparcel_shipping_choosed'] = $pickup_detail;
                if(!isset($session->data['myparcel_shipping_choosed'])){
                    $session->data['myparcel_shipping_choosed'] = $pickup_detail;
                }
                break;
        }
    }

    public function getDeliveryNextWorkingDate($order_data, $delivery_type = MyParcel_Shipment_Checkout::DELIVERY_TYPE_STANDARD , $is_pickup = false){
        $disable_days = [0];
        //if delivery option is not standard, explude Saturday when choose the next day working
        if($delivery_type != MyParcel_Shipment_Checkout::DELIVERY_TYPE_STANDARD){
            $disable_days[] = 6;
        }
        $next_working_date = null;

        $key = ($is_pickup) ? 'pickup' : 'delivery';
        $api = MyParcel()->api;
        $delivery_params =  $this->getDeliveryParams($order_data,'shipping_');
        $myparcel_delivery = $api->getDeliveryOptions($delivery_params);
        if(isset($myparcel_delivery['code']) && $myparcel_delivery['code'] == 200 && !isset($myparcel_delivery['body']['errors'])){
            if(count($myparcel_delivery['body']['data'][$key])){
                foreach ($myparcel_delivery['body']['data'][$key] as $delivery){
                    $day_num = date('w', strtotime($delivery['date']));
                    if(!in_array($day_num,$disable_days)){
                        $next_working_date = $delivery['date'];
                        break;
                    }
                }
            }
        }
        return $next_working_date;
    }


    function getCountryIsoCodeFromSession()
    {
        $registry = MyParcel::$registry;
        $session = $registry->get('session');
        $country_code = '';

        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            $address_data = isset($session->data['shipping_address']) ? $session->data['shipping_address'] : (isset($session->data['payment_address']) ? $session->data['payment_address'] : null);
            $country_code = !empty($address_data) ? $address_data['iso_code_2'] : '';//'NL';
        } else {
            if (MyParcel()->helper->isModuleExist('d_quickcheckout', true)) {
                $address_data['address_1'] = $session->data['shipping_address']['address_1'];
                $address_data['postcode'] = $session->data['shipping_address']['postcode'];
                $country_code = $session->data['shipping_address']['iso_code_2'];
            } else {
                if (!empty($session->data['shipping_country_id'])) {
                    $loader = $registry->get('load');

                    if (!empty($session->data['shipping_address_id']) && empty($session->data['guest']['shipping'])) {
                        $address_id = $session->data['shipping_address_id'];
                        $loader->model('account/address');
                        $model_address = $registry->get('model_account_address');
                        $address_data = $model_address->getAddress($address_id);
                        $country_code = $address_data['iso_code_2'];
                    } else {
                        if (!empty($session->data['guest']['shipping']['address_1'])) {
                            $address_data['address_1'] = $session->data['guest']['shipping']['address_1'];
                            $address_data['postcode'] = $session->data['guest']['shipping']['postcode'];
                            $country_code = $session->data['guest']['shipping']['iso_code_2'];
                        }
                    }
                }
            }
        }

        return $country_code;
    }
    public function getTaxRate($tax_rate_id){
        $registry = MyParcel::$registry;
        $db = $registry->get('db');
        $tax_query =$db->query("SELECT * FROM " . DB_PREFIX . "tax_rate WHERE tax_rate_id = '" . (int)$tax_rate_id . "'");

        if ($tax_query->num_rows) {
            return $tax_query->row;
        } else {
            return false;
        }
    }

    public function getHeightMyparcelIframe($sub_total,$iso_code_2){
        $height = 315; //default height

        $shipment_class = MyParcel()->shipment;
        /** @var MyParcel_Shipment_Checkout $checkout_helper **/
        $checkout_helper = $shipment_class->checkout;
        $delivery_prices = $checkout_helper->getDeliveryPrices(false, true, '', true, 0,$sub_total);
        if(isset($delivery_prices[$iso_code_2])){
            foreach ($delivery_prices[$iso_code_2] as $key => $value){
                if($value != 'disabled'){
                    $height += 25;
                }
            }
        }
        return $height.'px';
    }

    public function getUpdateIframeAddressFromSession(){
        $registry = MyParcel::$registry;
        $session = $registry->get('session');
        $result = [];
        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            // Get shipping address from session data if possible
            if (!empty($session->data['shipping_address'])) {
                $shipping_address = $session->data['shipping_address'];
                $country_code = $shipping_address['iso_code_2'];
                $result['postal_code'] = $shipping_address['postcode'];
                $result['cc'] = $country_code;

                $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
                if ($use_addition_address_as_number_suffix == 2) {
                    $result['street'] = isset($shipping_address['address_1']) ? $shipping_address['address_1'] : '';
                    $result['number'] = isset($shipping_address['address_2']) ? $shipping_address['address_2'] : '';
                } else {

                    //'Address field 1' and 'address field 2' will both be used for the full address
                    if($use_addition_address_as_number_suffix == 0){
                        $shipping_address['address_1'] .=  ' ' .$shipping_address['address_2'];
                    }

                    $address_parts = MyParcel()->helper->getAddressComponents($shipping_address['address_1']);
                    $result['number'] = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
                    $result['street'] = isset($address_parts['street']) ? $address_parts['street'] : '';
                }
            }
        } else {
            if (!empty($session->data['shipping_country_id'])) {
                $registry = MyParcel::$registry;
                $loader = $registry->get('load');
                $address_1 = '';

                if (!empty($session->data['shipping_address_id'])) {
                    $address_id = $session->data['shipping_address_id'];
                    $loader->model('account/address');
                    $model_address = $registry->get('model_account_address');
                    $address_data = $model_address->getAddress($address_id);
                    $address_1 = $address_data['address_1'];
                    $country_code = $address_data['iso_code_2'];
                } else {
                    if (!empty($session->data['guest']['shipping']['address_1'])) {
                        $address_1 = $session->data['guest']['shipping']['address_1'];
                        $country_code = $session->data['guest']['shipping']['iso_code_2'];
                    }
                }

                $address_parts = MyParcel()->helper->getAddressComponents($address_1);
                $result['number'] = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
                $result['street'] = isset($address_parts['street']) ? $address_parts['street'] : '';
                $result['postal_code'] = $session->data['shipping_postcode'];
            }
        }
        return $result;
    }
}

return new MyParcel_Helper();
