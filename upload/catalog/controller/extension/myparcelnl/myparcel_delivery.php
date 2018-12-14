<?php

class ControllerExtensionMyparcelnlMyparcelDelivery extends Controller
{
    function index()
    {
        $this->load->model('extension/myparcelnl/helper');
        $this->model_extension_myparcelnl_helper->getContent('iframe_base_delivery_options', array('data' => array()), true);
    }

    /**
     * Ajax function
     * @action get address data from provided address_id
     * @return array containing address components
     **/
    function address()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

        $address_id = !empty($_REQUEST['address_id']) ? $_REQUEST['address_id'] : null;

        if ($address_id) {
            $this->load->model('account/address');
            /** @var ModelAccountAddress $model_address * */
            $model_address = $this->model_account_address;
            $address_data = $model_address->getAddress($address_id);



            $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
            if ($use_addition_address_as_number_suffix == 2) {
                $address_data['street'] = isset($address_data['address_1']) ? $address_data['address_1'] : '';
                $address_data['number'] = isset($address_data['address_2']) ? $address_data['address_2'] : '';
            } else {
            $address_parts = MyParcel($this->registry)->helper->getAddressComponents($address_data['address_1']);
            $address_data['number'] = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
            $address_data['street'] = isset($address_parts['street']) ? $address_parts['street'] : '';
            }

            if (!empty($address_data)) {
                echo json_encode(
                    array(
                        'status'        => 'success',
                        'address_data'  => $address_data
                    )
                );
                die;
            } else {
                $error_message = 'Cannot separate address into parts';
            }
        } else {
            $error_message = 'No address id provided';
        }

        echo json_encode(
            array(
                'status'        => 'error',
                'error'         => $error_message
            )
        );
        die;
    }

    /**
     * Ajax function
     * @action get address data from session
     * @return array containing address components
     **/
    function address_session()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);
        $registry = MyParcel::$registry;
        $session = $registry->get('session');

        //TODO discriminate OC 2 and OC 1
        if (version_compare(VERSION, '2.0.0.0', '>=')) {
            $address_data = isset($session->data['shipping_address']) ? $session->data['shipping_address'] : (isset($session->data['payment_address']) ? $session->data['payment_address'] : null);
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

        if (!empty($address_data)) {

            $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
            if ($use_addition_address_as_number_suffix == 2) {
                $address_data['street'] = isset($address_data['address_1']) ? $address_data['address_1'] : '';
                $address_data['number'] = isset($address_data['address_2']) ? $address_data['address_2'] : '';
            } else {
            $address_parts = MyParcel($this->registry)->helper->getAddressComponents($address_data['address_1']);
            $address_data['number'] = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
            $address_data['street'] = isset($address_parts['street']) ? $address_parts['street'] : '';
            }

            // If Opencart 1x
            if (!version_compare(VERSION, '2.0.0.0', '>=')) {
                $address_data['iso_code_2'] = $country_code;
            }

            if (!empty($address_data)) {
                echo json_encode(
                    array(
                        'status'        => 'success',
                        'address_data'  => $address_data
                    )
                );
                die;
            } else {
                $error_message = 'Cannot separate address into parts';
            }
        } else {
            $error_message = 'No address id provided';
        }

        echo json_encode(
            array(
                'status'        => 'error',
                'error'         => $error_message
            )
        );
        die;
    }

    /**
     * Ajax function
     * @return array containing Street and House number
    **/
    function address_components()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

        $address_1 = !empty($_REQUEST['address_1']) ? $_REQUEST['address_1'] : null;

        if ($address_1) {
            $use_addition_address_as_number_suffix = MyParcel()->settings->general->use_addition_address_as_number_suffix;
            if ($use_addition_address_as_number_suffix == 2) {
                $address_data['street'] = $address_1;
                $address_data['number'] = isset($_REQUEST['address_2']) ? $_REQUEST['address_2'] : '';
                $address_data['number_addition'] = isset($_REQUEST['custom_field']['address']['address_3']) ? $_REQUEST['custom_field']['address']['address_3'] : '';
            } else {
            $address_parts = MyParcel($this->registry)->helper->getAddressComponents($address_1);
            $address_data['street'] = isset($address_parts['street']) ? $address_parts['street'] : '';
            $address_data['number'] = isset($address_parts['house_number']) ? $address_parts['house_number'] : '';
            $address_data['number_addition'] = isset($address_parts['number_addition']) ? $address_parts['number_addition'] : '';
            }

            if (!empty($address_data)) {
                echo json_encode(
                    array(
                        'status'        => 'success',
                        'address_data'  => $address_data
                    )
                );
                die;
            }
        }

        $address_data['street'] = '';
        $address_data['number'] = '';
        $address_data['number_addition'] = '';
        echo json_encode(
            array(
                'status'        => 'success',
                'address_data'  => $address_data
            )
        );
        die;
    }

    /**
     * Ajax function
     * @return boolean success or not
     **/
    function reset()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);
        $registry = MyParcel::$registry;
        $session = $registry->get('session');

        if (isset($session->data['myparcel'])) {
            unset($session->data['myparcel']);
        }

        echo json_encode(
            array(
                'status'        => 'success',
                'html'          => MyParcel()->view->iframe_delivery_options()
            )
        );
        die;
    }

    /**
     * Ajax function
     * @return HTML that will be appended into summary table
     **/
    function total_details()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);
        $registry = MyParcel::$registry;
        $session = $registry->get('session');

        /** @var MyParcel_Shipment_Checkout $checkout_helper **/
        $checkout_helper = MyParcel()->shipment->checkout;
        if (isset($_POST['delivery_options'])) {
            $data = $_POST['delivery_options'];
        } elseif (isset($session->data['myparcel'])) {
            $data = $session->data['myparcel'];
        } else {
            $data = array();
        }

        $order_id = isset($_POST['myparcel_order_id']) ? $_POST['myparcel_order_id'] : null;

        $total_array = $checkout_helper->getTotalArray($data, true, $order_id, 'incl ', false); // Get total with prices saved in myparcel_shipment

        ob_start();
        foreach ($total_array as $total_code => $total_item) {
?>
            <?php if (isset($_POST['aqc'])) { ?>
                <div class="row myparcel-total">
                    <label class="<?php echo $_POST['label_class'] ?>">
                        <?php echo $total_item['title'] ?>
                    </label>
                    <div class="<?php echo $_POST['price_class'] ?>"><?php echo $total_item['price'] ?></div>
                </div>
            <?php } else { ?>
                <?php if (version_compare(VERSION, '2.1.0.0', '>=')) { ?>
                    <tr class="myparcel-total">
                        <td colspan="4" class="text-right"><?php echo $total_item['title'] ?>:</td>
                        <td class="text-right"><?php echo $total_item['price'] ?></td>
                    </tr>
                <?php } else { ?>
                    <?php if (version_compare(VERSION, '2.0.3.1', '>=')) { ?>
                        <tr class="myparcel-total">
                            <td colspan="3"></td>
                            <td class="<?php echo (isset($_POST['admin']) ? 'right' : 'text-right') ?>"><?php echo $total_item['title'] ?>:</td>
                            <td class="<?php echo (isset($_POST['admin']) ? 'right' : 'text-right') ?>"><?php echo $total_item['price'] ?></td>
                        </tr>
                    <?php } else { ?>
                        <tr class="myparcel-total">
                            <td colspan="4" class="<?php echo (isset($_POST['admin']) ? 'right' : 'text-right') ?>"><?php echo $total_item['title'] ?>:</td>
                            <td class="<?php echo (isset($_POST['admin']) ? 'right' : 'text-right') ?>"><?php echo $total_item['price'] ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            <?php } ?>
<?php
        }

        $html = ob_get_clean();

        echo json_encode(
            array(
                'status'        => 'success',
                'html'          => $html
            )
        );
        die;
    }

    /**
     * Ajax function
     * @return array containing delivery options from API
     **/
    function delivery_options()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

        $params = $_REQUEST;

        // filter non API params
        $api_param_keys = array(
            'cc',
            'postal_code',
            'number',
            'carrier',
            'delivery_time',
            'delivery_date',
            'cutoff_time',
            'dropoff_days',
            'dropoff_delay',
            'deliverydays_window',
            'exclude_delivery_type',
        );
        foreach ($params as $key => $value) {
            if (!in_array($key, $api_param_keys)) {
                unset($params[$key]);
            }
        }

        /** @var MyParcel_Api $api **/
        $api = MyParcel($this->registry)->api;
        $response = $api->getDeliveryOptions( $params, true );

        @header('Content-type: application/json; charset=utf-8');

        echo $response['body'];
        die();
    }

    /**
     * Ajax function
     * @return html content of the iframe
     **/
    function iframe_content()
    {
        $this->load->model('extension/myparcelnl/helper');
        $this->model_extension_myparcelnl_helper->initMyParcel();
        $html = $this->model_extension_myparcelnl_helper->getContent('iframe_delivery_options');

        echo json_encode(
            array(
                'status' => 'success',
                'html'   => $html
            )
        );die;
    }

    /**
     * Ajax function
     * Save delivery options to session
     * @return boolean success or not
     **/
    function set_session()
    {
        if (!empty($_POST['mypa_data'])) {
            $this->session->data['myparcel']['data'] = $_POST['mypa_data'];
            $this->session->data['myparcel']['signed'] = $_POST['mypa_signed'];
            $this->session->data['myparcel']['recipient_only'] = $_POST['mypa_recipient_only'];

            echo json_encode(
                array(
                    'status' => 'success',
                    'session' => $this->session->data['myparcel']
                )
            );die;
        }

        echo json_encode(
            array(
                'status' => 'error'
            )
        );
        die;
    }
}