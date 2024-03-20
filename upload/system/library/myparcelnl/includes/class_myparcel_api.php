<?php
require_once DIR_SYSTEM . 'library/myparcelnl/includes/class_myparcel_curl.php';

if ( class_exists( 'MyParcel_Curl' ) ) :

class MyParcel_Api extends MyParcel_Curl
{

	public $api_domain = "https://api.myparcel.nl/";
	private $key;

	/**
	 * Default constructor
	 *
	 * @param  string  $key     API Key provided by MyParcel
	 */
	function __construct( $key = null ) {

		if (empty($key)) {
			//throw new Exception('MyParcel API key is empty');
		}

		parent::__construct();

		$this->key = $key;
	}

	/**
	 * Add shipment
	 * @param array  $shipment_data array of shipments
	 * @param string $type      shipment type: standard/return/unrelated_return
	 * @return Array $response
	 */
	public function addShipments ( $shipment_data, $type = 'standard' )
	{
	    $shipment_data = $this->validateAndFixData($shipment_data);

		$endpoint = 'shipments';
		// define content type
        switch ($type) {
            case 'standard': default:
            $content_type = 'application/vnd.shipment+json;charset=utf-8;version=1.1';
            $data_key = 'shipments';
            break;
            case 'return':
                $content_type = 'application/vnd.return_shipment+json; charset=utf-8; version=1.1';
                $data_key = 'return_shipments';
                break;
            case 'unrelated_return':
                $content_type = 'application/vnd.unrelated_return_shipment+json; charset=utf-8; version=1.1';
                $data_key = 'unrelated_return_shipments';
                break;
        }

		$data = array(
			'data' => array (
				$data_key => $shipment_data,
			),
		);

		$json = json_encode( $data );

		$headers = array(
			"Content-type: " . $content_type . "; charset=UTF-8",
			'Authorization: basic '. base64_encode("{$this->key}"),
            'User-Agent:' .  'OpenCart/2.3.0.2'
		);

		$request_url = $this->api_domain . $endpoint;
		MyParcel()->log->add($request_url);
		$response = $this->post($request_url, $json, $headers);

		return $response;
	}

	/**
	 * Delete Shipment
	 * @param  array  $ids shipment ids
	 * @return array       response
	 */
	public function deleteShipments ( $ids )
	{
		$endpoint = 'shipments';

		$headers = array (
			'Accept: application/json; charset=UTF-8',
			'Authorization: basic '. base64_encode("{$this->key}"),
		);

		$request_url = $this->api_domain . $endpoint . '/' . implode(';', $ids);
		$response = $this->delete($request_url, $headers );

		return $response;
	}

	/**
	 * Unrelated return shipments
	 * @return array       response
	 */
	public function unrelatedReturnShipments ()
	{
		$endpoint = 'return_shipments';

		$headers = array (
			'Authorization: basic '. base64_encode("{$this->key}"),
		);

		$request_url = $this->api_domain . $endpoint;
		$response = $this->post($request_url, '', $headers );

		return $response;
	}

	/**
	 * Get shipments
	 * @param  array  $ids request parameters
	 * @return array          response
	 */
	public function getShipments ( $ids )
	{
		$endpoint = 'shipments';

		$headers = array (
			'Authorization: basic '. base64_encode("{$this->key}"),
		);

		$request_url = $this->api_domain . $endpoint . '/' . implode(';', (array) $ids);

		MyParcel()->log->add($request_url);
		$response = $this->sendRequest($request_url, 'GET', null, $headers);

		return $response;
	}

	/**
	 * Get shipment labels
	 * @param  array  $ids    shipment ids
	 * @param  array  $params request parameters
	 * @param  string $return pdf or json
	 * @return array          response
	 */
	public function getShipmentLabels ( $ids, $params = array(), $return = 'pdf' )
	{
		$endpoint = 'shipment_labels';

		if ( $return == 'pdf' ) {
			$accept = 'Accept: application/pdf'; // (For the PDF binary. This is the default.)
			$raw = true;
		} else {
			$accept = 'Accept: application/json; charset=UTF-8'; // (For shipment download link)
			$raw = false;
		}

		$headers = array (
			$accept,
			'Authorization: basic '. base64_encode("{$this->key}"),
		);

		$request_url = MyParcel()->helper->add_query_arg( $params, $this->api_domain . $endpoint . '/' . implode(';', $ids) );
		MyParcel()->log->add($request_url);
		MyParcel()->log->add(var_export($headers, true));
		MyParcel()->log->add($this->key);

		$response = $this->get($request_url, $headers, $raw);

		return $response;
	}

    /**
     * Track shipments
     * @param  array  $ids    shipment ids
     * @param  array  $params request parameters
     * @return array          response
     */
    public function getTracktraces ( $ids, $params = array() )
    {
        $endpoint = 'tracktraces';

        $headers = array (
            'Authorization: basic '. base64_encode("{$this->key}"),
        );

        $request_url = MyParcel()->helper->add_query_arg( $params, $this->api_domain . $endpoint . '/' . implode(';', $ids) );
        $response = $this->sendRequest($request_url, 'GET', null, $headers);
        return $response;
    }

    public function getTracktraceUrl( $order_id, $tracktrace )
    {
        if (empty($order_id))
            return;
        $registry = MyParcel::$registry;

        $loader = $registry->get('load');
        $loader->model(MyParcel()->getModelPath('shipment'));
        $model_shipment = $registry->get('model_extension_myparcelnl_shipment');

        $shipment_data = $model_shipment->getSavedMyParcelShipments($order_id);
        $shipment_data = array_shift($shipment_data);
        $response = $this->getTracktraces([$shipment_data['shipment_id']]);

        if ($response['code'] === 200) {
            $tracktrace = array_shift($response['body']['data']['tracktraces']);
            return $tracktrace['link_consumer_portal'];
        }

        $order_info = MyParcel()->helper->getShippingOrder($order_id);
        $country = $order_info['shipping_iso_code_2'];
        $postcode = $order_info['shipping_postcode'];
        // set url for NL or foreign orders
        $shipment_helper = MyParcel()->shipment->shipment_helper;
        $is_pickup = $shipment_helper->isPickup( $order_id );
        if ($country == 'NL') {
            // use billing postcode for pickup/pakjegemak
            if ( $is_pickup ) {
                $postcode = $order_info['payment_postcode'];;
            }
            // $tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Inbox/Search?lang=nl&B=%s&P=%s', $tracktrace, $postcode);
            // $tracktrace_url = sprintf('https://mijnpakket.postnl.nl/Claim?Barcode=%s&Postalcode=%s', $tracktrace, $postcode);
            $tracktrace_url = sprintf('https://jouw.postnl.nl/track-and-trace/%s-NL-%s?language=nl', $tracktrace, $postcode);
        } else {
            $tracktrace_url = sprintf('https://www.internationalparceltracking.com/Main.aspx#/track/%s/%s/%s', $tracktrace, $country, $postcode);
        }

        return $tracktrace_url;
    }

	public function getTracktraceLinks ( $order_id )
	{
		if ( $consignments = $this->getTracktraceShipments( $order_id )) {
			foreach ($consignments as $key => $consignment) {
				$tracktrace_links[] = $consignment['tracktrace_link'];
			}
			return $tracktrace_links;
		} else {
			return false;
		}
	}


	public function getTracktraceShipments ( $order_id )
	{
		$registry = MyParcel::$registry;
		$loader = $registry->get('load');
		$loader->model(MyParcel()->getModelPath('shipment'));
		$model_shipment = $registry->get('model_extension_myparcelnl_shipment');
		$shipments = $model_shipment->getSavedMyParcelShipments($order_id);
		if (empty($shipments)) {
			return false;
		}

		foreach ($shipments as $shipment_id => $shipment) {
			// skip concepts, letters & mailbox packages
			if (empty($shipment['tracktrace'])) {
				unset($shipments[$shipment_id]);
				continue;
			}
			// add links & urls
			$shipments[$shipment_id]['tracktrace_url'] = $tracktrace_url = $this->getTracktraceUrl( $order_id, $shipment['tracktrace'] );
			$shipments[$shipment_id]['tracktrace_link'] = sprintf('<a href="%s">%s</a>', $tracktrace_url, $shipment['tracktrace']);
		}

		if (empty($shipments)) {
			return false;
		}


		return $shipments;
	}


	/**
	 * Get delivery options
	 * @param array 	$params
	 * @param boolean 	$raw
	 * @return array 	response
	 */
	public function getDeliveryOptions ( $params = array(), $raw = false )
	{
		$endpoint = 'delivery_options';

		$request_url = MyParcel()->helper->add_query_arg( $params, $this->api_domain . $endpoint );
		$response = $this->get($request_url, null, $raw);

		return $response;
	}

	function getLocalRequest($route = 'myparcelnl/myparcel_order/updateorderstatus', $params = array())
	{
		$url = MyParcel()->getRootUrl() . 'index.php?route=' . $route;
		$url = MyParcel()->helper->add_query_arg($params, $url);
		$response = $this->get($url, array(), false);

		return $response;
	}

	function validateAndFixData($shipment_data)
    {
        if (!empty($shipment_data)) {
            foreach ($shipment_data as &$shipment_data_item) {
                if (!empty($shipment_data_item['options']['label_description'])) {
                    $shipment_data_item['options']['label_description'] = substr($shipment_data_item['options']['label_description'], 0, 44);
                }
            }
        }
        return $shipment_data;
    }
}
	$registry = MyParcel::$registry;
	$config = $registry->get('config');
	$general_settings = $config->get('module_myparcelnl_fields_general');
	$api = isset($general_settings['api']) ? $general_settings['api'] : '';
	return new MyParcel_Api($api);

endif;