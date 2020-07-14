<?php

class MyParcel_Shipment_Helper
{
    public function getOrderShipmentData($order_id)
    {
        $registry = MyParcel::$registry;
        $model_order = $registry->get('model_sale_order');
        $order_data = $model_order->getOrder($order_id);

        if (empty($order_data)) {
            return null;
        }

        $shipment = array(
            'recipient' => $this->getRecipient( $order_data ),
            'options'	=> $this->getOptions( $order_data , false, true ),
            'carrier'	=> 1, // default to POSTNL for now
        );

        if ( $pickup = $this->isPickup( $order_id ) ) {
            $shipment['pickup'] = array(
                'postal_code'	=> $pickup['postal_code'],
                'street'		=> $pickup['street'],
                'city'			=> $pickup['city'],
                'number'		=> $pickup['number'],
                'location_name'	=> $pickup['location'],
            );

            if ($order_data['shipping_iso_code_2'] == 'BE') {
                $shipment['pickup']['location_code'] = $pickup['location_code'];
                $shipment['pickup']['retail_network_id'] = $pickup['retail_network_id'];
            }
        }

        // get physical properties
        $shipment['physical_properties'] = $this->getPhysicalProperties($order_id, $shipment['options']['package_type']);
        if(empty($shipment['physical_properties'])){
            unset($shipment['physical_properties']);
        }
        if($shipment['options'] == null){
            return null;
        }
        return $shipment;
    }

    public function getRecipient( $order )
    {
        $connect_email = MyParcel()->settings->export->connect_customer_email;
        $connect_phone = MyParcel()->settings->export->connect_customer_phone;

        $address = array(
            'cc'			=> $order['shipping_iso_code_2'],
            'city'			=> $order['shipping_city'],
            'person'		=> trim( $order['shipping_firstname'] . ' ' . $order['shipping_lastname'] ),
            'company'		=> $order['shipping_company'],
            'email'			=> !empty($connect_email) ? $order['email'] : '',
            'phone'			=> !empty($connect_phone) ? $order['telephone'] : '',
        );

        $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
        $general_custom_field_homenumber_suffix = MyParcel()->settings->general->general_custom_field_homenumber_suffix;
        // MyParcel_Helper $helper
        $helper = MyParcel()->helper;
        $iso_code = @ $order['shipping_iso_code_2'];

        switch ($iso_code) {
            default:
                if ((!empty($order['shipping_address_2']) && is_numeric(($order['shipping_address_2'])) && !$use_addition_address_as_number_suffix) || !$use_addition_address_as_number_suffix) {
                    $order['shipping_address_1'] = $order['shipping_address_1'] . ' ' . $order['shipping_address_2'];
                }

                $address_parts = $helper->getAddressComponents($order['shipping_address_1']);

                if ($use_addition_address_as_number_suffix == 1) {
                    $number_addition = isset($order['shipping_address_2']) ? $order['shipping_address_2'] : '';
                } elseif ($use_addition_address_as_number_suffix == 2) {
                    $address_parts['street'] = isset($order['shipping_address_1']) ? $order['shipping_address_1'] : '';
                    $address_parts['house_number'] = isset($order['shipping_address_2']) ? $order['shipping_address_2'] : '';
//					$number_addition = isset($order['shipping_custom_field']['address_3']) ? $order['shipping_custom_field']['address_3'] : '';
                    $number_addition = isset($order['shipping_custom_field'][$general_custom_field_homenumber_suffix]) ? $order['shipping_custom_field'][$general_custom_field_homenumber_suffix] : '';
                } else {
                    $number_addition = isset($address_parts['number_addition']) ? $address_parts['number_addition'] : '';
                }

                if($helper->isModuleExist('xnlpostcode', true)){
                    $house_number = $helper->getAddressNumberXNLPostcode($order, 'shipping_');
                }else{
                    $house_number = isset($address_parts['house_number']) ? trim($address_parts['house_number']) : '';
                }
                $address_intl = array(
                    'street' => isset($address_parts['street']) ? $address_parts['street'] : '',
                    'number' => $house_number,
                    'number_suffix' => $number_addition,
                    'postal_code' => $order['shipping_postcode'],
                );
        }
        $address = array_merge($address, $address_intl);

        return $address;
    }

    public function getPhysicalProperties($order_id, $package_type){
        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $config = $registry->get('config');

        $default_export_settings = $config->get('module_myparcelnl_fields_export');
        $saved_export_settings = $model_shipment->getSavedExportSettings($order_id);

        $properties = [];
        switch ($package_type){
            case '4':
                if(isset($saved_export_settings['digital_stamp_weight'])){
                    $properties['weight'] = intval($saved_export_settings['digital_stamp_weight']);
                }
                elseif(isset($default_export_settings['digital_stamp_default_weight'])){
                    $properties['weight'] = intval($default_export_settings['digital_stamp_default_weight']);
                }
                break;
            default:
                break;
        }

        return $properties;
    }

    /**
     * Retrieve shipment options from default export settings or saved exported data
     * The returned data will be used to display shipment form on admin order page or send to MyParcel via API
     * @param array $order_data
     * @param boolean $view
     * @return array
     *
     **/
    public function getOptions( $order_data , $view = false , $export = false)
    {
        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        /** @var MyParcel_Shipment_Checkout $checkout_helper **/
        $checkout_helper = MyParcel()->shipment->checkout;

        $config = $registry->get('config');
        $order_id = $order_data['order_id'];
        $default_export_settings = $config->get('module_myparcelnl_fields_export');
        $saved_export_settings = $model_shipment->getSavedExportSettings($order_id);

        // Saved shipment options
        if ((isset($saved_export_settings['package_type']))) {
            $package_type = $saved_export_settings['package_type'];
        }

        // If no shipping method package type is set in saved settings, get it from order default shipment options
        // Define corresponding package type for the current order
        if (empty($package_type)) {
            if (isset($default_export_settings['shipping_methods_package_types'])) {
                $order_shipping_code = $this->getShippingCodeByShippingQuote($order_data['shipping_code']);

                if ($order_shipping_code == 'myparcel_shipping') {
                    $package_type = MyParcel::PACKAGE_TYPE_STANDARD; // 1. package | 2. mailbox package | 3. letter
                } else {

                    foreach ($default_export_settings['shipping_methods_package_types'] as $package_type_key => $package_type_shipping_methods) {
                        if (in_array($order_shipping_code, $package_type_shipping_methods)) {
                            $package_type = $package_type_key;
                            break;
                        }
                    }

                    // If can't find order shipping code in package type shipping methods
                    $package_type = !empty($package_type) ? $package_type : MyParcel::PACKAGE_TYPE_STANDARD;
                }
            } else {
                $package_type = MyParcel::PACKAGE_TYPE_STANDARD; // 1. package | 2. mailbox package | 3. letter
            }
        }

        $shipping_country_code = isset($order_data['shipping_iso_code_2']) ? $order_data['shipping_iso_code_2'] : '';
        // If country is not NL, disable mailbox package
        if ($shipping_country_code != 'NL' && isset($package_type) && ($package_type == MyParcel::PACKAGE_TYPE_MAILBOX || $package_type == MyParcel::PACKAGE_TYPE_DIGITAL_STAMP)  ) {
            $package_type = MyParcel::PACKAGE_TYPE_STANDARD;
        }

        // Always use Standard Package for Pickup and Pickup express delivery types.
        if ( $this->isPickup( $order_id ) ) {
            $package_type = 1;
        }
        // Use shipment options from order when available
        if (!empty($saved_export_settings)) {

            // Parse description
            if (!$view && isset($saved_export_settings['label_description'])) {
                $saved_export_settings['label_description'] = $this->parseDescription($saved_export_settings['label_description'], $order_data);
            }

            $defaults = array(
                'package_type'		=> 1,
                'only_recipient'	=> 0,
                'signature'			=> 0,
                'return'			=> 0,
                'large_format'		=> 0,
                'label_description'	=> '',
                'insured'           => 0,
                'insured_amount_selectbox' => '',
                'insured_amount'	=> 0,
                'age_check'         => 0
            );
            $options = array_merge($defaults, $saved_export_settings);

            // If shipment options from order is not saved, get options from default export settings
        } else {

            // Parse description
            if (isset($default_export_settings['label_description'])) {
                if ($view) {
                    $description = $default_export_settings['label_description'];
                } else {
                    $description = $this->parseDescription($default_export_settings['label_description'], $order_data);
                }
            } else {
                $description = '';
            }

            if (!empty($default_export_settings['insured']) && empty($default_export_settings['insured_amount_selectbox']) && !empty($default_export_settings['insured_amount_custom'])) {
                $insured_amount = $default_export_settings['insured_amount_custom'];
            } elseif (!empty($default_export_settings['insured_amount_selectbox'])) {
                $insured_amount = isset($default_export_settings['insured_amount_selectbox'])? $default_export_settings['insured_amount_selectbox']:0;
            } else {
                $insured_amount = 0;
            }

            $options = array(
                'package_type'		=> isset($package_type)?$package_type:'',
                'only_recipient'	=> (!empty($default_export_settings['address_only'])) ? 1 : 0,
                'signature'			=> (!empty($default_export_settings['signature_delivery'])) ? 1 : 0,
                'return'			=> (!empty($default_export_settings['return_no_answer'])) ? 1 : 0,
                'large_format'		=> (!empty($default_export_settings['extra_large_size'])) ? 1 : 0,
                'label_description'	=> $description,
                'insured'           => (!empty($default_export_settings['insured']))? 1:0,
                'insured_amount_selectbox' => (!empty($default_export_settings['insured_amount_selectbox']))? $default_export_settings['insured_amount_selectbox']:"",
                'insured_amount'	=> $insured_amount,
            );

            if ($view && $insured_amount) {
                $options['insured_amount_custom'] = $insured_amount;
            }
        }

        /**
         * If checkbox "insured" is unchecked, other data about insurance has no meaning
         **/
        if (intval($options['insured']) === 0) {
            if (isset($options['insured_amount'])) {
                unset($options['insured_amount']);
            }
            if (isset($options['insured_amount_selectbox'])) {
                unset($options['insured_amount_selectbox']);
            }
        }

        // Add the insurance parameter to the API
        // If the insured amount is entered
        if ( !isset($options['insurance']) && isset($options['insured_amount']) ) {
            if ($options['insured_amount'] > 0) {
                $options['insurance'] = array(
                    'amount'	=> (int) $options['insured_amount'] * 100,
                    'currency'	=> 'EUR',
                );
            }

            // Actually there are no params called "insured" or "insured_amount" in the API
            // So remove them
            if ($view == false){
                unset($options['insured_amount']);
                unset($options['insured']);
            }else{
                if (isset($options['insurance'])) {
                    unset($options['insurance']);
                }
            }
        }

        // Set insurance amount to int
        if (isset($options['insurance'])) {
            $options['insurance']['amount'] = intval($options['insurance']['amount']);
        }

        // Remove frontend insurance option values
        if ($view == false) {
            if (isset($options['insured_amount'])) {
                unset($options['insured_amount']);
            }
            if (isset($options['insured'])) {
                unset($options['insured']);
            }
            if (isset($options['insured_amount_selectbox'])) {
                unset($options['insured_amount_selectbox']);
            }
            if (isset($options['insured_amount_custom'])) {
                unset($options['insured_amount_custom']);
            }
        }

        // Load delivery options stored from Frontend Checkout
        $myparcel_delivery_options = $model_shipment->getMyParcelDeliveryOptions($order_id);
        //get myparcel shipment type
        if(!empty($myparcel_delivery_options)){
            $myparcel_shipment_type = $model_shipment->getData($order_id,'type');
            if($myparcel_shipment_type != null && $myparcel_shipment_type != $order_data['shipping_code']){
                $myparcel_delivery_options = array();
            }
        }
        // Set delivery type
        $options['delivery_type'] = $checkout_helper->getDeliveryTypeFromSavedData($myparcel_delivery_options);

        // Options for Pickup and Pickup express delivery types:
        // Always enable signature on receipt
        if ( $this->isPickup( $order_id, $myparcel_delivery_options ) ) {
            $options['signature'] = 1;
        }

        //digital stamp type option
        if($options['package_type'] == 4){
            $options = array(
                'package_type' => intval($options['package_type']),
                'label_description' => $options['label_description']
            );
            if(empty($options['label_description'])){
                unset($options['label_description']);
            }
            return $options;
        }

        // delivery date (postponed delivery & pickup)
        if ($delivery_date = $checkout_helper->getDeliveryDateFromSavedData( $myparcel_delivery_options ) ) {
            $date_time = explode(' ', $delivery_date); // split date and time
            if($export){
                if(date('Y-m-d') >= date('Y-m-d',strtotime($date_time[0]))){
                    //if date is outdate, will update it to next working date
                    $next_working_date = MyParcel()->helper->getDeliveryNextWorkingDate($options['delivery_type'],$order_data,$this->isPickup( $order_id ));
                    if($next_working_date == null){
                        return null;
                    }
                    $delivery_time = $date_time[1];
//                    $time_end = '';
//                    //check if the next working date is  saturday, create only standard delivery
//                    if(date('w', strtotime($next_working_date)) == 6){
//                        $delivery_time = '12:00:00';
//                        $time_end = '14:30:00';
//                    }

                    //START update delivery_option in myparcel_shipment table
                    $myparcel_delivery_options['date'] = $next_working_date;
//                    if(!empty($time_end)){
//                        $time_detail_option = $myparcel_delivery_options['time'];
//                        if(count($time_detail_option) == 1){
//                            $time_detail_option = array_shift($myparcel_delivery_options['time']);
//                            $time_detail_option['start'] = $delivery_time;
//                            $time_detail_option['end'] = $time_end;
//                            $time_detail_option['price_comment'] = 'standard';
//                        }
//                        $myparcel_delivery_options['time'][] = $time_detail_option;
//                    }
                    $model_shipment->update($order_id,'delivery_options',$myparcel_delivery_options);
                    //END update delivery_option in myparcel_shipment table

                    $delivery_date = "{$next_working_date} {$delivery_time}";
                    $options['delivery_date'] = $delivery_date;
                }

            }
            // only add if date is in the future
            $timestamp = strtotime($date_time[0]);
            if (time() < $timestamp) {
                $options['delivery_date'] = $delivery_date;
            }
        }

        /**
         * Note that only when no settings saved for a shipment
         * the settings from checkout can be used
         **/
        if (empty($saved_export_settings)) {
            // Get options signed & recipient only from frontend checkout
            $signed = $model_shipment->getMyParcelSigned($order_id);
            if (!empty($signed)) {
                $options['signature'] = 1;
            } elseif ($signed === 0) {
                $options['signature'] = 0;
            }

            $only_recipient = $model_shipment->getMyParcelOnlyRecipient($order_id);
            if (!empty($only_recipient)) {
                $options['only_recipient'] = 1;
            } elseif ($only_recipient === 0) {
                $options['only_recipient'] = 0;
            }
        }

        // PREVENT ILLEGAL SETTINGS
        // Convert numeric strings to int
        $int_options = array( 'package_type', 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format' );
        foreach ($options as $key => &$value) {
            if ( in_array($key, $int_options) ) {
                $value = (int) $value;
            }
        }

        // Disable options for mailbox package and unpaid letter
        if ( $options['package_type'] != 1 ) {
            $illegal_options = array( 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format', 'insurance', 'delivery_date' );
            foreach ($options as $key => $option) {
                if (in_array($key, $illegal_options)) {
                    unset($options[$key]);
                }
            }
        }
        if($shipping_country_code == 'NL' && (((isset($default_export_settings['age_check'])) && $default_export_settings['age_check'] == 1) || ((isset($saved_export_settings['age_check'])) && $saved_export_settings['age_check'] == 1))){
            $options['age_check'] = 1;
            $options['only_recipient'] = 1;
            $options['signature'] = 1;
        }
        else{
            $options['age_check'] = 0;
        }
        return $options;
    }

    /**
     * Save shipment options to table myparcel_shipment
     * Each order has a shipment option
     * @param array $form_data
     **/
    public function saveOptions($form_data = null, $is_ajax = true)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        if (empty($form_data)) {
            extract($_POST);

            // If OC version is 1.0 then decode html before parse_str
            // It is due to the way old jQuery serialize the form data
            if(!version_compare(VERSION, '2.0.0.0', '>=')) {
                $form_data = html_entity_decode($form_data);
            }
            parse_str($form_data, $form_data);
        } else {
            $order_id = $form_data['order_id'];
        }

        // Validate shipment options
        if (isset($form_data['myparcel_options'][$order_id])) {
            $shipment_options = $form_data['myparcel_options'][$order_id];
            // Check signature required for pickup
            /** @var MyParcel_Shipment_Helper $shipment_helper **/
            $shipment_helper = MyParcel()->shipment->shipment_helper;
            $is_pickup = $shipment_helper->isPickup( $order_id );
            if ($is_pickup && (empty($shipment_options['signature']) || empty($shipment_options['only_recipient']))) {
                echo json_encode(
                    array(
                        'status' => 'error',
                        'msg' => MyParcel()->lang->get('error_signature_required'),
                    )
                );
                die;
            }

            // convert insurance option
            if (!empty($shipment_options['insured'])) {
                if (!empty($shipment_options['insured_amount_selectbox'])) {
                    $shipment_options['insurance'] = array(
                        'amount'    => (int) $shipment_options['insured_amount_selectbox'] * 100,
                        'currency'  => 'EUR',
                    );
                    unset($shipment_options['insured_amount']);
                }elseif (!empty($shipment_options['insured_amount_input_not_eu'])) {
                    $shipment_options['insurance'] = array(
                        'amount'    => (int) $shipment_options['insured_amount_input_not_eu'] * 100,
                        'currency'  => 'EUR',
                    );
                    unset($shipment_options['insured_amount']);
                    unset($shipment_options['insured_amount_selectbox']);
                }else{
                    if ((int) $shipment_options['insured_amount_custom']>500) {
                        $shipment_options['insurance'] = array(
                            'amount'    => (int) $shipment_options['insured_amount_custom'] * 100,
                            'currency'  => 'EUR',
                        );
                    }else{
                        $error = MyParcel()->lang->get('error_amount');
                        echo json_encode(
                            array(
                                'status' => 'error',
                                'msg' => $error,
                            )
                        );
                        die;
                    }
                }
            }
            // separate extra options
            if (isset($shipment_options['extra_options'])) {
                $model_shipment->update($order_id, 'extra_options', $shipment_options['extra_options']);
                unset($shipment_options['extra_options']);
            }

            $model_shipment->update($order_id, 'export_settings', $shipment_options);

            if ($is_ajax) {
                echo json_encode(
                    array(
                        'status' => 'success',
                        'msg' => 'success',
                    )
                );
                die;
            }
        }
    }

    /**
     * Check if an order is pickup (pakjemake)
     * @param int order_id
     * @param array $myparcel_delivery_options
     * @return boolean
     **/
    public function isPickup( $order_id = null, $myparcel_delivery_options = array() )
    {
        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        if (empty($myparcel_delivery_options) && !empty($order_id)) {
            $myparcel_delivery_options = $model_shipment->getMyParcelDeliveryOptions($order_id);
        }

        $pickup_types = array( 'retail', 'retailexpress' );
        if ( !empty($myparcel_delivery_options['price_comment']) && in_array($myparcel_delivery_options['price_comment'], $pickup_types) ) {
            return $myparcel_delivery_options;
        } else {
            return false;
        }
    }

    /**
     * Get the shipping method code from the shipping quote retrieved from order data
     * @param string $shipping_quote (flat.flat, free.free, pickup.express, pickup.standard,...)
     * @return string shipping code
     **/
    function getShippingCodeByShippingQuote($shipping_quote)
    {
        $parts = explode('.', $shipping_quote);
        if (is_array($parts) && count($parts) >= 2) {
            return current($parts);
        }
        return '';
    }

    public function getDeliveryType( $order_id, $myparcel_delivery_options = '' )
    {
        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        // Delivery types
        $delivery_types = array(
            'morning'		=> 1,
            'standard'		=> 2,
            'night'			=> 3,
            'retail'		=> 4,
            'retailexpress'	=> 5,
        );

        if (empty($myparcel_delivery_options)) {
            $myparcel_delivery_options = $model_shipment->getMyParcelDeliveryOptions($order_id);
        }

        $delivery_type = 'standard';

        if (!empty($myparcel_delivery_options)) {
            // pickup & pickupexpress store the delivery type in the delivery options,
            // morning & night store it in the time data (...)
            if ( empty($myparcel_delivery_options['price_comment']) ) {
                // check if we have a price_comment in the time option
                $delivery_time = array_shift($myparcel_delivery_options['time']); // take first element in time array
                if (isset($delivery_time['price_comment'])) {
                    $delivery_type = $delivery_time['price_comment'];
                }
            } else {
                $delivery_type = $myparcel_delivery_options['price_comment'];
            }
        }

        // convert to int (default to 2 = standard for unknown types)
        $delivery_type = isset($delivery_types[$delivery_type]) ? $delivery_types[$delivery_type] : 2;

        return $delivery_type;
    }

    public function getShipmentData( $shipment_id, $return_all = false )
    {
        if ($return_all) {
            $shipment_id = (array)$shipment_id;
        }

        try {
            $api = MyParcel()->api;
            $response = $api->getShipments( $shipment_id );

            if (!empty($response['body']['data']['shipments'])) {
                $shipment_id_string = is_array($shipment_id) ? implode(', ', $shipment_id) : $shipment_id;
                MyParcel()->log->add("API response (shipment {$shipment_id_string}):\n" . var_export($response, true));
                $shipments = $response['body']['data']['shipments'];

                if ($return_all) {
                    if ($shipments) {
                        return $shipments;
                    } else {
                        return false;
                    }
                } else {
                    $shipment = array_shift($shipments);
                    // if shipment id matches and status is not concept, get tracktrace barcode and status name
                    if (isset($shipment['id']) && $shipment['id'] == $shipment_id && $shipment['status'] >= 2) {
                        $status = $this->getShipmentStatusName($shipment['status']);
                        $tracktrace = $shipment['barcode'];
                        $shipment_data = compact('shipment_id', 'status', 'tracktrace', 'shipment');
                        return $shipment_data;
                    } else {
                        return false;
                    }
                }

            } else {
                // No shipments found with this ID
                return false;
            }


        } catch (Exception $e) {
            // echo $e->getMessage();
            return false;
        }
    }

    public function getShipmentStatusName( $status_code )
    {
        $lang = MyParcel()->lang;

        $shipment_statuses = array(
            1	=> $lang->get('shipment_status_pending_concept'),
            2	=> $lang->get('shipment_status_pending_registered'),
            3	=> $lang->get('shipment_status_enroute_handed_to_carrier'),
            4	=> $lang->get('shipment_status_enroute_sorting'),
            5	=> $lang->get('shipment_status_enroute_distribution'),
            6	=> $lang->get('shipment_status_enroute_customs'),
            7	=> $lang->get('shipment_status_delivered_at_recipient'),
            8	=> $lang->get('shipment_status_delivered_ready_for_pickup'),
            9	=> $lang->get('shipment_status_delivered_package_picked_up'),
            30	=> $lang->get('shipment_status_inactive_concept'),
            31	=> $lang->get('shipment_status_inactive_registered'),
            32	=> $lang->get('shipment_status_inactive_enroute_handed_to_carrier'),
            33	=> $lang->get('shipment_status_inactive_enroute_sorting'),
            34	=> $lang->get('shipment_status_inactive_enroute_distribution'),
            35	=> $lang->get('shipment_status_inactive_enroute_customs'),
            36	=> $lang->get('shipment_status_inactive_delivered_at_recipient'),
            37	=> $lang->get('shipment_status_inactive_delivered_ready_for_pickup'),
            38	=> $lang->get('shipment_status_inactive_delivered_package_picked_up'),
            99	=> $lang->get('shipment_status_inactive_unknown'),
        );

        if (isset($shipment_statuses[$status_code])) {
            return $shipment_statuses[$status_code];
        } else {
            return $lang->get('shipment_status_inactive_unknown');
        }
    }

    public function getShipmentIdsByOrderIds( $order_ids, $args )
    {
        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $shipment_ids = array();
        foreach ($order_ids as $order_id) {
            $order_shipments = $model_shipment->getSavedMyParcelShipments($order_id);
            if (!empty($order_shipments)) {
                $order_shipment_ids = array();
                // exclude concepts or only concepts
                foreach ( $order_shipments as $key => $shipment) {
                    if (isset($args['exclude_concepts']) && empty($shipment['tracktrace'])) {
                        continue;
                    }
                    if (isset($args['only_concepts']) && !empty($shipment['tracktrace'])) {
                        continue;
                    }

                    $order_shipment_ids[] = $shipment['shipment_id'];
                }

                if (isset($args['only_last'])) {
                    $shipment_ids[] = array_pop( $order_shipment_ids );
                } else {
                    $shipment_ids = $order_shipment_ids;
                }
            }
        }

        return $shipment_ids;
    }

    public function streamPdf ( $pdf_data, $order_ids )
    {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $this->getFilename( ) . '"');
        echo $pdf_data;
        die();
    }

    public function downloadPdf ( $pdf_data, $order_ids )
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $this->getFilename( ) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        echo $pdf_data;
        die();
    }

    public function getFilename ( ) {
        $filename  = 'MyParcel';
        $filename .= '-' . date('Y-m-d H:i:s') . '.pdf';

        return $filename;
    }

    public function getOrderShipments( $order_id, $exclude_concepts = false, $only_last = false )
    {
        if (empty($order_id)) {
            return;
        }

        /** @var ModelMyparcelnlShipment $model_shipment **/
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $consignments = $model_shipment->getSavedMyParcelShipments($order_id);

        if (!empty($consignments)) {
            if ($only_last) {
                $last_consignment = array_pop($consignments);
                if (!empty($last_consignment['tracktrace'])) {
                    return array($last_consignment);
                } else {
                    return null;
                }
            } elseif ($exclude_concepts) {
                if (is_array($consignments)) {
                    foreach ($consignments as $key => $consignment) {
                        if (empty($consignment['tracktrace'])) {
                            unset($consignments[$key]);
                        }
                    }
                }
            }
        }

        return $consignments;
    }

    /**
     * Prepare data array for creating related return shipment
     * @param integer $order_id
     * @param array $options
     * @return array shipment options
     **/
    public function prepareReturnShipmentData( $order_id, $options )
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('sale/order');
        $model_order = $registry->get('model_sale_order');
        $order = $model_order->getOrder($order_id);
        $success = true;
        $errors = array();
        $connect_email = MyParcel()->settings->export->connect_customer_email;

        // set name & email
        $return_shipment_data = array(
            'name'			=> trim( $order['shipping_firstname'] . ' ' . $order['shipping_lastname'] ),
            'email'			=> !empty($connect_email) ? $order['email'] : '',
            'carrier'		=> 1, // default to POSTNL for now
        );

        // add options if available
        if (!empty($options['package_type'])) {

            $package_type = $options['package_type'];
            if ($package_type == MyParcel::PACKAGE_TYPE_STANDARD) {
                $defaults = array(
                    'package_type' => 1,
                    'only_recipient' => 0,
                    'signature' => 0,
                    'return' => 0,
                    'large_format' => 0,
                    'label_description' => '',
                    'insured_amount' => 0,
                );
                $options = array_merge($defaults, $options);

                $options['label_description'] = $this->parseDescription($options['label_description'], $order);

                if (isset($options['insured'])) {

                    $insured = intval($options['insured']);

                    if (!empty($insured)) {

                        $amount_select_box = isset($options['insured_amount_selectbox']) ? intval($options['insured_amount_selectbox']) : 0;

                        if ($amount_select_box) {
                            $insured_amount = intval($amount_select_box);
                        } else {
                            // convert insurance option
                            if (isset($options['insured_amount_custom'])) {
                                $insured_amount = intval($options['insured_amount_custom']);
                                unset($options['insured_amount_custom']);
                            } else {
                                $insured_amount = 0;
                            }

                            if ($insured_amount < 500) {
                                $success = false;
                                $errors[] = MyParcel()->lang->get('error_amount');
                            }
                        }

                        if ($insured_amount > 0) {
                            $options['insurance'] = array(
                                'amount' => $insured_amount * 100,
                                'currency' => 'EUR',
                            );
                        } else {
                            $success = false;
                            $errors[] = MyParcel()->lang->get('error_empty_insured_amount');
                        }
                    }

                    unset($options['insured']);
                }

                if (isset($options['insured_amount'])) {
                    unset($options['insured_amount']);
                }

                if (isset($options['insured_amount_selectbox'])) {
                    unset($options['insured_amount_selectbox']);
                }

                if (isset($options['insured_amount_custom'])) {
                    unset($options['insured_amount_custom']);
                }

                // PREVENT ILLEGAL SETTINGS
                // convert numeric strings to int
                $int_options = array('package_type', 'delivery_type', 'only_recipient', 'signature', 'return', 'large_format');
                foreach ($options as $key => &$value) {
                    if (in_array($key, $int_options)) {
                        $value = (int)$value;
                    }
                }
            } else {
                // If package type is Mailbox package or Letter then remove other additional parameters
                $options = array('package_type' => intval($package_type));
            }

            $return_shipment_data['options'] = $options;
        }

        // get parent
        $shipment_ids = $this->getShipmentIdsByOrderIds( (array) $order_id, array( 'exclude_concepts' => true, 'only_last' => true ) );
        if ( !empty($shipment_ids) ) {
            $return_shipment_data['parent'] = array_pop( $shipment_ids);
        }

        if (!$success) {
            $return_shipment_data = $errors;
        }
        return array($success, $return_shipment_data);
    }

    /**
     * Get latest package type that was exported to MyParcel via API
     * @param int $order_id
     * @return array
     **/
    function getLatestPackageType($order_id)
    {
        $options = $this->getLatestExportedShipmentOptions($order_id);
        if (!empty($options['package_type'])) {
            return $options['package_type'];
        }

        return MyParcel::PACKAGE_TYPE_STANDARD;
    }

    /**
     * Get latest shipment options that were exported to MyParcel via API
     * @param int $order_id
     * @return array
     **/
    function getLatestExportedShipmentOptions($order_id)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $shipment_data = $model_shipment->getSavedMyParcelShipments($order_id);

        if (!empty($shipment_data) && is_array($shipment_data)) {
            $last_one = array_pop($shipment_data);
            if (!empty($last_one['shipment']['options'])) {
                return $last_one['shipment']['options'];
            }
        }

        return false;
    }

    /**
     * Get latest shipment that were exported to MyParcel via API
     * @param int $order_id
     * @return array
     **/
    function getLatestExportedShipment($order_id)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $shipment_data = $model_shipment->getSavedMyParcelShipments($order_id);

        if (!empty($shipment_data) && is_array($shipment_data)) {
            $last_one = array_pop($shipment_data);
            if (!empty($last_one)) {
                return $last_one;
            }
        }

        return false;
    }

    /**
     * Get the total of all products inside an order
     * @param int $order_id
     * @return int total weight (kg) of order
     **/
    public function getTotalOrderWeight($order_id)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('sale/order');
        $loader->model('catalog/product');
        $model_order = $registry->get('model_sale_order');
        $model_product = $registry->get('model_catalog_product');
        $class_weight = $registry->get('weight');
        $class_language = $registry->get('language');

        /** @var Config $config **/
        $config = $registry->get('config');

        $products = $model_order->getOrderProducts($order_id);
        $total_weight = 0;

        foreach ($products as $product) {
            $option_weight = 0;

            $product_info = $model_product->getProduct($product['product_id']);

            if ($product_info) {
                $option_data = array();

                $options = $model_order->getOrderOptions($order_id, $product['order_product_id']);

                foreach ($options as $option) {

                    $value = $option['value'];
                    $option_data[] = array(
                        'name'  => $option['name'],
                        'value' => $value
                    );

                    if((version_compare(VERSION, '1.5.4', '>=') || version_compare(VERSION, '2.0.0.0', '>=')) && version_compare(VERSION, '2.2.0.0', '<')) {
                        $product_option_value_info = $this->getProductOptionValue($product['product_id'], $option['product_option_value_id']);
                    } else {
                        $product_option_value_info = $model_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);
                    }
                    if ($product_option_value_info) {
                        if ($product_option_value_info['weight_prefix'] == '+') {
                            $option_weight += $product_option_value_info['weight'];
                        } elseif ($product_option_value_info['weight_prefix'] == '-') {
                            $option_weight -= $product_option_value_info['weight'];
                        }
                    }
                }

                $weight = ($product_info['weight'] + $option_weight) *  $product['quantity'];
                // Convert to default weight class "kg"
                $total_weight += $class_weight->convert($weight, $product_info['weight_class_id'], $config->get('config_weight_class_id'));
            }
        }

        return $class_weight->format($total_weight, $config->get('config_weight_class_id'), $class_language->get('decimal_point'), $class_language->get('thousand_point'));
    }

    public function getProductOptionValue($product_id, $product_option_value_id) {
        $registry = MyParcel::$registry;
        $db = $registry->get('db');
        $config = $registry->get('config');
        $query = $db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$config->get('config_language_id') . "'");

        return $query->row;
    }

    /**
     * Store the delivery options from checkout page into session
     * @param array $post_data
     * @return boolean save success or not
     **/
    function saveDeliveryOptionsInCheckout($post_data)
    {
        $registry = MyParcel::$registry;
        $session = $registry->get('session');

        if (!empty($post_data['mypa_data'])) {
            $session->data['myparcel']['data'] = $post_data['mypa_data'];

            if (!empty($post_data['mypa_signed'])) {
                $session->data['myparcel']['signed'] = $post_data['mypa_signed'];
            } else {
                if (isset($session->data['myparcel']['signed'])) {
                    unset($session->data['myparcel']['signed']);
                }
            }

            if (!empty($post_data['mypa_recipient_only'])) {
                $session->data['myparcel']['recipient_only'] = $post_data['mypa_recipient_only'];
            } else {
                if (isset($session->data['myparcel']['recipient_only'])) {
                    unset($session->data['myparcel']['recipient_only']);
                }
            }
        }
        return true;
    }

    /**
     * Synchronize shipment data (track&trace status) of orders in the list
     * Note: To improve performance, only orders whose status is "concept" will be synced
     * @param array $orders
     **/
    function syncShipmentForOrders($orders)
    {
        $shipment_list = array();
        $order_list = array();
        $shipments = array();

        foreach ($orders as $order) {
            $order_id = $order['order_id'];
            $latest_shipment = $this->getLatestExportedShipment($order_id);

            if ($latest_shipment && !empty($latest_shipment['shipment_id']) && (empty($latest_shipment['shipment']['barcode']) || empty($latest_shipment['tracktrace']))) {
                $shipment_list[] = $latest_shipment['shipment_id'];
                $order_list[] = $order_id;
            }
        }

        if ($shipment_list) {
            $shipments = $this->getShipmentData($shipment_list, true);
        }

        if (!empty($shipments)) {
            foreach ($shipments as $key => $shipment) {
                if (isset($shipment['id']) && $shipment['status'] >= 2) {
                    $shipment_id = $shipment['id'];
                    $status = $this->getShipmentStatusName($shipment['status']);
                    $tracktrace = $shipment['barcode'];
                    $shipment_data = compact('shipment_id', 'status', 'tracktrace', 'shipment');
                    $order_id = $order_list[$key];

                    /** @var ModelMyparcelnlShipment $model_shipment **/
                    $registry = MyParcel::$registry;
                    $loader = $registry->get('load');
                    $loader->model(MyParcel()->getModelPath('shipment'));
                    $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

                    $model_shipment->saveShipmentData($order_id, $shipment_data);
                }
            }
        }
    }

    /**
     * Parse special string in description such as [ORDER_NR] into corresponding value
     * @param string $desc
     * @param array $order
     * @return string after parsed
     **/
    function parseDescription($desc, $order)
    {
        $replacements = array(
            '[ORDER_NR]'		=> $order['order_id'],
        );

        $description = str_replace(array_keys($replacements), array_values($replacements), $desc);

        return $description;
    }
}
