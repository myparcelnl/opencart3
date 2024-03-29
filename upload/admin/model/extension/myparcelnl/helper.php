<?php
require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

class ModelExtensionMyparcelnlHelper extends Model
{
    function getContent($template_name, $params = array(), $echo = false)
    {
        $content = call_user_func_array( array( MyParcel($this->registry)->view, $template_name ), $params );

        if ($echo) {
            echo $content;
        } else {
            return $content;
        }
    }

    function getCssUrl()
    {
        return MyParcel($this->registry)->getCssUrl();
    }

    function getJsUrl()
    {
        return MyParcel($this->registry)->getJsUrl();
    }

    function addDeliveryDataIntoOrder($order_info)
    {
        return MyParcel($this->registry)->shipment->checkout->addDeliveryDataIntoOrder($order_info);
    }
}