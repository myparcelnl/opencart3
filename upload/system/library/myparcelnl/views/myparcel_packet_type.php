<?php
	printf('<a target="_blank" href="https://maps.google.com/maps?&amp;q=%s&z=16">%s</a>',$address_map_url, $address);
	if (!empty($shipping_methods)) {
		printf('<small class="meta">%s %s</small><br/>',MyParcel()->lang->get('shipping_methods_via') ,$shipping_methods);
	}
	if (!empty($package_type)) {
		printf('<a href="#" class="oc_show_shipment_options"><span class="oc_package_type">%s</span> &#x25BE;</a>', $package_type);
	}
?>

<?php if (!empty($delivery_data['data'])) : ?>
	<?php
		$delivery_options = $delivery_data['data'];

		$lang = MyParcel()->lang;
		/** @var MyParcel_Shipment_Helper $shipment_helper **/
		$shipment_helper = MyParcel()->shipment->shipment_helper;
		$is_pickup = $shipment_helper->isPickup( null, $delivery_options );
		$is_mailbox = false;
		$time_title = '';

		if (isset($delivery_options['time'][0]['price_comment'])) {
			switch ($delivery_options['time'][0]['price_comment']) {
				case 'morning':
					$time_title = $lang->get( 'entry_morning_delivery');
					break;
				case 'standard':
					$time_title = '';
					break;
				case 'night':
				case 'avond':
					$time_title = $lang->get( 'entry_evening_delivery');
					break;
				case 'mailbox':
					$is_mailbox = true;
			}
		} else {
			if (isset($delivery_options['price_comment'])) {
				$time_title = $lang->get( 'entry_time_not_specified');
			}
		}

		// If Mailbox
		if ($is_mailbox) {
//			printf(
//				'<strong>%s: </strong>%s',
//				$lang->get('entry_delivery_type'),
//				$lang->get('entry_mailbox')
//			);
		}
		// If Standard || Morning || Evening || Pickup || Pickup Express
		else {
			if (!empty($time_title)) {
				$time_title = '(' . $time_title . ')';
			}
			printf('<div class="delivery-date"><strong>%s: </strong>%s %s</div>', $lang->get('Delivery date'), date($lang->get('date_format'), strtotime($delivery_options['date'])), $time_title);

			if ($is_pickup) {
				switch ($delivery_options['price_comment']) {
					case 'retail':
						$title = $lang->get( 'entry_pickup_title');
						break;
					case 'retailexpress':
						$title = $lang->get( 'entry_pickup_express_title');
						break;
					default:
						$title = $lang->get( 'entry_pickup_title');
				}

				printf(
					'<strong>%s: </strong>%s, %s %s, %s, %s',
					$title,
					$delivery_options['location'],
					$delivery_options['street'],
					$delivery_options['number'],
					$delivery_options['postal_code'],
					$delivery_options['city']
				);
			}
		}
	?>
<?php endif; ?>