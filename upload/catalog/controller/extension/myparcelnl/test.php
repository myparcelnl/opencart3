<?php

class ControllerExtensionMyparcelnlTest extends Controller
{
    public function index()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

        $helper = MyParcel($this->registry)->helper;
        $address_parts = $helper->getAddressComponents('Hoofdweg');
        var_dump($address_parts);die;
    }

    function serialize()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        $helper = MyParcel()->helper;
        $data = $this->db->escape(json_encode(array('key1' => 'data1 "haha"', 'key2' => 'data2'), true));
        var_dump($data);
    }

    function update()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        /** @var ModelMyparcelnlShipment $model_shipment **/
        MyParcel()->init($this->registry);
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment', true));
        $model_shipment = $registry->get('model_myparcelnl_shipment');
        //$model_shipment->update(69, 'shipment_data', array('abc' => 123, 'bde' => '"456"'));
        $data = $model_shipment->getData(69, 'shipment_data');

    }

    function test_die()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);
    }

    function get_order()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);
        $registry = MyParcel::$registry;
        $loader = $registry->get('load');
        $loader->model('sale/order');
        $model_order = $registry->get('model_sale_order');
        $order = $model_order->getOrder(1);
         var_dump($order);

    }

    function print_options()
    {
        $order_id = $_GET['order_id'];
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

        $is_log_enabled = MyParcel($this->registry)->settings->general->order_status_automation;

        $data = MyParcel($this->registry)->shipment->shipment_helper->getLatestExportedShipmentOptions($order_id);
        var_dump($data);

    }

}