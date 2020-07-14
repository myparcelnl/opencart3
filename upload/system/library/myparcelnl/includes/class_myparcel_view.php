<?php
include_once 'class_myparcel_view_core.php';
require_once DIR_SYSTEM . 'library/myparcelnl/includes/class_myparcel_shipment_helper.php';
class MyParcel_View extends MyParcel_View_Core
{
    function order_details_myparcel_actions($order_id)
    {
        $buttons = $this->column_myparcel($order_id, 'order_detail');

        ob_start();
        $this->render('view_order_details_myparcel_actions', array('order_id' => $order_id, 'buttons' => $buttons));
        $html = ob_get_clean();

        return $html;
    }

    function print_batch()
    {
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $session = $registry->get('session');
        $url = MyParcel()->url;
        $token = $session->data['user_token'];

        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            $formAction = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'printBatch'), array('user_token' => $token));
        } else {
            $formAction = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'exportPrintBatchHelper'), array('user_token' => $token));
        }

        ob_start();
        $this->render('view_print_batch', array('formAction' => $formAction,'position_label_title' => $lang->get('entry_title_choose_position_label')));
        $html = ob_get_clean();
        return $html;
    }

    function export_batch()
    {
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $session = $registry->get('session');
        $url = MyParcel()->url;
        $token = $session->data['user_token'];
        $formAction = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'exportBatch'), array('user_token' => $token));
        ob_start();
        $this->render('view_export_batch', array('formAction' => $formAction));
        $html = ob_get_clean();
        return $html;
    }

    function export_print_batch()
    {
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $session = $registry->get('session');
        $url = $registry->get('url');
        $token = $session->data['user_token'];
        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            $formAction = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'exportPrintBatch'), array('user_token' => $token));
        } else {
            $formAction = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'exportPrintBatch'), array('user_token' => $token));
        }
        ob_start();
        $this->render('view_export_print_batch', array('formAction' => $formAction));
        $html = ob_get_clean();
        return $html;
    }

    function column_myparcel($order_id, $screen = 'order_overview')
    {
        /**
         * @var MyParcel_Helper $helper
         */

        $registry = MyParcel::$registry;

        // Load language package of MyParcel module
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        // Load other packages
        $url = MyParcel()->url;
        $session = $registry->get('session');
        $helper = MyParcel()->helper;

        // Load models
        $model_order = $registry->get('model_sale_order');
        /** @var MyParcel_Shipment_Helper $shipment_helper **/
        $shipment_helper = MyParcel()->shipment->shipment_helper;

        // Get order data
        $order_data = $model_order->getOrder($order_id);
        $order_id = $order_data['order_id'];

        if (empty($order_data)) {
            return;
        }

        $shipping_country_code = isset($order_data['shipping_iso_code_2']) ? $order_data['shipping_iso_code_2'] : '';
        if (!$helper->isEUCountry($shipping_country_code)) {
            return;
        }

        $token = $session->data['user_token'];
        $setting_download_display = MyParcel()->settings->general->pdf;
        $option_download_target = !empty($setting_download_display) ? 'download' : 'display';

        $listing_actions = array(
            'add_shipment'		=> array (
                'url'		=> $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'shipment'), array('action' => 'add_shipment', 'user_token' => $token, 'order_ids[]' => $order_id)),
                'img'		=> MyParcel()->getImageUrl() . 'myparcel-up.png',
                'alt'		=> $lang->get('entry_title_button_export'),
                'target'    => '',
                'loader'    => MyParcel()->getImageUrl() . 'myparcel-spin.gif',
                'class_name'=> 'btn-myparcel-' . $order_id . ' btn-myparcel-action'
            ),
            'get_labels'	=> array (
                'url'		=> $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'shipment'), array('action' => 'get_labels', 'user_token' => $token, 'order_ids[]' => $order_id)),
                'img'		=> MyParcel()->getImageUrl() . 'myparcel-pdf.png',
                'alt'		=> $lang->get('entry_title_button_print'),
                'target'    => ($option_download_target == 'display' ? 'target="_blank"' : ''),
                'class_name'=> 'btn-myparcel-' . $order_id
            ),
            'add_return'	    => array (
                'url'		    => $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'shipment'), array('action' => 'add_return', 'user_token' => $token, 'order_ids[]' => $order_id)),
                'img'		    => MyParcel()->getImageUrl() . 'myparcel-retour.png',
                'alt'	    	=> $lang->get('entry_title_button_return'),
                'target'        => '',
                'loader'        => MyParcel()->getImageUrl() . 'myparcel-spin.gif',
                'popup_loader'  => MyParcel()->getImageUrl() . 'loading.gif',
                'class_name'    => 'btn-myparcel-' . $order_id . ' btn-myparcel-return'
            ),
        );

        $consignments = $shipment_helper->getOrderShipments($order_id);

        if (empty($consignments)) {
            unset($listing_actions['get_labels']);
        }

        $processed_shipments = $shipment_helper->getOrderShipments($order_id, true, true);
        $latest_package_type = $shipment_helper->getLatestPackageType($order_id);

        if (empty($processed_shipments) || $order_data['shipping_iso_code_2'] != 'NL' || $latest_package_type != MyParcel::PACKAGE_TYPE_STANDARD) {
            unset($listing_actions['add_return']);
        }

        $listing_position_label = array();
        if(isset($listing_actions['get_labels'])){
            $listing_position_label = array(
                'title'     => $lang->get('entry_title_choose_position_label'),
                'url' => $listing_actions['get_labels']['url'],
            );
        }

        ob_start();
        $this->render('view_column_myparcel', array('listing_actions' => $listing_actions,'listing_position_label' => $listing_position_label ,'order_id' => $order_id, 'screen' => $screen));
        $html = ob_get_clean();

        return $html;
    }

    function myparcel_popup_modal()
    {
        ob_start();
        $this->render('view_column_myparcel_popup');
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Render shipment options for the popup content
     * when admin clicks on button "Return" (Related return shipments).
     * This function is able to render multiple order ids
     * @param array $order_ids
     * @return HTML of shipment options form
     **/
    function return_shipment_form($order_ids, $screen)
    {
        $total = count($order_ids);
        $html = '<div class="return-shipment-form-wrapper">';
        foreach ($order_ids as $order_id)
        {
            $html .= '<h4><strong>' . MyParcel()->lang->get('entry_order') . ' #' . $order_id . '</strong></h4>';
            //TODO also render summary order table
            $html .= $this->ship_to_myparcel($order_id, true, $screen);
            if (($total) > 1) {
                $html .= '<br/><hr>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    function ship_to_myparcel($order_id, $return = false, $screen = 'order_overview')
    {
        /**
         * @var MyParcel_Helper $helper
         */
        $registry = MyParcel::$registry;

        // Load other packages
        $url = MyParcel()->url;
        $session = $registry->get('session');
        $helper = MyParcel()->helper;

        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $loader->model('sale/order');

        $model_order = $registry->get('model_sale_order');
        /** @var MyParcel_Shipment_Helper $shipment_helper **/
        $shipment_helper = MyParcel()->shipment->shipment_helper;
        $order_data = $model_order->getOrder($order_id);
        $recipient = $shipment_helper->getRecipient($order_data);
        $is_pickup = $shipment_helper->isPickup( $order_id );
        $extra_options = $model_shipment->getSavedExtraExportSettings($order_id);

        $export_settings = $shipment_helper->getOptions($order_data, true);

        if (!empty($export_settings['insured_amount_selectbox'])) {
            unset($export_settings['insured_amount_custom']);
        }

        if ($return) {
            $export_settings['package_type'] = $shipment_helper->getLatestPackageType($order_id);
        }

        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $data = array();
        $data['entry_order_myparcel_shipment_type']     = $lang->get('entry_order_myparcel_shipment_type');
        $data['entry_order_myparcel_calculated_weight'] = $lang->get('entry_order_myparcel_calculated_weight');
        $data['entry_order_myparcel_number_of_label']   = $lang->get('entry_order_myparcel_number_of_label');
        $data['entry_order_myparcel_custom_id']         = $lang->get('entry_order_myparcel_custom_id');

        $data['entry_order_myparcel_text_extra_large_size'] = $lang->get('entry_order_myparcel_text_extra_large_size');
        $data['entry_order_myparcel_text_home_address_only'] = $lang->get('entry_order_myparcel_text_home_address_only');
        $data['entry_order_myparcel_text_signature_on_delivery'] = $lang->get('entry_order_myparcel_text_signature_on_delivery');
        $data['entry_order_myparcel_text_return_if_no_answer'] = $lang->get('entry_order_myparcel_text_return_if_no_answer');
        $data['entry_order_myparcel_text_age_check'] = $lang->get('entry_order_myparcel_text_age_check');
        $data['entry_order_myparcel_text_insured_home'] = $lang->get('entry_order_myparcel_text_insured_home');
        $data['entry_order_myparcel_text_standar_insurance'] = $lang->get('entry_order_myparcel_text_standar_insurance');
        $data['entry_order_myparcel_text_insurance'] = $lang->get('entry_order_myparcel_text_insurance');
        $data['entry_order_myparcel_text_insurance_amount'] = $lang->get('entry_order_myparcel_text_insurance_amount');

        $data['button_save'] = $lang->get('button_save');
        $data['button_send_to_customer'] = $lang->get('button_send_to_customer');

        $data['package_types'] = array(
            1 => $lang->get('package_type_parcel'),
            2 => $lang->get('package_type_mailbox'),
            3 => $lang->get('package_type_unpaid_letter'),
            4 => $lang->get('package_type_digital_stamp'),

        );

        $data['insured_amounts'] = array(
            '49'  => $lang->get('entry_tab_2_select_insured_up_to_50'),
            '249' => $lang->get('entry_tab_2_select_insured_up_to_250'),
            '499' => $lang->get('entry_tab_2_select_insured_up_to_500'),
            ''    => $lang->get('entry_tab_2_select_insured_500'),
        );

        $data['entry_weight'] = $lang->get('entry_weight');
        $data['digital_stamp_weights'] = MyParcel()->helper->_getDigitalStampDefaultWeight();


        $token = $session->data['user_token'];

        if (!$return) {
            $url_myparcel_ship_to = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'shipToMyParcel'), array('user_token' => $token, 'order_ids' => $order_id));
        } else {
            $url_myparcel_ship_to = $url->link(MyParcel()->getMyparcelControllerPath('myparcelnl', 'shipment'), array('user_token' => $token, 'action' => 'send_return'));
        }

        ob_start();
        $this->render('view_ship_to_myparcel', array('data' => $data, 'order_id' => $order_id, 'url' => $url_myparcel_ship_to, 'number_of_copies' => $extra_options['number_of_copies'], 'export_settings' => $export_settings, 'recipient' => $recipient, 'is_pickup' => $is_pickup, 'return' => $return, 'screen' => $screen) );
        $html = ob_get_clean();

        return $html;
    }


    function myparcel_tracktrace($order_id)
    {
        /**
         * @var MyParcel_Helper $helper
         */
        $registry = MyParcel::$registry;

        // Load other packages
        $url = $registry->get('url');
        $session = $registry->get('session');
        $helper = MyParcel()->helper;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $track_trace_shipments = array();
        $consignments = $model_shipment->getSavedMyParcelShipments($order_id);

        if (!empty($consignments)) {
            $consignment = array_pop($consignments);
            $shipment_id = $consignment['shipment_id'];

            // Get shipment data via API
            $shipment_data = MyParcel()->shipment->shipment_helper->getShipmentData($shipment_id);

            $model_shipment->saveShipmentData($order_id, $shipment_data);

            // skip concepts, letters & mailbox packages
            if (empty($shipment_data['tracktrace'])) {
                unset($consignments[$shipment_id]);
                // continue;
            }

            $api = MyParcel()->api;
            $shipment_data['tracktrace_url'] = $api->getTracktraceUrl( $order_id, $shipment_data['tracktrace']);
            $track_trace_shipments[$shipment_id] = $shipment_data;
            if ( empty( $track_trace_shipments ) ) {
                return;
            }

            ob_start();
            $this->render('view_tracktrace_myparcel', array('track_trace_shipments' => $track_trace_shipments));
            $html = ob_get_clean();
            return $html;
        }
    }


    function myparcel_shipment_summary($order_id)
    {
        /**
         * @var MyParcel_Helper $helper
         */
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);
        // Load other packages
        $url = $registry->get('url');
        $session = $registry->get('session');
        $helper = MyParcel()->helper;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        $track_trace_shipments = array();
        $consignments = $model_shipment->getSavedMyParcelShipments($order_id);
        $export_settings = $model_shipment->getSavedExportSettings($order_id);

        $package_types = array(
            1 => $lang->get('package_type_parcel'),
            2 => $lang->get('package_type_mailbox'),
            3 => $lang->get('package_type_unpaid_letter'),
        );

        $option_strings = array(
            'large_format'      => $lang->get('entry_order_myparcel_text_extra_large_size'),
            'only_recipient'    => $lang->get('entry_order_myparcel_text_home_address_only'),
            'signature'         => $lang->get('entry_order_myparcel_text_signature_on_delivery'),
            'return'            => $lang->get('entry_order_myparcel_text_return_if_no_answer'),
        );

        $labels['entry_order_myparcel_custom_id'] = $lang->get('entry_order_myparcel_custom_id');
        $labels['entry_order_myparcel_insured_for'] = $lang->get('entry_order_myparcel_insured_for');
        $labels['entry_order_detail_myparcel_shipment'] = $lang->get('entry_order_detail_myparcel_shipment');
        $labels['entry_order_detail_myparcel_status'] = $lang->get('entry_order_detail_myparcel_status');
        $labels['entry_order_myparcel_shipment_type'] = $lang->get('entry_order_myparcel_shipment_type');
        if (!empty($consignments)) {
            foreach ($consignments as $key => $consignment) {
                $shipment_id = $consignment['shipment_id'];
            }
            $shipment_data = MyParcel()->shipment->shipment_helper->getShipmentData($shipment_id);

            // skip concepts, letters & mailbox packages
            if (empty($shipment_data['tracktrace'])) {
                unset($consignments[$shipment_id]);
                //continue;
            }

            $api = MyParcel()->api;
            $shipment_data['tracktrace_url'] = $api->getTracktraceUrl( $order_id, $shipment_data['tracktrace']);
            $track_trace_shipments[$shipment_id] = $shipment_data;
            if ( empty( $track_trace_shipments ) ) {
                return;
            }

            ob_start();
            $this->render('view_shipment_summary_myparcel', array('track_trace_shipments' => $track_trace_shipments, 'export_settings' => $export_settings, 'option_strings' => $option_strings, 'package_types' => $package_types, 'labels' => $labels));
            $html = ob_get_clean();
            return $html;
        }
    }



    function myparcel_packet_type($order_id)
    {
        /**
         * @var MyParcel_Helper $helper
         */
        $registry = MyParcel::$registry;

        // Load other packages
        $url = $registry->get('url');
        $session = $registry->get('session');
        $helper = MyParcel()->helper;

        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $loader->model(MyParcel()->getModelPath('shipping'));
        // Load models
        $model_shipping = $registry->get('model_myparcelnl_shipping');
        $model_order = $registry->get('model_sale_order');
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
        /*if not EU not show*/
        $order_data = $model_order->getOrder($order_id);
        $shipping_country_code = isset($order_data['shipping_iso_code_2']) ? $order_data['shipping_iso_code_2'] : '';

        /*map url*/
        $address_info = array(
            'address_1'     => isset($order_data['shipping_address_1'])?$order_data['shipping_address_1']:"",
            'address_2'     => isset($order_data['shipping_address_2'])?$order_data['shipping_address_2']:"",
            'city'          => isset($order_data['shipping_city'])?$order_data['shipping_city']:"",
            'state'         => "",
            'postcode'      => isset($order_data['shipping_postcode'])?$order_data['shipping_postcode']:"",
            'country'       => isset($order_data['shipping_country'])?$order_data['shipping_country']:""
        );
        $address_map_url = urlencode( implode( ', ', $address_info ) );
        /*tap.nguyen test address*/
        $address = "";
        $address .= isset($order_data['shipping_firstname'])?$order_data['shipping_firstname']." ": "";
        $address .= isset($order_data['shipping_lastname'])?$order_data['shipping_lastname']." ": "";
        $address .= isset($order_data['shipping_company'])?$order_data['shipping_company']." ": "";
        $address .= isset($order_data['shipping_address_1'])?$order_data['shipping_address_1']." ": "";
        $address .= isset($order_data['shipping_address_2'])?$order_data['shipping_address_2']." ": "";
        $address .= isset($order_data['shipping_city'])?$order_data['shipping_city']." ": "";
        $address .= isset($order_data['shipping_postcode'])?$order_data['shipping_postcode']." ": "";
        $address .= isset($order_data['shipping_country'])?$order_data['shipping_country']." ": "";
        $address .= "<br/>";

        $shipping_methods = isset($order_data['shipping_method'])? $order_data['shipping_method'] : "";
        if (!$helper->isEUCountry($shipping_country_code)) {
            $package_type = "";
        }else{
            $export_settings = $model_shipment->getSavedExportSettings($order_id);
            $mp_packet_type = isset($export_settings['package_type'])?$export_settings['package_type']:'';
            switch ($mp_packet_type) {
                case '2':
                    $package_type = $lang->get('package_type_mailbox');
                    break;
                case '3':
                    $package_type = $lang->get('package_type_unpaid_letter');
                    break;
                case '4':
                    $package_type = $lang->get('package_type_digital_stamp');
                    break;
                default:
                    $package_type = $lang->get('package_type_parcel');
                    break;
            }
        }

        // Get delivery options to display in order list column
        $delivery_data = MyParcel()->shipment->checkout->getDeliveryDataFromOrder($order_id);

        ob_start();
        $this->render('myparcel_packet_type', array('delivery_data' => $delivery_data, 'package_type' => $package_type, 'address' => $address,'shipping_methods' => $shipping_methods, 'address_map_url' => $address_map_url) );
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Display checkout delivery options
     * In Order Info
     * Use the function iframe_delivery_options() to render delivery options
     **/
    function myparcel_admin_checkout_delivery_options($order_id)
    {
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        // Load models
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $delivery_options = $model_shipment->getMyParcelDeliveryOptionsSignedRecipientOnly($order_id);

        $delivery_options['data'] = json_encode($delivery_options['data']);

        if (empty($delivery_options['data']) || $delivery_options['data'] === 'null') {
            return '';
        }

        $session = $registry->get('session');

        $session->data['myparcel'] = $delivery_options;

        $delivery_options_html = $this->iframe_delivery_options(true);

        ob_start();
        $this->render('view_admin_checkout_delivery_options', array('delivery_options_html' => $delivery_options_html));
        return ob_get_clean();
    }

    function myparcel_admin_checkout_delivery_details($order_id)
    {
        $ship_to_myparcel = $this->ship_to_myparcel($order_id);
        $mp_packet_type = $this->myparcel_packet_type($order_id);

        ob_start();
        $this->render('view_admin_checkout_delivery_details', array('ship_to_myparcel' => $ship_to_myparcel, 'mp_packet_type' => $mp_packet_type));
        return ob_get_clean();
    }

    function column_header_myparcel()
    {
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);
        return $lang->get('text_column_myparcel_header');

    }


    function myparcel_email_tracktrace($order_id)
    {
        if (!MyParcel()->settings->general->trackandtrace_email) {
            return;
        }
        $registry = MyParcel::$registry;
        $lang = $registry->get('language');
        //prevent overriding heading_title
        MyParcel()->loadMyparcelLang($lang);

        $emailTrackTrace = array();
        $api = MyParcel()->api;
        $tracktrace_links = $api->getTracktraceLinks ( $order_id );
        if ( !empty($tracktrace_links) ) {
            $emailTrackTrace['code'] = $tracktrace_links;
            $emailTrackTrace['text'] = $lang->get('email_track_trace');
        }

        return $emailTrackTrace;
    }


    function tracktrace_myaccount($order_id)
    {
        $actions = array();
        $api = MyParcel()->api;
        if ($consignments = $api->getTracktraceShipments ( $order_id )) {
            foreach ($consignments as $key => $consignment) {
                $actions['myparcel_tracktrace_'.$consignment['tracktrace']] = array(
                    'url'  => $consignment['tracktrace_url'],
                    'name' => 'ocmyparcel_myaccount_tracktrace_button',
                    'code' => $consignment['tracktrace']
                );
            }
        }

        ob_start();
        $this->render('view_tracktrace_myaccount_myparcel', array('actions' => $actions));
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Display Iframe Delivery Options
     * @return HTML content of the delivery options
     **/
    function iframe_delivery_options($order_info = false)
    {
        global $config;

        $delivery_enable = intval(MyParcel()->settings->checkout->enable_delivery);
        if (empty($delivery_enable)) {
            return '';
        }
        $registry = MyParcel::$registry;
        $session = $registry->get('session');
        $data = !empty($session->data['myparcel']) ? $session->data['myparcel'] : array();
        if (!empty($data)) {
            if (empty($data['info'])) {
                $data['info'] = json_decode(html_entity_decode($data['data']), true);
            }
        }

        $myparcel_country = '';

        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            $address_data = isset($session->data['shipping_address']) ? $session->data['shipping_address'] : (isset($session->data['payment_address']) ? $session->data['payment_address'] : null);
            if ($address_data) {
                $myparcel_country = $address_data['iso_code_2'];
            }
        } else {
            if (!empty($session->data['shipping_country_id'])) {
                $registry = MyParcel::$registry;
                $loader = $registry->get('load');
                $loader->model('localisation/country');
                $model_country = $registry->get('model_localisation_country');

                $country_id = $session->data['shipping_country_id'];
                $country_data = $model_country->getCountry($country_id);
                if (isset($country_data['iso_code_2'])) {
                    $myparcel_country = $country_data['iso_code_2'];
                }
            }
        }

        $belgium_enabled = intval(MyParcel()->settings->checkout->belgium_enabled);
        $country_allowed = ($myparcel_country == 'NL' || ($myparcel_country == 'BE' && $belgium_enabled)) ? true : false;

        // Find out if journal2 theme is active
        $config = empty($config) ? $registry->get('config') : $config;
        $data['theme'] = !empty($config->get('config_template')) ? $config->get('config_template') : $config->get('config_theme');
        if (!$order_info && !$country_allowed && !MyParcel()->helper->isModuleExist('d_quickcheckout', true)) {
            return '';
        }
//        echo "<pre>";
//        var_dump($session->data);die();
        $lang = MyParcel()->lang;
        ob_start();
        $this->render('view_iframe_delivery_options', array('data' => $data, 'lang' => $lang, 'myparcel_country' => $myparcel_country, 'is_order_info' => $order_info));
        return ob_get_clean();
    }

    /**
     * Display Base Iframe Delivery Options
     * @param array $data
     * @return HTML content of the delivery options
     **/
    function iframe_base_delivery_options($data)
    {
        $delivery_enable = intval(MyParcel()->settings->checkout->enable_delivery);
        if (empty($delivery_enable)) {
            return '';
        }
        $shipping_address = json_encode(MyParcel()->helper->getUpdateIframeAddressFromSession());
        ob_start();
        $this->render('view_iframe_base_delivery_options', array('shipping_address' => $shipping_address));
        return ob_get_clean();
    }

    /**
     * Declares js variables for Delivery Iframe
     * @return html of global js variables
     **/
    function iframe_delivery_checkout_header()
    {
        $delivery_enable = intval(MyParcel()->settings->checkout->enable_delivery);
        if (empty($delivery_enable)) {
            return '';
        }
        ob_start();
        $this->render('view_iframe_delivery_checkout_header', array());
        return ob_get_clean();
    }

    /**
     * Declares js variables for Admin Order header
     * @return html of global js variables
     **/
    function myparcel_order_header()
    {
        ob_start();
        $this->render('view_myparcel_order_header', array());
        return ob_get_clean();
    }
}

return new MyParcel_View();
