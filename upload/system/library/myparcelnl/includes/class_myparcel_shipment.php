<?php
require_once DIR_SYSTEM . 'library/myparcelnl/includes/class_myparcel_shipment_helper.php';
require_once DIR_SYSTEM . 'library/myparcelnl/includes/class_myparcel_shipment_checkout.php';

class MyParcel_Shipment
{
    public $shipment_helper;
    public $checkout;
    public $errors;

    function __construct()
    {
        $this->shipment_helper = new MyParcel_Shipment_Helper();
        $this->checkout = new MyParcel_Shipment_Checkout();
    }

    /**
     * Export shipments to MyParcel via API
     * @param array $params
     * @param boolean $process If need to process the shipment right after exporting
    **/
    function add($params, $process = false)
    {
        $order_ids = isset($params['order_ids']) ? $params['order_ids'] : null;
        $log = MyParcel()->log;

        if (!empty($order_ids)) {
            $log->add("*** -------------------------- ***");
            $log->add("*** Creating shipments started ***");

            /** @var MyParcel_Helper $helper **/
            /** @var MyParcel_Shipment_Helper $shipment_helper **/
            $helper = MyParcel()->helper;
            $shipment_helper = $this->shipment_helper;
            $order_ids = $helper->filterEUOrders($order_ids);

            // Declare model Shipment
            /** @var ModelMyparcelnlShipment $model_shipment **/
            $registry = MyParcel::$registry;
            $loader = $registry->get('load');
            $loader->model(MyParcel()->getModelPath('shipment'));
            $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

            /** @var ModelSaleOrder $model_order **/
            $model_order = $registry->get('model_sale_order');

            /** @var MyParcel_Api $api **/
            $api = MyParcel()->api;

            $shipment_data = array();

            foreach ($order_ids as $order_id) {
                $shipment_data_single = $shipment_helper->getOrderShipmentData($order_id);
                if (!empty($shipment_data_single)) {
                    $shipment_data[] = $shipment_data_single;
                }
            }

            // Call API to export an order shipment to MyParcel
            // And receive shipment_id from the API
            $responses = array();
            if (!empty($shipment_data)) {
                $saved_extra_settings = $model_shipment->getSavedExtraExportSettings($order_id);
                $number_of_copies = (!empty($saved_extra_settings['number_of_copies']) ? $saved_extra_settings['number_of_copies'] : 1);

                for ($i = 1; $i <= intval($number_of_copies); $i++) {
                    try {
                        MyParcel()->log->add("Prepared data:\n".var_export($shipment_data, true));
                        $response = $api->addShipments($shipment_data);
                        $responses[] = $response;
                    } catch (Exception $e) {
                        $this->errors[] = sprintf(MyParcel()->lang->get('entry_api_error_with_order_id'), $order_id) . $e->getMessage();
                    }
                    MyParcel()->log->add("API response (order {$order_id}):\n" . var_export($response, true));
                }
            }

            // Process the response form API
            // And save shipment data into database
            if (!empty($responses)) {
                foreach ($responses as $response) {

                    if (!empty($response['body']['data']['ids']) && $response['code'] == 200) {
                        $ids = $response['body']['data']['ids'];

                        foreach ($ids as $key => $shipment_id_data) {

                            $shipment_id = $shipment_id_data['id'];
                            $shipment = array(
                                'shipment_id' => $shipment_id,
                            );

                            $order_id = $order_ids[$key];

                            // Save shipment data in myparcel_order_shipment
                            if (!$model_shipment->saveShipmentData($order_id, $shipment)) {
                                $this->errors[] = sprintf(MyParcel()->lang->get('error_cannot_save_order_shipment'), $order_id, $shipment_id);
                            }

                            if (empty($this->errors)) {
                                // Process directly setting when export process succeeded
                                if (MyParcel()->settings->general->shipment_directly || $process === true) {
                                    $this->printPdf((array)$order_id, 'url');
                                    $log->add("*** -------------------- ***");
                                    $log->add("*** Get Shipment Started ***");
                                    // Get shipment data via API
                                    $shipment_data = $shipment_helper->getShipmentData($shipment_id);
                                    $model_shipment->saveShipmentData($order_id, $shipment_data);
                                }

                                // Auto change order status when export process succeeded
                                if (MyParcel()->settings->general->order_status_automation) {
                                    $loader->model('localisation/order_status');
                                    $model_order_status = $registry->get('model_localisation_order_status');
                                    $order_status_data = $model_order_status->getOrderStatus((int)MyParcel()->settings->general->automatic_order_status);
                                    if (!empty($order_status_data)) {
                                        if (version_compare(VERSION, '2.0.0.0', '>=')) {
                                            /** @var MyParcel_Api $api * */
                                            $api = MyParcel()->api;
                                            $response = $api->getLocalRequest('myparcelnl/myparcel_order/updateorderstatus', array('order_id' => $order_id));
                                            if (empty($response['body']['success'])) {
                                                $this->errors[] = MyParcel()->lang->get('entry_update_order_status_error') . ' - Order #' . $order_id;
                                                MyParcel()->log->add('Update status error - Order #' . $order_id);
                                            } else {
                                                $new_order_status_name = !empty($order_status_data['name']) ? $order_status_data['name'] : null;
                                            }
                                        } else {
                                            $data = array(
                                                'order_status_id' => MyParcel()->settings->general->automatic_order_status,
                                                'notify' => true,
                                                'comment' => MyParcel()->log->add('Update status error - Order #' . $order_id)
                                            );
                                            $loader->model('sale/order');
                                            $model_order = $registry->get('model_sale_order');
                                            ob_start();
                                            $model_order->addOrderHistory($order_id, $data);
                                            ob_clean();
                                            $new_order_status_name = !empty($order_status_data['name']) ? $order_status_data['name'] : null;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $this->errors[] = 'Body data returned from API is empty';
                    }
                }
            } else {
                $this->errors[] = 'Empty response from API';
            }
        }
        else {
            $this->errors[] = 'Order ids cannot be empty';
        }

        // Return json data for the ajax request
        if (empty($this->errors)) {

            /**
             * In the first time an order is exported
             * if this order is exported with the default export settings (No saved shipment options stored)
             * then save the default export settings as saved shipment options
             **/
            foreach ($order_ids as $order_id) {
                if (empty($saved_export_settings)) {
                    $loader->model('sale/order');
                    $model_order = $registry->get('model_sale_order');
                    $order_data =  $model_order->getOrder($order_id);
                    $shipment_options = $shipment_helper->getOptions($order_data, true);
                    $form_data = array('myparcel_options' => array($order_id => $shipment_options), 'order_id' => $order_id);
                    $shipment_helper->saveOptions($form_data, false);
                }
            }


            $order_id = current($order_ids);
            $loader->model(MyParcel()->getModelPath('helper'));
            /** @var ModelMyparcelnlHelper $model_helper **/
            $model_helper = $registry->get('model_extension_myparcelnl_helper');
            $action = 'refresh';

            if (count($order_ids) > 1) {
                $items = array();


                foreach ($order_ids as $order_id) {
                    $items['items'][] = array(
                        'order_id' => $order_id,
                        'element_id' => 'column-myparcel-order-' . $order_id,
                        'order_status_element_id' => 'column-status-order-' . $order_id,
                        'html' => $model_helper->getContent('column_myparcel', array('order_id' => $order_id)),
                    );
                }
            } else {
                if (isset($_GET['route']) && $_GET['route'] == 'sale/order/info') {
                    $action = 'reload';
                }
                $items = array(
                    'order_id'                  => $order_id,
                    'element_id'                => 'column-myparcel-order-' . $order_id,
                    'order_status_element_id'   => 'column-status-order-' . $order_id,
                    'html'                      => $model_helper->getContent('column_myparcel', array('order_id' => $order_id)),
                );
            }

            echo json_encode(
                array(
                    'status'                    => 'success',
                    'action'                    => $action,
                    'new_order_status'          => isset($new_order_status_name) ? $new_order_status_name : '',
                    'current_screen'            => !empty($params['screen']) ? $params['screen'] : '',
                    'multiple_returned'         => count($order_ids) > 1 ? true : false
                ) + $items
            );

            die;
        }

        echo json_encode(
            array(
                'status' => 'error',
                'errors' => $this->errors
            )
        );
        die;
    }

    /**
     * Download pdf file containing  shipment information
     * @param array $order_ids
     * @param string $label_response_type
     * @return response from api
     **/
    function printPdf($order_ids, $label_response_type = NULL, $position = null)
    {
        if (empty($order_ids)) {
           $this->errors[] = 'No order specified for printing PDF';
        }

        /** @var MyParcel_Api $api **/
        $api = MyParcel()->api;
        /** @var MyParcel_Shipment_Helper $shipment_helper **/
        $shipment_helper = $this->shipment_helper;
        $return = array();
        /** @var MyParcel_Helper $helper **/
        $helper = MyParcel()->helper;

        $shipment_ids = $shipment_helper->getShipmentIdsByOrderIds( $order_ids, array( 'only_last' => true ) );

        if ( empty($shipment_ids) ) {
            MyParcel()->log->add("*** Failed label request (not exported yet) ***");
            $this->errors[] = MyParcel()->log->add( MyParcel()->lang->get('error_selected_orders_not_exported_yet' ));
            $a = MyParcel()->lang->get('error_selected_orders_not_exported_yet' );
            echo $a;
            return $a;
        }

        MyParcel()->log->add("*** Label request started ***");
        MyParcel()->log->add("Shipment ID's: ".implode(', ', $shipment_ids));
        $params = array();
        if($position != null){
            $params['positions'] = $position;
        }
        try {
            if ($label_response_type == 'url') {
                $response = $api->getShipmentLabels( $shipment_ids, array(), 'link' );
                MyParcel()->log->add("API response:\n".var_export($response, true));

                if (isset($response['body']['data']['pdfs']['url'])) {
                    $url = $helper->untrailingslashit( $api->api_domain ) . $response['body']['data']['pdfs']['url'];
                    $return['url'] = $url;
                } else {
                    MyParcel()->log->add(MyParcel()->lang->get('error_unknown'));
                }
            } else {
                $response = $api->getShipmentLabels( $shipment_ids, $params, 'pdf' );

                if (isset($response['body'])) {
                    MyParcel()->log->add(MyParcel()->lang->get("log_pdf_data_received"));
                    $pdf_data = $response['body'];
                    $setting_download_display = MyParcel()->settings->general->pdf;
                    $output_mode = !empty($setting_download_display) ? 'download' : 'display';

                    if ( $output_mode == 'display' ) {
                        $shipment_helper->streamPdf( $pdf_data, $order_ids );
                    } else {
                        $shipment_helper->downloadPdf( $pdf_data, $order_ids );
                    }
                } else {
                    MyParcel()->log->add(MyParcel()->lang->get("error_unknown_response") . "\n".var_export($response, true));
                }
            }
        } catch (Exception $e) {
            MyParcel()->log->add(MyParcel()->lang->get('error_unknown'));
            MyParcel()->log->add($e->getMessage());
        }

        return $return;
    }

    /**
     * Retrieve the options for return form
     **/
    function addReturn($params)
    {
        $order_ids = isset($params['order_ids']) ? $params['order_ids'] : null;
        $screen = isset($params['screen']) ? $params['screen'] : 'order_overview';
        if (!empty($order_ids)) {
            /** @var MyParcel_View $class_view **/
            $class_view = MyParcel()->view;
            $html = $class_view->return_shipment_form($order_ids, $screen);

            echo json_encode(
                array(
                    'status' => 'success',
                    'html' => $html,
                    'order_ids' => (array)$order_ids
                )
            );
            die;
        } else {
            $this->errors[] = 'Order ids cannot be empty';
        }

        echo json_encode(
            array(
                'status' => 'error',
                'html' => MyParcel()->helper->renderErrors($this->errors),
                'order_ids' => (array)$order_ids
            )
        );
        die;
    }

    /**
     * Process the return options form
     * At the moment, support return for one order at a time
     * @param Array $params
     * @return json
     **/
    function sendReturn($params)
    {
        MyParcel()->log->add("*** Creating return shipments started ***");

        // If OC version is 1.0 then decode html before parse_str
        // It is due to the way old jQuery serialize the form data
        if(!version_compare(VERSION, '2.0.0.0', '>=')) {
            $form_data = html_entity_decode($_POST['data']);
        } else {
            $form_data = $_POST['data'];
        }

        parse_str($form_data, $data);

        $registry = MyParcel::$registry;
        $loader = $registry->get('load');

        if (!empty($data['myparcel_options']) && empty($this->errors)) {
            /** @var MyParcel_Shipment_Helper $shipment_helper **/
            $shipment_helper = $this->shipment_helper;

            $myparcel_options = $data['myparcel_options'];
            $order_ids = array();

            foreach ($myparcel_options as $order_id => $options) {

                list($prepare_result, $return_shipments) = $shipment_helper->prepareReturnShipmentData($order_id, $options);
                if (!$prepare_result) {
                    $this->errors = $return_shipments;
                    break;
                }
                $return_shipments = array($return_shipments);

                MyParcel()->log->add("Return shipment data for order {$order_id}:\n" . var_export($return_shipments, true));
                $order_ids[] = $order_id;

                try {
                    /** @var MyParcel_Api $api **/
                    $api = MyParcel()->api;
                    $response = $api->addShipments($return_shipments, 'return');
                    MyParcel()->log->add("API response (order {$order_id}):\n" . var_export($response, true));

                    if (isset($response['body']['data']['ids'])) {
                        $ids = array_shift($response['body']['data']['ids']);
                        $shipment_id = $ids['id'];

                        $shipment_data = array(
                            'shipment_id' => $shipment_id,
                        );

                        // Save shipment data into database
                        /** @var ModelMyparcelnlShipment $model_shipment **/
                        $loader->model(MyParcel()->getModelPath('shipment'));
                        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');
                        $model_shipment->saveShipmentData($order_id, $shipment_data);

                    } else {
                        $this->errors[] = sprintf(MyParcel()->lang->get('error_create_related_return_shipment_for_order'), $order_id);
                    }
                } catch (Exception $e) {
                    $this->errors[] = sprintf(MyParcel()->lang->get('entry_api_error_with_order_id'), $order_id) . $e->getMessage();
                }
            }
        } else {
            $this->errors[] = MyParcel()->lang->get('error_return_options_is_empty');
        }

        // Return json data for the ajax request
        if (empty($this->errors)) {

            $loader->model(MyParcel()->getModelPath('helper'));
            /** @var ModelMyparcelnlHelper $model_helper **/
            $model_helper = $registry->get('model_extension_myparcelnl_helper');

            echo json_encode(
                array(
                    'status'                    => 'success',
                    'order_ids'                 => (array)$order_id,
                    'element_id'                => 'column-myparcel-order-' . $order_id,
                    'order_status_element_id'   => 'column-status-order-' . $order_id,
                    'html'                      => $model_helper->getContent('column_myparcel', array('order_id' => $order_id)),
                    'current_screen'            => !empty($params['screen']) ? $params['screen'] : ''
                )
            );
            die;
        }

        /** @var MyParcel_Helper $helper **/
        $helper = MyParcel()->helper;
        $order_ids = !empty($order_ids) ? $order_ids : array();

        echo json_encode(
            array(
                'status'    => 'error',
                'html'      => $helper->renderErrors($this->errors),
                'order_ids' => (array)$order_ids
            )
        );
        die;
    }
}

return new MyParcel_Shipment();