<?php

use Cart\Cart;

class MyParcel_Shipment_Checkout
{
    const DELIVERY_TYPE_MORNING = 1;
    const DELIVERY_TYPE_STANDARD = 2;
    const DELIVERY_TYPE_NIGHT = 3;
    const DELIVERY_TYPE_PICKUP = 4;
    const DELIVERY_TYPE_PICKUP_EXPRESS = 5;
    const DELIVERY_TYPE_MAILBOX = 6;

    static $delivery_types = array(
        'morning',
        'default',
        'night',
        'pickup',
        'pickup_express',
    );

    static $belgium_delivery_types = array(
        'default',
        'pickup',
    );

    static $delivery_types_as_value = array(
        'morning'           => self::DELIVERY_TYPE_MORNING,
        'default'           => self::DELIVERY_TYPE_STANDARD,
        'night'             => self::DELIVERY_TYPE_NIGHT,
        'avond'             => self::DELIVERY_TYPE_NIGHT,
        'pickup'            => self::DELIVERY_TYPE_PICKUP,
        'pickup_express'    => self::DELIVERY_TYPE_PICKUP_EXPRESS,
        'mailbox'           => self::DELIVERY_TYPE_MAILBOX
    );

    static $delivery_extra_options = array(
        'signed',
        'only_recipient',
    );

    /**
     * Retrieve delivery total as an array of pair of title and price
     * Decide if signed and recipient are enabled
     * @param array $data (this array is retrieved from cart session $session->data['myparcelnl'])
     * @param boolean $price_format
     * @param mixed $order_id number or null
     * @param string $prefix
     * @param boolean $taxIncluded
     * @return array delivery totals
     */
    function getTotalArray($data, $price_format = false, $order_id = null, $prefix = '', $taxIncluded = true)
    {
        // If this request is called from admin, $data is already an array
        // so no need to encode
        if (!is_array($data['data'])) {
            $delivery_options = json_decode(html_entity_decode($data['data']), true);
        } else {
            $delivery_options = $data['data'];
        }

        $registry               = MyParcel::$registry;
		$cart                   = $registry->get('cart');
        $config                 = $registry->get('config');
        $checkout_settings      = $config->get('module_myparcelnl_fields_checkout');
        $belgium_enabled        = !empty($checkout_settings['belgium_enabled']) ? true : false;
        $country_code           = MyParcel()->helper->getCountryIsoCodeFromSession();
        /**
         * If country is BE
         * Then check if the BE setting is enabled
         **/

        if ($belgium_enabled && $country_code == 'BE' && !empty($delivery_options['price_comment'])) {

            $prices = $this->getDeliveryPrices($price_format, true, '', $taxIncluded, $order_id, $cart->getSubTotal());

            $total_array = array();

            if ($delivery_options['price_comment'] == 'standard' && !empty($prices['BE']['default'])) {
                $total_array = array(
                    'default' => array(
                        'title' => MyParcel()->lang->get('entry_total_myparcel_belgium_default'),
                        'price' => $prices['BE']['default'],
                    ),
                );
            } elseif (!empty($prices['BE']['pickup'])) {
                $total_array = array(
                    'pickup' => array(
                        'title' => MyParcel()->lang->get('entry_total_myparcel_belgium_pickup'),
                        'price' => $prices['BE']['pickup'],
                    )
                );
            }

            return $total_array;
        }

        $signed = false;
        $recipient_only = false;

        $delivery_type = $this->getDeliveryTypeFromSavedData($delivery_options);
        if (!empty($data['signed']) && $data['signed'] != 'off') {
            $signed = true;
        }

        // If delivery type is "Standard" then recipient is enabled and otherwise
        if (!empty($data['recipient_only']) && $data['recipient_only'] != 'off' && $delivery_type == 2) {
            $recipient_only = true;
        }

        $total_array = $this->getDeliveryTotalsFromSavedData($delivery_options, $signed, $recipient_only, $price_format, $order_id, $prefix, $taxIncluded);
        return $total_array;
    }

    /**
     * Retrieve delivery total as an array of pair of title and price
     * @param array $myparcel_delivery_options
     * @param boolean $signed
     * @param boolean $recipient_only
     * @param boolean $price_format
     * @return array delivery totals
     */
    public function getDeliveryTotalsFromSavedData($myparcel_delivery_options, $signed = false, $recipient_only = false, $price_format = false, $order_id = null, $prefix = '', $taxIncluded = true)
    {
        /**
         * Important: This function is executed in CATALOG scenario
         **/

        $total_array = array();
        $delivery_type = $this->getDeliveryTypeFromSavedData($myparcel_delivery_options);
        $delivery_type_text = array_search($delivery_type, self::$delivery_types_as_value);
        if (!$delivery_type_text) {
            $delivery_type_text = 'default';
        }

		$registry = MyParcel::$registry;
		$cart = $registry->get('cart');

        if (!empty($order_id) && is_numeric($order_id)) {
            $registry = MyParcel::$registry;
            $loader = $registry->get('load');
            $loader->model(MyParcel()->getModelPath('shipment'));
            $loader->model(MyParcel()->getModelPath('helper'));
            /** @var ModelMyparcelnlShipment $model_shipment **/
            $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
            $prices = $model_shipment->getMyParcelOrderPrices($order_id);

            $model_helper = $registry->get('model_extension_myparcelnl_helper');
            $country_code = $model_helper->getOrderCountryIsoCode($order_id);

            if (isset($prices[$country_code])) {
                $prices = $prices[$country_code];
            }

            if (!empty($prices) && $prefix) {
                foreach ($prices as &$price_text){
                    $price_text = $prefix . $price_text;
                }
            }
        }

        if (empty($prices)) {
            $prices = $this->getDeliveryPrices($price_format, false, $prefix, $taxIncluded, $order_id, $cart->getSubTotal()); // Get Prices without formatting
            $country_code           = MyParcel()->helper->getCountryIsoCodeFromSession();
            $prices = !empty($country_code) ? $prices[$country_code] : $prices;
        }

        if (!empty($prices[$delivery_type_text])) {
            $total_array[$delivery_type_text] = array(
                'title' => MyParcel()->lang->get('entry_total_myparcel_' . $delivery_type_text),
                'price' => $prices[$delivery_type_text]
            );

        } else {
            // MAILBOX mode
            $mailbox_fee = floatval(MyParcel()->settings->checkout->mailbox_fee);
            if ($price_format) {
                $mailbox_fee = $this->formatDeliveryPrice($mailbox_fee, null, true, $prefix);
            }
            if ($delivery_type == self::DELIVERY_TYPE_MAILBOX) {
                $total_array['mailbox'] = array(
                    'title' => MyParcel()->lang->get('entry_total_myparcel_mailbox'),
                    'price' => $mailbox_fee
                );
            }
        }

        if ($delivery_type_text != 'pickup' && $delivery_type_text != 'pickup_express' && $delivery_type != self::DELIVERY_TYPE_MAILBOX) {
            if ($signed) {
                if (!empty($prices['signed'])) {
                    $total_array['signed'] = array(
                        'title' => MyParcel()->lang->get('entry_total_myparcel_signed'),
                        'price' => $prices['signed'],
                    );
                }
            }

            if ($recipient_only) {
                if (!empty($prices['only_recipient'])) {
                    $total_array['only_recipient'] = array(
                        'title' => MyParcel()->lang->get('entry_total_myparcel_recipient_only'),
                        'price' => $prices['only_recipient'],
                    );
                }
            }
        }

        return $total_array;
    }

    /**
     * Retrieve delivery type in Integer
     * @param array $myparcel_delivery_options
     * @return int delivery type as a number
     */
    public function getDeliveryTypeFromSavedData( $myparcel_delivery_options )
    {
        $delivery_types = self::$delivery_types_as_value;

        $delivery_type = 'standard';
        if (!empty($myparcel_delivery_options)) {
            // Regular
            if ( empty($myparcel_delivery_options['price_comment']) && !empty($myparcel_delivery_options['time']) ) {
                // check if we have a price_comment in the time option
                $delivery_time = ($myparcel_delivery_options['time']);
                if(count($delivery_time) == 1){
                    $delivery_time = array_shift($myparcel_delivery_options['time']);
                }
                if (isset($delivery_time['price_comment'])) {
                    $delivery_type = $delivery_time['price_comment'];
                }
                // Pickup
            } elseif ( !empty($myparcel_delivery_options['price_comment'] ) ) {
                $delivery_type = $myparcel_delivery_options['price_comment'];
                switch ($delivery_type) {
                    case 'retail':
                        $delivery_type = 'pickup';
                        break;
                    case 'retailexpress':
                        $delivery_type = 'pickup_express';
                        break;
                }
            }
        }

        // convert to int (default to 2 = standard for unknown types)
        $delivery_type = isset($delivery_types[$delivery_type]) ? $delivery_types[$delivery_type] : 2;

        return $delivery_type;
    }

    /**
     * Retrieve delivery date (Y-m-d)
     * @param array $myparcel_delivery_options
     * @return date delivery date
     */
    public function getDeliveryDateFromSavedData($myparcel_delivery_options)
    {
        if ( !empty($myparcel_delivery_options) && !empty($myparcel_delivery_options['date']) ) {
            $delivery_date = $myparcel_delivery_options['date'];

            $delivery_type = $this->getDeliveryTypeFromSavedData( $myparcel_delivery_options );
            if ( in_array($delivery_type, array(self::DELIVERY_TYPE_MORNING, self::DELIVERY_TYPE_NIGHT)) && !empty($myparcel_delivery_options['time']) ) {
                $delivery_time_options = ($myparcel_delivery_options['time']);
                if(count($delivery_time_options) == 1){
                    $delivery_time_options = array_shift($myparcel_delivery_options['time']);
                }
                $delivery_time = $delivery_time_options['start'];
            } else {
                $delivery_time = '00:00:00';
            }
            $delivery_date = "{$delivery_date} {$delivery_time}";
            return $delivery_date;
        } else {
            return false;
        }
    }

    /**
     * Retrieve delivery prices that are set in admin settings
     * @param boolean $price_format (add currency symbol and convert dot/comma)
     * @param boolean $color_format (add color code into price)
     * @param string prefix
     * @return array prices of delivery types
     */
    function getDeliveryPrices($price_format = true, $color_format = true, $prefix = '', $addTax = true, $order_id = 0, $value = 0)
    {
        $registry = MyParcel::$registry;
        /** @var \Cart\Cart $cart **/
        $cart = $registry->get('cart');
        /** @var \Cart\Currency $currency **/
        $currency = $registry->get('currency');
        $config = $registry->get('config');
        $checkout_settings          = $config->get('module_myparcelnl_fields_checkout');
        $price_options = array_merge( self::$delivery_extra_options, self::$delivery_types );
        $prices = array();

        // default settings
        $price_type = 0;
        $subtotal1_min = isset($checkout_settings['subtotal1_min']) ? $checkout_settings['subtotal1_min'] : 0;
        $subtotal1_max = isset($checkout_settings['subtotal1_max']) ? $checkout_settings['subtotal1_max'] : 50;
        $subtotal2_min = isset($checkout_settings['subtotal2_min']) ? $checkout_settings['subtotal2_min'] : 50.01;
        $subtotal2_max = isset($checkout_settings['subtotal2_max']) ? $checkout_settings['subtotal2_max'] : 1000000;

        if($order_id > 0) {
            $loader = $registry->get('load');
            $loader->model('checkout/order');
            $model_order = $registry->get('model_checkout_order');
            $order = $model_order->getOrder($order_id);
            $value = $order['total'];

            if($subtotal2_min <= $value && $value < $subtotal2_max)
                $price_type = 1;
        }else{
            if($subtotal2_min <= $value && $value < $subtotal2_max)
                $price_type = 1;
        }

        foreach ($price_options as $key => $option) {
            // JS API correction
            /* if ($option == 'standard') {
                 $option = 'default';
             }*/

            $option_enabled = (!empty($checkout_settings[$option.'_enabled'])) ? true : false;

            if ($option_enabled) {
                if ($price_type == 0 && (float)$checkout_settings[$option . '_fee'] >= 0) {
                    $fee = (float)$checkout_settings[$option . '_fee'];

                    if($fee >= 0) {
                        $fee = $this->convertPriceToFloat($fee);

                        //$fee += $this->getMyParcelShippingCost();
                        if ($addTax) {
                            $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                        } else {
                            $fee_including_tax = $fee;
                        }

                        if ($price_format) {
                            $formatted_fee = $this->formatDeliveryPrice($fee_including_tax, $currency, $color_format, $prefix);
                        } else {
                            $formatted_fee = $fee_including_tax;
                        }

                        $prices[$option] = $formatted_fee;
                    }
                }

                if ($price_type == 1 && (float)($checkout_settings[$option . '_fee2']) >= 0 ) {
                    $fee = (float)$checkout_settings[$option . '_fee2'];

                    if($fee >= 0) {
                        $fee = $this->convertPriceToFloat($fee);

                        //$fee += $this->getMyParcelShippingCost();
                        if ($addTax) {
                            $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                        } else {
                            $fee_including_tax = $fee;
                        }

                        if ($price_format) {
                            $formatted_fee = $this->formatDeliveryPrice($fee_including_tax, $currency, $color_format, $prefix);
                        } else {
                            $formatted_fee = $fee_including_tax;
                        }

                        $prices[$option] = $formatted_fee;
                    }
                }
            } else {
                if (in_array($option, self::$delivery_extra_options)) {
                    $prices[$option] = 'disabled';
                }
            }
        }

        if (empty($prices)) {
            $prices['pickup'] = '';
            $prices['pickup_express'] = '';
        }

        $belgium_enabled = !empty($checkout_settings['belgium_enabled']) ? true : false;

        if ($belgium_enabled) {
            $price_options = array_merge( self::$belgium_delivery_types );
            $be_prices = array();

            foreach ($price_options as $option) {

                $formatted_fee = '';

                if ($price_type == 0 && (float)($checkout_settings['belgium_' . $option . '_fee']) >= 0) {
                    $fee = (float)$checkout_settings['belgium_' . $option . '_fee'];
                    $fee = $this->convertPriceToFloat($fee);
//                    $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                    if ($addTax) {
                        $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                    } else {
                        $fee_including_tax = $fee;
                    }
                    //$fee_including_tax = $fee;
                    if ($price_format) {
                        $formatted_fee = $this->formatDeliveryPrice($fee_including_tax, $currency, $color_format, $prefix);
                    } else {
                        $formatted_fee = $fee_including_tax;
                    }
                }

                if ($price_type == 1 && (float)($checkout_settings['belgium_' . $option . '_fee2']) >= 0) {
                    $fee = (float)$checkout_settings['belgium_' . $option . '_fee2'];
                    $fee = $this->convertPriceToFloat($fee);
//                    $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                    if ($addTax) {
                        $fee_including_tax = $this->getTotalDeliveryTaxAmountFromCart($fee, $cart);
                    } else {
                        $fee_including_tax = $fee;
                    }
                    //$fee_including_tax = $fee;
                    if ($price_format) {
                        $formatted_fee = $this->formatDeliveryPrice($fee_including_tax, $currency, $color_format, $prefix);
                    } else {
                        $formatted_fee = $fee_including_tax;
                    }
                }

                $be_prices[$option] = $formatted_fee;
            }

            return array(
                'NL' => $prices,
                'BE' => $be_prices
            );
        }

        return array(
            'NL' => $prices,
        );
    }

    /**
     * Converts price string to float value, assuming no thousand-separators used
     * @param float $price
     * @return float price after converted
     */
    function convertPriceToFloat( $price )
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);

        return $price;
    }

    /**
     * Calculate a price base on taxes that affect in cart session
     * @param float $deliveryFee
     * @param Cart $cart
     * @return float price with taxes included
     */
    function getTotalDeliveryTaxAmountFromCart($deliveryFee, $cart)
    {
        if (! $deliveryFee) {
            return 0;
        }

        $registry = MyParcel::$registry;
        $tax = $registry->get('tax');

        if ($cart instanceof Cart && ($taxes = $cart->getTaxes())) {

            return $tax->calculate($deliveryFee, $taxes, true);
        }

        return $deliveryFee;
    }

    function formatDeliveryPrice($price, $currency = null, $color_format = true, $prefix = '')
    {
        $registry = MyParcel::$registry;
        if (!$currency) {
            /** @var \Cart\Currency $currency **/
            $currency = $registry->get('currency');
        }

        // Color format may be used in future
        if(version_compare(VERSION, '2.0.0.0', '>=')) {
            $session = $registry->get('session');
            //$code = isset($session->data['currency']) ? $session->data['currency'] : 'EUR';
            $code = 'EUR';
            return $prefix . $currency->format($price, $code);
        } else {
            //$code = $currency->getCode();
            $code = 'EUR';
            return $prefix . $currency->format($price, $code);
        }
    }


    /**
     * Convert delivery type from text to int
     * @param string $type
     * @return int delivery type
     **/
    function getDeliveryTypeCode($type)
    {
        $delivery_types = self::$delivery_types_as_value;
        if (isset($delivery_types[$type])) {
            return $delivery_types[$type];
        }
        return 2;
    }

    /**
     * Check if selected shipping method is valid to Parcel
     * @param string $package_type ('standard' | 'others')
     * @return boolean true if current shipping method is valid to Parcel | false otherwise
     * @return int package type connected to the current shipping method
     **/
    function checkValidShippingMethod($package_type = 'standard')
    {
        // Check shipping methods for Parcel
        // Checkout shipping method must be one of shipping methods for Parcel (set in Admin)
        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $session = $registry->get('session');

        $export_default_settings = $config->get('module_myparcelnl_fields_export');

        if (!empty($session->data['shipping_method'])) {
            $shipping_method_quote = $session->data['shipping_method']['code'];
            $checkout_shipping_method = MyParcel()->shipment->shipment_helper->getShippingCodeByShippingQuote($shipping_method_quote);
        } else {
            $checkout_shipping_method = null;
        }

        if ($package_type == 'standard') {
            /*if (!empty($export_default_settings['shipping_methods_package_types'][1])) {
                // Shipping methods associated with parcels = enable delivery options
                $delivery_options_shipping_methods = $export_default_settings['shipping_methods_package_types'][1];
            } else {
                $delivery_options_shipping_methods = array();
            }

            if (in_array($checkout_shipping_method, $delivery_options_shipping_methods)) {
                return true;
            }*/

            if ($checkout_shipping_method == 'myparcel_shipping') {
                return true;
            }
            return false;
        } else {
            $other_types = array(
                MyParcel::PACKAGE_TYPE_MAILBOX,
                MyParcel::PACKAGE_TYPE_LETTER
            );

            foreach ($other_types as $other_type) {
                if (!empty($export_default_settings['shipping_methods_package_types'][$other_type])) {
                    // Shipping methods associated with parcels = enable delivery options
                    $delivery_options_shipping_methods = $export_default_settings['shipping_methods_package_types'][$other_type];
                    if (in_array($checkout_shipping_method, $delivery_options_shipping_methods)) {
                        $package_type = $other_type;
                        return $package_type;
                    }
                }
            }

            return MyParcel::PACKAGE_TYPE_STANDARD;
        }
    }

    /**
     * Check if the provided shipping method is valid to show in shipping method list on checkout
     * Shipping method must belong to at least one of 3 package types set in Admin
     * If one of the quote belonging to shipping method is not valid, ignore the shipping method
     * @param array $shipping_method
     *      array(4) { ["title"]=> string(9) "Flat Rate" ["quote"]=> array(1) { ["flat"]=> array(5) { ["code"]=> string(9) "flat.flat" ["title"]=> string(18) "Flat Shipping Rate" ["cost"]=> string(4) "5.00" ["tax_class_id"]=> string(1) "9" ["text"]=> string(7) "8.00€" } } ["sort_order"]=> string(1) "1" ["error"]=> bool(false) }
     * @return boolean whether the total
     **/
    function isVisibleShippingQuote($shipping_method)
    {
        return true;

        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $export_default_settings = $config->get('module_myparcelnl_fields_export');

        foreach ($shipping_method['quote'] as $quote) {
            $quote_code = $quote['code'];
            $shipping_code = MyParcel()->shipment->shipment_helper->getShippingCodeByShippingQuote($quote_code);
            if ($shipping_code == 'myparcel_shipping') {
                return true;
            }

            if ($shipping_code) {
                if (!empty($export_default_settings['shipping_methods_package_types'])) {

                    foreach ($export_default_settings['shipping_methods_package_types'] as $package_type) {

                        if (!empty($package_type)) {
                            // Shipping methods associated with parcels = enable delivery options
                            $delivery_options_shipping_methods = $package_type;
                        } else {
                            $delivery_options_shipping_methods = array();
                        }

                        if (empty($delivery_options_shipping_methods)) {
                            return true;
                        }

                        if (in_array($shipping_code, $delivery_options_shipping_methods)) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Check if the provided shipping method is associated with Parcel
     * @param array $shipping_method
     * @return boolean whether the total
     **/
    function isParcelShippingMethod($shipping_method)
    {
        foreach ($shipping_method['quote'] as $quote) {
            $registry = MyParcel::$registry;
            //$config = $registry->get('config');
            //$export_default_settings = $config->get('module_myparcelnl_fields_export');

            $quote_code = $quote['code'];
            $shipping_code = MyParcel()->shipment->shipment_helper->getShippingCodeByShippingQuote($quote_code);

            if ($shipping_code == 'myparcel_shipping') {
                return $quote_code;
            }
            /*if (!empty($export_default_settings['shipping_methods_package_types'][1])) {
                $delivery_options_shipping_methods = $export_default_settings['shipping_methods_package_types'][1];
                if (in_array($shipping_code, $delivery_options_shipping_methods)) {
                    return $quote_code;
                }
            }*/
        }
        return false;
    }

    /**
     * Unset any invalid shipping method that does not belong to any Parcel / Mailbox / Letter
     * Using function isVisibleShippingQuote() to check
     * @param array $shipping_methods
     * @return array list of valid shipping_methods
     **/
    function cleanShippingMethods($shipping_methods)
    {
        foreach ($shipping_methods as $key => $shipping_method) {
            if (!$this->isVisibleShippingQuote($shipping_method)) {
                unset($shipping_methods[$key]);
            }
        }

        return $shipping_methods;
    }

    /**
     * Save delivery options from checkout into database
     * @param int $order_info
     * @return boolean success or not
     **/
    function addDeliveryDataIntoOrder($order_info)
    {
        $registry = MyParcel::$registry;
		$cart = $registry->get('cart');
        $session = $registry->get('session');
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        /**
         * If selected shipping method is connected to Parcel
         * Save delivery options to order database
         **/
        if ($this->checkValidShippingMethod()) {
            $session = $registry->get('session');

            if (empty($session->data['myparcel'])) {
                return true;
            }

            $data = $session->data['myparcel'];
            $delivery_options = json_decode(html_entity_decode($data['data']), true);
            $delivery_type = $this->getDeliveryTypeFromSavedData($delivery_options);

            /**
             * If Mailbox mode is available, change the default shipment options of the current order to Mailbox
             * Mailbox can be enabled by select it on delivery options
             **/
            if ($delivery_type == self::DELIVERY_TYPE_MAILBOX) {
                $signed = 0;
                $recipient_only = 0;
                $model_shipment->update($order_info['order_id'], 'export_settings', array('package_type' => MyParcel::PACKAGE_TYPE_MAILBOX, 'label_description' => $this->getDefaultDescription()));

            } elseif ($delivery_type == self::DELIVERY_TYPE_PICKUP || $delivery_type == self::DELIVERY_TYPE_PICKUP_EXPRESS) {
                $registry = MyParcel::$registry;
                $config = $registry->get('config');
                $export_default_settings = $config->get('module_myparcelnl_fields_export');
                $signed = 1; // Signature is required for pickup and pickup express
                $recipient_only = 1; // Recipient_only is required for pickup and pickup express

            } else {
                $signed = (!empty($data['signed']) && ($data['signed'] == 'on' || $data['signed'] == 1) ? 1 : 0);
                $recipient_only = ((!empty($data['recipient_only']) && ($data['recipient_only'] == 'on' || $data['recipient_only'] == 1)) || in_array($delivery_type, array(self::DELIVERY_TYPE_MORNING, self::DELIVERY_TYPE_NIGHT)) ? 1 : 0);
            }

            // Save current prices into order
            /**
             * @var MyParcel_Shipment_Checkout $checkout;
             **/
            $checkout = MyParcel()->shipment->checkout;
            $current_prices = $checkout->getDeliveryPrices(true, false, '', false, $order_info['order_id'], $cart->getSubTotal());

            /**
             * Reset the delivery options in session after saving it to database
             **/
            $session->data['myparcel'] = null;

            return $model_shipment->saveDeliveryOptions($order_info['order_id'], $delivery_options, $signed, $recipient_only, $current_prices);

            /**
             * If selected shipping method is connected to Mailbox or Unpaid letter
             * Change the default shipment options of the current order to Mailbox or Unpaid letter
             * Else do nothing (default export setting will be used when exporting)
             **/
        } else {
            $package_type = $this->checkValidShippingMethod('others');
            if (in_array($package_type, array(MyParcel::PACKAGE_TYPE_MAILBOX, MyParcel::PACKAGE_TYPE_LETTER))) {
                $model_shipment->update($order_info['order_id'], 'export_settings', array('package_type' => $package_type, 'label_description' => $this->getDefaultDescription() ));
            }

            /**
             * Reset the delivery options in session after saving it to database
             **/
            $session->data['myparcel'] = null;

            return true;
        }
        return false;
    }

    /**
     * Retrieve delivery options from checkout from database
     * @param int $order_id
     * @return array delivery options
     **/
    function getDeliveryDataFromOrder($order_id)
    {
        $registry = MyParcel::$registry;

        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        return $model_shipment->getMyParcelDeliveryOptionsSignedRecipientOnly($order_id);
    }

    /**
     * Retrieve delivery options from checkout from database
     * @return boolean whether mailbox mode is enabled and the weight is suitable
     **/
    function isMailboxAvailable()
    {
        if (!MyParcel()->settings->checkout->mailbox_enabled) {
            return false;
        }
        $registry = MyParcel::$registry;
        /** @var \Cart\Cart $cart **/
        $cart = $registry->get('cart');
        $weight_limit = MyParcel()->settings->checkout->mailbox_weight;
        if (empty($weight_limit)) {
            $weight_limit = 0;
        }

        return ($cart->getWeight() <= $weight_limit);
    }

    /**
     * Retrieve "description" from default export settings
     * @return string description attribute
     **/
    function getDefaultDescription()
    {
        $registry = MyParcel::$registry;
        $config = $registry->get('config');

        $export_default_settings = $config->get('module_myparcelnl_fields_export');
        if (!empty($export_default_settings['label_description'])) {
            return $export_default_settings['label_description'];
        }

        return '';
    }

    /**
     * Check if MyParcel Total is applied for the selected shipping method
     * If it is applied, other Shipping Total need to be ignored
     * @param array $total
     * @return boolean if MyParcel Total is applied or not
     **/
    function appliedByParcel($total)
    {
        if(version_compare(VERSION, '2.0.0.0', '>=')) {
            $totals = $total['totals'];
        } else {
            $totals = $total;
        }

        foreach ($totals as $total_item) {
            if (strpos($total_item['code'],'myparcel_total') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set delivery options form order to session
     * This should help to re-calculate totals
     * @param int $order_id
     * @return void
     **/
    function setSessionOrderDeliveryOptions($order_id)
    {
        if (!MyParcel()->helper->isModuleExist('myparcelnl')) {
            return;
        }

        // Check if MyParcelNL is enabled
        $registry = MyParcel::$registry;
        $session = $registry->get('session');
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        // Load models
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $delivery_options = $model_shipment->getMyParcelDeliveryOptionsSignedRecipientOnly($order_id);
        $delivery_options['data'] = json_encode($delivery_options['data']);
        if (empty($delivery_options['data']) || $delivery_options['data'] === 'null') {
            return ;
        }

        $session->data['myparcel'] = $delivery_options;
    }

    function getMyParcelShippingCost()
    {
        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $cost = $config->get('shipping_myparcel_shipping_cost');
        if (!empty($cost)) {
            return $cost;
        }
        return 0;
    }
}
