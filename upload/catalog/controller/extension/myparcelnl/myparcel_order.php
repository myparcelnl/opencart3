<?php

class ControllerExtensionMyparcelnlMyparcelOrder extends Controller
{
    function updateOrderStatus()
    {
        require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
        MyParcel($this->registry);

        $this->load->model('checkout/order');
        /** @var ModelCheckoutOrder $model_order **/
        $model_order = $this->model_checkout_order;
        $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;

        $order_status_id = MyParcel()->settings->general->automatic_order_status;

        if (empty($order_id) || empty($order_status_id)) {
            $return = array(
                'success' => false,
            );
        } else {
            if (version_compare(VERSION, '2.0.0.0', '>=')) {
                ob_start();
                    $model_order->addOrderHistory($order_id, $order_status_id, MyParcel()->lang->get('mssg_order_status_changed_by_myparcel'), true);
                ob_clean();
            } else {
                ob_start();
                    $model_order->update($order_id, $order_status_id, MyParcel()->lang->get('mssg_order_status_changed_by_myparcel'), true);
                ob_clean();
            }
            $return = array(
                'success' => true
            );
        }

        echo json_encode($return);
        die;
    }
}