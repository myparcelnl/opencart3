<?php
if (version_compare(VERSION, '2.2.0.0', '>=')) {
	trait MyparcelT {
		public function getTotal() {
			if ($this->cart->hasShipping() && isset($this->session->data['shipping_method'])) {
				if (!class_exists('MyParcel')) {
					require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
					MyParcel($this->registry);
				}

				if (MyParcel()->shipment->checkout->checkValidShippingMethod()
					&& !empty($this->session->data['myparcel']['data'])
				) {

					/** @var MyParcel_Shipment_Checkout $checkout_helper * */
					$checkout_helper = MyParcel()->shipment->checkout;
					$data = $this->session->data['myparcel'];

					// Get current shipping title
					$checkout_shipping_method = MyParcel()->shipment->shipment_helper->getShippingCodeByShippingQuote($this->session->data['shipping_method']['code']);

					$total_array = $checkout_helper->getTotalArray($data, false, null, '', false);
					$total_price = 0;

					foreach ($total_array as $total_code => $total_item) {
						$total_price += $total_item['price'];
					}

					if (version_compare(VERSION, '2.3.0.0', '>=')) {
						$this->load->language('extension/shipping/' . $checkout_shipping_method);
					} else {
						$this->load->language('shipping/' . $checkout_shipping_method);
					}

					if ($total_price > 0) {
						$details = MyParcel()->lang->get('entry_details');
						$title = $this->config->get('shipping_myparcel_shipping_title');
						$this->session->data['shipping_method']['title'] =
							"$title <a class=\"button-myparcel-total-details\" data-collapse=\"1\">$details<i class=\"fa fa-caret-down\"></i></a>";
						$this->session->data['shipping_method']['cost'] = $total_price;
						$this->session->data['shipping_method']['text'] = $title; // currently not used in OC that I can see
					}
				}
			}
		}
	}
} else {
	trait MyparcelT {
		public function getTotal(&$totals, &$price, &$taxes) {
			if ($this->cart->hasShipping() && isset($this->session->data['shipping_method'])) {

				if (!class_exists('MyParcel')) {
					require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';
					MyParcel($this->registry);
				}

				if (MyParcel()->shipment->checkout->checkValidShippingMethod()) {

					if (!empty($this->session->data['myparcel']['data'])) {

						/** @var MyParcel_Shipment_Checkout $checkout_helper * */
						$checkout_helper = MyParcel()->shipment->checkout;
						$data = $this->session->data['myparcel'];

						// Get current shipping title
						$checkout_shipping_method = MyParcel()->shipment->shipment_helper->getShippingCodeByShippingQuote($this->session->data['shipping_method']['code']);

						$total_array = $checkout_helper->getTotalArray($data, false, null, '', false);
						$total_price = 0;

						foreach ($total_array as $total_code => $total_item) {
							$total_price += $total_item['price'];
						}

						if (version_compare(VERSION, '2.3.0.0', '>=')) {
							$this->load->language('extension/shipping/' . $checkout_shipping_method);
						} else {
							$this->load->language('shipping/' . $checkout_shipping_method);
						}

						if ($total_price > 0) {
							$totals[] = array(
								'code' => 'total_myparcel_total',
								'title' => $this->config->get('shipping_myparcel_shipping_title') .
									'
							<a class="button-myparcel-total-details" data-collapse="1">'
									. MyParcel()->lang->get('entry_details') .
									'<i class="fa fa-caret-down"></i>
							</a>
						',
								'value' => $total_price,
								'sort_order' => $this->config->get('total_myparcel_total_sort_order')
							);

							$price += $total_price;
						}
					}
				}
			}
		}
	}
}