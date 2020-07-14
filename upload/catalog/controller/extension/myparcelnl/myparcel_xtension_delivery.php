<?php

class ControllerExtensionMyparcelnlMyparcelXtensionDelivery extends Controller
{
    public $data = array();
    public function __construct($registry)
    {
        parent::__construct($registry);
        if (!class_exists('MyParcel')) {
            require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
            MyParcel($this->registry);
            $this->load->language('extension/module/myparcelnl.php');
        }
    }

    function index()
    {
        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $myparcelnl_checkout_settings = $config->get('module_myparcelnl_fields_checkout');

        $shipping_method_code = '';
        $shipping_address = [];
        if (isset($this->session->data['shipping_address'])) {
            $shipping_address = $this->session->data['shipping_address'];
        }
        if(isset($this->request->post['shipping_method'])){
            $shipping_method = $this->request->post['shipping_method'];
            $shipping_method = explode('.',$shipping_method);
            if(isset($shipping_method[1])){
                $shipping_method_code = explode('_',$shipping_method[1])[1];
            }
            if(!isset($shipping_method_code) && $shipping_method_code == null){
                $json['error']['warning'] = $this->language->get('error_shipping');
            }
        }
        if(isset($this->session->data['myparcel_delivery'])){
            $myparcel_delivery = $this->session->data['myparcel_delivery'];
        }
        else{
            if (!empty($shipping_address) && ($shipping_address['iso_code_2'] == 'NL' || $shipping_address['iso_code_2'] == 'BE')) {
                $api = MyParcel()->api;
                $current_currency_code = $this->session->data['currency'];
                $delivery_params = MyParcel()->helper->getDeliveryParams($shipping_address);
                $myparcel_delivery = $api->getDeliveryOptions($delivery_params);

                if(isset($myparcel_delivery['code']) && $myparcel_delivery['code'] == 200){
                    $myparcel_delivery = MyParcel()->helper->formatDeliveryPrice($myparcel_delivery, $shipping_address ,$this->cart->getSubTotal(),$this->language,$current_currency_code);

                    /** @var \Cart\Currency $currency **/
                    $currency = $registry->get('currency');

                    $this->session->data['myparcel_delivery'] = $myparcel_delivery['body']['data'];
                    $myparcel_delivery = $myparcel_delivery['body']['data'];
                }
            }
        }
        if(isset($myparcel_delivery[$shipping_method_code])){
            $this->data['myparcel_delivery_option'] = $myparcel_delivery[$shipping_method_code];
            $this->data['shipping_method_code'] = $shipping_method_code;


            if($shipping_method_code == 'delivery'){
                $this->data['min_date'] = date('Y-m-d',strtotime($this->data['myparcel_delivery_option'][0]['date']));
                $this->data['max_date'] = date('Y-m-d',strtotime($this->data['myparcel_delivery_option'][count($this->data['myparcel_delivery_option']) - 1]['date']));
                $this->data['entry_delivery_date'] = $this->language->get('entry_select') . ' ' . $this->language->get('entry_delivery_date');
                $this->data['date_format'] = 'DD-MM-YYYY';
                $this->data['date_pick']  = (isset($this->session->data['delivery_date']) && date('Y-m-d',strtotime($this->data['min_date'])) < date('Y-m-d',strtotime($this->session->data['delivery_date']))) ? date('Y-m-d',strtotime($this->session->data['delivery_date'])) : date('Y-m-d',strtotime($this->data['min_date']));
                $this->data['delivery_time_start'] = isset($this->session->data['delivery_time_start']) ? $this->session->data['delivery_time_start'] : '';
                $this->data['delivery_time_end'] = isset($this->session->data['delivery_time_end']) ? $this->session->data['delivery_time_end'] : '';
                $this->data['dropoff_days']= implode(',' , MyParcel()->helper->getDisableDropoffDays($myparcel_delivery));

                if(isset($this->session->data['myparcel_price_delivery']['myparcel_shipping_choosed'])){
                    $this->session->data['myparcel_shipping_choosed'] = $this->session->data['myparcel_price_delivery']['myparcel_shipping_choosed'];
                }
                //select default option
                if(empty($this->data['delivery_time_start']) && empty($this->data['delivery_time_end'])){
                    foreach ($this->data['myparcel_delivery_option'] as $value){
                        if($value['date'] == $this->data['date_pick']){
                            foreach ($value['time'] as $time){
                                if($time['price_comment'] == 'standard'){
                                    $this->saveMyparcelPriceDelivery($value, $shipping_method_code, $time);
                                    break;
                                }
                            }
                        }
                    }
                }

            }
            else{
                if(isset($this->session->data['pickup_location'])){
                    foreach ($this->data['myparcel_delivery_option'] as $value){
                        if($value['location'] == $this->session->data['pickup_location']){
                            $this->data['pickup_detail'] = $value;
                            break;
                        }
                    }
                }
                if(!isset($this->data['pickup_detail'])){
                    $this->data['pickup_detail'] = $this->data['myparcel_delivery_option'][0];
                }

                $this->data['entry_date'] = $this->language->get('entry_date');
                $this->data['entry_pickup_from'] = $this->language->get('entry_pickup_from');
                $this->data['pickup_time_start'] = isset($this->session->data['pickup_time_start']) ? $this->session->data['pickup_time_start'] : '';

                if(empty($this->data['pickup_time_start'])){
                    $this->saveMyparcelPricePickup($this->data['pickup_detail'],$shipping_method_code, $this->data['pickup_detail']['time'][0]);
                }

                if(isset($this->session->data['myparcel_price_pickup']['myparcel_shipping_choosed'])){
                    $this->session->data['myparcel_shipping_choosed'] = $this->session->data['myparcel_price_pickup']['myparcel_shipping_choosed'];
                }
            }
        }

        if(isset($shipping_address['iso_code_2']) && $shipping_address['iso_code_2'] == 'NL'){
            if(!isset($currency)){
                $registry = MyParcel::$registry;
                /** @var \Cart\Currency $currency **/
                $currency = $registry->get('currency');
            }
            $current_currency_code = $this->session->data['currency'];
            $shipment_class = MyParcel()->shipment;
            /** @var MyParcel_Shipment_Checkout $checkout_helper **/
            $checkout_helper = $shipment_class->checkout;
            $additional_service = $checkout_helper->getDeliveryPrices(false, true, '', true, 0,$this->cart->getSubTotal());
            $arr_additional_service = [];
            $array_additional_key = ['signed','only_recipient'];
            $default_price_0_text = isset($myparcelnl_checkout_settings['default_price_0_text']) ? $myparcelnl_checkout_settings['default_price_0_text'] : '';

            foreach ($additional_service[$shipping_address['iso_code_2']] as $key => $value){
                if(in_array($key,$array_additional_key) && $value !== 'disabled'){
                    $key_title = 'entry_'. $key;
                    $arr_additional_service[$key]['amount'] = round($currency->convert($value,'EUR',$current_currency_code),2);
                    $arr_additional_service[$key]['text_amount'] = ($arr_additional_service[$key]['amount'] == 0 && $default_price_0_text != '') ? $default_price_0_text : $currency->format($arr_additional_service[$key]['amount'],$current_currency_code);
                    $arr_additional_service[$key]['title'] = $this->language->get($key_title);
                    $this->data['myparcel_additional_checked'][$key] = isset($this->session->data['additional_service_checked'][$shipping_method_code][$key]) ?$this->session->data['additional_service_checked'][$shipping_method_code][$key] : false;

                }
            }
            $this->data['additional_title'] = $this->language->get('entry_additional_service');
            if(count($arr_additional_service) > 0){
                $this->data['additional_service'] = $arr_additional_service;
                $this->session->data['additional_service'] = $arr_additional_service;
            }

        }

        $this->data['action'] = MyParcel()->getMyparcelXtensionControllerPath();
        $this->response->setOutput($this->load->view(MyParcel()->getMyparcelXtensionViewPath() . 'myparcel_xtension_delivery', $this->data));

    }


    function change(){
        $shipping_methods = [];
        $myparcel_delivery = [];
        $shipping_method_code = '';
        $json['success'] = false;

        $registry = MyParcel::$registry;
        $config = $registry->get('config');
        $myparcelnl_checkout_settings = $config->get('module_myparcelnl_fields_checkout');
        if(isset($this->session->data['myparcel_delivery'])){
            $myparcel_delivery = $this->session->data['myparcel_delivery'];
        }

        if(isset($this->request->post['myparcel_pickup_location'])){
            $myparcel_pickup_location = $this->request->post['myparcel_pickup_location'];
            $shipping_method_code = 'pickup';
            foreach ($myparcel_delivery[$shipping_method_code] as $value){
                if($value['location'] == $myparcel_pickup_location){
                    $this->session->data['pickup_location'] = $value['location'];
//                    $this->session->data['pickup_time_start'] = $value['time'][0]['start'];
                    $this->data['pickup_detail'] = $value;
                    $this->saveMyparcelPricePickup($value, $shipping_method_code, $value['time'][0]);
//                    $this->data['pickup_time_start'] = '';
//                    if(isset($this->session->data['pickup_time_start'])){
//                        unset($this->session->data['pickup_time_start']);
//                    }
//                    if(isset($this->session->data['myparcel_price_pickup'])){
//                        unset($this->session->data['myparcel_price_pickup']);
//                    }
                    break;
                }
            }
            $this->data['entry_date'] = $this->language->get('entry_date');
            $this->data['entry_pickup_from'] = $this->language->get('entry_pickup_from');
        }
        elseif(isset($this->request->post['delivery_date'])){
            $shipping_method_code = 'delivery';
            $delivery_date = date('Y-m-d',strtotime($this->request->post['delivery_date']));
            foreach ($myparcel_delivery[$shipping_method_code] as $value){
                if(date('m-d-Y',strtotime($value['date'])) == date('m-d-Y',strtotime($delivery_date)) ) {
//                    $this->data['delivery_time_start'] = '';
//                    $this->data['delivery_time_end'] = '';
                    $this->data['delivery_detail'] = $value;
//
//                    $this->session->data['delivery_time_start'] = '';
//                    $this->session->data['delivery_time_end'] = '';
                    $this->session->data['delivery_date'] = $delivery_date;
//                    if(isset($this->session->data['myparcel_price_delivery'])){
//                        unset($this->session->data['myparcel_price_delivery']);
//                    }

                    foreach ($value['time'] as $time){
                        if($time['price_comment'] == 'standard'){
                            $this->saveMyparcelPriceDelivery($value, $shipping_method_code, $time);
                            break;
                        }
                    }
                    break;
                }
            }
            $shipping_address = [];
            if (isset($this->session->data['shipping_address'])) {
                $shipping_address = $this->session->data['shipping_address'];
            }
            if(isset($shipping_address['iso_code_2']) && $shipping_address['iso_code_2'] == 'NL' && $shipping_method_code == 'delivery'){
                if(!isset($currency)){
                    $registry = MyParcel::$registry;
                    /** @var \Cart\Currency $currency **/
                    $currency = $registry->get('currency');
                }
                $current_currency_code = $this->session->data['currency'];
                $shipment_class = MyParcel()->shipment;
                /** @var MyParcel_Shipment_Checkout $checkout_helper **/
                $checkout_helper = $shipment_class->checkout;
                $additional_service = $checkout_helper->getDeliveryPrices(false, true, '', true, 0,$this->cart->getSubTotal());
                $arr_additional_service = [];
                $array_additional_key = ['signed','only_recipient'];
                $default_price_0_text = isset($myparcelnl_checkout_settings['default_price_0_text']) ? $myparcelnl_checkout_settings['default_price_0_text'] : '';

                foreach ($additional_service[$shipping_address['iso_code_2']] as $key => $value){
                    if(in_array($key,$array_additional_key) && $value !== 'disabled'){
                        $key_title = 'entry_'. $key;
                        $arr_additional_service[$key]['amount'] = round($currency->convert($value,'EUR',$current_currency_code),2);
                        $arr_additional_service[$key]['text_amount'] = ($arr_additional_service[$key]['amount'] == 0 && $default_price_0_text != '') ? $default_price_0_text : $currency->format($arr_additional_service[$key]['amount'],$current_currency_code);
                        $arr_additional_service[$key]['title'] = $this->language->get($key_title);
                        $this->data['myparcel_additional_checked'][$key] = isset($this->session->data['additional_service_checked'][$shipping_method_code][$key]) ?$this->session->data['additional_service_checked'][$shipping_method_code][$key] : false;
                    }
                }
                $this->data['additional_title'] = $this->language->get('entry_additional_service');
                $this->data['additional_service'] = $arr_additional_service;
                $this->session->data['additional_service'] = $arr_additional_service;

            }
        }
        $this->data['action'] = MyParcel()->getMyparcelXtensionControllerPath();
        $this->data['shipping_method_code'] = $shipping_method_code;
        if(isset($this->session->data['myparcel_shipping_choosed'])){
            unset($this->session->data['myparcel_shipping_choosed']);
        }
        if($shipping_method_code != ''){
            $json['html'] = $this->load->view(MyParcel()->getMyparcelXtensionViewPath() . 'myparcel_xtension_'. $shipping_method_code .'_change', $this->data);
            $json['success'] = true;
        }
        echo json_encode($json);
        die();
    }

    function save(){
        $shipping_methods = [];
        $myparcel_delivery = [];
        $json['success'] = false;
        if(isset($this->session->data['shipping_methods'])){
            $shipping_methods = $this->session->data['shipping_methods'];
        }
        if(isset($this->session->data['myparcel_delivery'])){
            $myparcel_delivery = $this->session->data['myparcel_delivery'];
        }
        if(isset($this->request->post['myparcel_option'])){
            $myparcel_option = $this->request->post['myparcel_option'];
            $shipping_method_code = explode('-',$myparcel_option)[0];
            $key_shipping =  "myparcel_". $shipping_method_code;
            if($shipping_method_code == 'delivery'){
                $this->session->data['delivery_date'] = date('Y-m-d',strtotime($this->request->post['delivery_date']));
                $delivery_option_time = explode('-',str_replace($shipping_method_code.'-','',$myparcel_option));
                $this->session->data['delivery_time_start'] = $delivery_option_time[0];
                $this->session->data['delivery_time_end'] = $delivery_option_time[1];

                //update shipping_methods
                foreach ($myparcel_delivery[$shipping_method_code] as $key => $value){
                    if(date('d-m-Y',strtotime($value['date'])) == date('d-m-Y',strtotime($this->session->data['delivery_date'])) ) {
                        foreach ($value['time'] as $k => $time){
                            if($time['start'] == $this->session->data['delivery_time_start'] && $time['end'] == $this->session->data['delivery_time_end']){
                                $shipping_methods[$key_shipping]['quote'][$key_shipping]['cost'] = $time['price']['current_currency_amount'];
                                $shipping_methods[$key_shipping]['quote'][$key_shipping]['text'] = $time['price']['current_currency_amount'];
                                $this->session->data['shipping_methods'] = $shipping_methods;

//                                $this->session->data['myparcel_shipping_choosed']['date'] = date('Y-m-d',strtotime($value['date']));
//                                $this->session->data['myparcel_shipping_choosed']['time'][] = $time;
//                                $this->session->data['myparcel_shipping_choosed']['code'] = 'myparcel_'. $shipping_method_code;
//                                $this->session->data['myparcel_shipping_choosed']['additional_service'] = (isset($this->session->data['additional_service_checked']['delivery'])) ? $this->session->data['additional_service_checked']['delivery'] : [];
//                                $time['price']['shipping_method_code'] = $shipping_method_code;
//                                $time['price']['text_myparcel_price_delivery'] = date('d-m-Y',strtotime($value['date'])) . ' - ' . date('H:i',strtotime($time['start'])) . ' - ' .date('H:i',strtotime($time['end']));
//                                $this->session->data['myparcel_price_delivery'] = $time['price'];
//                                $this->session->data['myparcel_price_delivery']['myparcel_shipping_choosed'] = $this->session->data['myparcel_shipping_choosed'];
                                $this->saveMyparcelPriceDelivery($value, $shipping_method_code, $time);
                                break;
                            }
                        }
                        break;
                    }
                }
                $json['success'] = true;

            }
            else if($shipping_method_code == 'pickup'){
                $pickup_option_time = explode('-',str_replace($shipping_method_code.'-','',$myparcel_option));
                //update shipping_methods
                foreach ($myparcel_delivery[$shipping_method_code] as $key => $value){
                    if($value['location'] == $pickup_option_time[0] ) {
                        foreach ($value['time'] as $k => $time){
                            if($time['start'] == $pickup_option_time[1]){
                                $shipping_methods[$key_shipping]['quote'][$key_shipping]['cost'] = $time['price']['current_currency_amount'];
                                $shipping_methods[$key_shipping]['quote'][$key_shipping]['text'] = $time['price']['current_currency_amount'];
                                $this->session->data['shipping_methods'] = $shipping_methods;
//                                $time['price']['shipping_method_code'] = $shipping_method_code;
//                                $time['price']['text_myparcel_price_pickup'] =  $value['location'] . ' - ' .date('d-m-Y',strtotime($value['date'])) . ' - ' . date('H:i',strtotime($time['start']));
//                                $this->session->data['myparcel_price_pickup'] = $time['price'];
//                                $value['code'] = 'myparcel_'. $shipping_method_code;
//                                $value['additional_service'] = [];
//                                $value['time'] = [];
//                                $value['time'][] = $time;
//                                $this->session->data['myparcel_shipping_choosed'] = $value;
//                                $this->session->data['myparcel_price_pickup']['myparcel_shipping_choosed'] = $this->session->data['myparcel_shipping_choosed'];
//                                $this->session->data['pickup_time_start'] = $time['start'];
                                $this->saveMyparcelPricePickup($value,$shipping_method_code,$time);
                                break;
                            }
                        }
                        break;
                    }
                }
                $json['success'] = true;
            }
        }

        if(isset($this->request->post['addition_name']) && isset($_POST['addition_value'])){
            $addition_name = $this->request->post['addition_name'];
            $addition_value = $_POST['addition_value'];
            $this->session->data['additional_service_checked']['delivery'][$addition_name] = ($addition_value == 'false') ? false : true;
            if(isset($this->session->data['myparcel_shipping_choosed'])){
                $this->session->data['myparcel_shipping_choosed']['additional_service'] = $this->session->data['additional_service_checked']['delivery'];
            }
            $json['success'] = true;
        }

        echo json_encode($json);
        die();
    }

    function externalValidate(){
        $myparcel_shipping_key = explode('.',$this->session->data['shipping_method']['code'])[0];
        $myparcel_shipping_key = explode('_',$myparcel_shipping_key)[1];

        if(isset($this->session->data['myparcel_price_' . $myparcel_shipping_key]['myparcel_shipping_choosed'])){
            $this->session->data['myparcel_shipping_choosed'] = $this->session->data['myparcel_price_' . $myparcel_shipping_key]['myparcel_shipping_choosed'];
        }

        if(!isset($this->session->data['myparcel_shipping_choosed'])){
            $json['warning'] = $this->language->get('error_external_validate');
            $json['action'] = 'index.php?route=' .MyParcel()->getMyparcelXtensionControllerPath();
            $entry_title = 'entry_select_' . explode('.',$this->session->data['shipping_method']['code'])[0];
            $json['title'] =$this->language->get($entry_title);
            return $json;
        }
        return false;
    }
    function forgetMyParcelChoosed(){
        if(isset($this->session->data['myparcel_shipping_choosed'])){
            unset($this->session->data['myparcel_shipping_choosed']);
        }
        if(isset($this->session->data['additional_service'])){
            unset($this->session->data['additional_service']);
        }
        if(isset($this->session->data['pickup_time_start'])){
            unset($this->session->data['pickup_time_start']);
        }
        if(isset($this->session->data['additional_service_checked'])){
            unset($this->session->data['additional_service_checked']);
        }
        if(isset($this->session->data['pickup_time_start'])){
            unset($this->session->data['pickup_time_start']);
        }
        if(isset($this->session->data['myparcel_price_pickup'])){
            unset($this->session->data['myparcel_price_pickup']);
        }
        if(isset($this->session->data['myparcel_price_delivery'])){
            unset($this->session->data['myparcel_price_delivery']);
        }
        if(isset($this->session->data['delivery_time_start'])){
            unset($this->session->data['delivery_time_start']);
        }
        if(isset($this->session->data['delivery_time_end'])){
            unset($this->session->data['delivery_time_end']);
        }
        if(isset($this->session->data['delivery_date'])){
            unset($this->session->data['delivery_date']);
        }
        if(isset($this->session->data['pickup_location'])){
            unset($this->session->data['pickup_location']);
        }
        if(isset($this->session->data['myparcel_option_title'])){
            unset($this->session->data['myparcel_option_title']);
        }

        echo json_encode(array('status' => true));
        die();
    }

    function saveMyparcelPriceDelivery($delivery_option , $shipping_method_code, $time){

        $this->data['delivery_time_start'] = $time['start'];
        $this->data['delivery_time_end'] = $time['end'];
        if(isset($this->session->data['myparcel_shipping_choosed'])){
            unset($this->session->data['myparcel_shipping_choosed']);
        }
        $this->session->data['myparcel_shipping_choosed']['date'] = date('Y-m-d',strtotime($delivery_option['date']));
        $this->session->data['myparcel_shipping_choosed']['time'][] = $time;
        $this->session->data['myparcel_shipping_choosed']['code'] = 'myparcel_'. $shipping_method_code;
        $this->session->data['myparcel_shipping_choosed']['additional_service'] = (isset($this->session->data['additional_service_checked']['delivery'])) ? $this->session->data['additional_service_checked']['delivery'] : [];
        $time['price']['shipping_method_code'] = $shipping_method_code;
        $time['price']['text_myparcel_price_delivery'] = date('d-m-Y',strtotime($delivery_option['date'])) . ' - ' . date('H:i',strtotime($time['start'])) . ' - ' .date('H:i',strtotime($time['end']));
        $this->session->data['myparcel_price_delivery'] = $time['price'];
        $this->session->data['myparcel_price_delivery']['myparcel_shipping_choosed'] = $this->session->data['myparcel_shipping_choosed'];
    }

    function saveMyparcelPricePickup($pickup_detail , $shipping_method_code, $time){
        //select default option
        $time['price']['shipping_method_code'] = $shipping_method_code;
        $time['price']['text_myparcel_price_pickup'] =  $pickup_detail['location'] . ' - ' .date('d-m-Y',strtotime($pickup_detail['date'])) . ' - ' . date('H:i',strtotime($time['start']));
        $this->session->data['myparcel_price_pickup'] = $time['price'];
        $pickup_detail['code'] = 'myparcel_'. $shipping_method_code;
        $pickup_detail['additional_service'] = [];
        $pickup_detail['time'] = [];
        $pickup_detail['time'][] = $time;
        $this->session->data['myparcel_shipping_choosed'] = $pickup_detail;
        $this->session->data['myparcel_price_pickup']['myparcel_shipping_choosed'] = $this->session->data['myparcel_shipping_choosed'];
        $this->session->data['pickup_time_start'] = $time['start'];
        $this->data['pickup_time_start'] = $time['start'];
    }

}
