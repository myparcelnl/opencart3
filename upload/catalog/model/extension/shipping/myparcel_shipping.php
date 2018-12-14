<?php
class ModelExtensionShippingMyparcelShipping extends Model {
	function getQuote($address) {

        $checkout_settings          = $this->config->get('module_myparcelnl_fields_checkout');
        $status_config = $this->config->get('shipping_myparcel_shipping_status');
		$status = !empty($status_config) ? true : false;
        $belgium_enabled = !empty($checkout_settings['belgium_enabled']) ? true : false;
		$country_iso_code = isset($address['iso_code_2']) ? $address['iso_code_2'] : null;

		$method_data = array();

		if ($status && ($country_iso_code == 'NL' || ($belgium_enabled && $country_iso_code == 'BE'))) {
			$total_price = 0;

			/** @var MyParcel_Shipment_Checkout $checkout_helper **/
			/**
			 * This case should happen in admin / edit order
			 * In the last step, OC needs to retrieve a list of shipment methods
			 * But they don't pass order_id to getQuote function
			 * So we need to add it via session
			**/
			if (isset($this->session->data['myparcel_order_id'])) {

                if (!class_exists('MyParcel')) {
                    require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
                    MyParcel($this->registry);
                }

				$checkout_helper = MyParcel()->shipment->checkout;
				$checkout_helper->setSessionOrderDeliveryOptions($this->session->data['myparcel_order_id']);
				$data = isset($this->session->data['myparcel']) ? $this->session->data['myparcel'] : false;

				if ($data) {
					$total_array = $checkout_helper->getTotalArray($data);
					foreach ($total_array as $total_code => $total_item) {
						$total_price += $total_item['price'];
					}
				}
			}

			$quote_data = array();

			$quote_data['myparcel_shipping'] = array(
				'code'         => 'myparcel_shipping.myparcel_shipping',
				'title'        => $this->config->get('shipping_myparcel_shipping_title'),
				'cost'         => $total_price,
				'tax_class_id' => '',
				'text'         => $this->currency->format($this->tax->calculate($total_price, '', ''), $this->session->data['currency'])
			);

			$method_data = array(
				'code'       => 'myparcel_shipping',
				'title'      => $this->config->get('shipping_myparcel_shipping_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_myparcel_shipping_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}