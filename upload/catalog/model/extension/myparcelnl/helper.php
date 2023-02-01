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

    function initMyParcel()
    {
        MyParcel($this->registry);
    }

    function getCssUrl()
    {
        return MyParcel($this->registry)->getCssUrl();
    }

    function getJsUrl()
    {
        return MyParcel($this->registry)->getJsUrl();
    }

    function addCompatibleScript($script_name, $document)
    {
        return MyParcel($this->registry)->addCompatibleScript($script_name, $document);
    }

    function saveDeliveryOptionsInCheckout()
    {
        return MyParcel($this->registry)->shipment->shipment_helper->saveDeliveryOptionsInCheckout($this->request->post);
    }

    function addDeliveryDataIntoOrder($order_info)
    {
        return MyParcel($this->registry)->shipment->checkout->addDeliveryDataIntoOrder($order_info);
    }

    function getOrderCountryIsoCode($order_id)
    {
        $order_query = $this->db->query("SELECT `shipping_country_id` FROM `" . DB_PREFIX . 'order' . "` WHERE order_id = " . (int)$order_id . " LIMIT 1");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                return $country_query->row['iso_code_2'];
            }
        }

        return '';
    }
}