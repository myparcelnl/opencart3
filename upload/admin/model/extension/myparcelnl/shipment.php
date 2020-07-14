<?php
class ModelExtensionMyparcelnlShipment extends Model
{
	static $table_name = 'myparcel_shipment';

    /**
     * Get myparcel export settings saved from backoffice
     * @param int $order_id
     * @return array options
     **/
    public function getData($order_id, $column_name)
    {
        $order_query = $this->db->query("SELECT "  . $column_name . " FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
        return $this->getValueFromQuery($column_name, $order_query);
    }

	/**
	 * Get myparcel export settings saved from backoffice
	 * @param int $order_id
	 * @return array options
	 **/
	public function getSavedExportSettings($order_id)
	{
		$order_query = $this->db->query("SELECT export_settings FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		$result = $this->getValueFromQuery('export_settings', $order_query);
		if (!$result) {
			return array();
		}
		return $result;
	}

	/**
	 * Get myparcel delivery options saved from frontend checkout
	 * @param int $order_id
	 * @return array options
	 **/
	public function getMyParcelDeliveryOptions($order_id)
	{
		$order_query = $this->db->query("SELECT delivery_options FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		$result = $this->getValueFromQuery('delivery_options', $order_query);

		if (!$result) {
			return array();
		}
		return $result;
	}

	/**
	 * Get myparcel delivery options saved from frontend checkout
	 * @param int $order_id
	 * @return array options
	 **/
	public function getMyParcelDeliveryOptionsSignedRecipientOnly($order_id)
	{
		$order_query = $this->db->query("SELECT delivery_options, signed, recipient_only FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		$result['data'] = $this->getValueFromQuery('delivery_options', $order_query);
		$result['signed'] = $this->getValueFromQuery('signed', $order_query);
		$result['recipient_only'] = $this->getValueFromQuery('recipient_only', $order_query);

		if (!$result) {
			return array();
		}
		return $result;
	}

	/**
	 * Get myparcel shipments data saved from backoffice
	 * @param int $order_id
	 * @return array options
	 **/
	public function getSavedMyParcelShipments($order_id)
	{
		$order_query = $this->db->query("SELECT shipment_data FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		$result = $this->getValueFromQuery('shipment_data', $order_query);

		if (!$result) {
			return array();
		}
		return $result;
	}

	/**
	 * Get myparcel_signed option from frontend checkout
	 * @param int $order_id
	 * @return array options
	 **/
	public function getMyParcelSigned($order_id)
	{
		$order_query = $this->db->query("SELECT signed FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		return $this->getValueFromQuery('signed', $order_query);
	}

	/**
	 * Get myparcel_only_recipient option from frontend checkout
	 * @param int $order_id
	 * @return array options
	 **/
	public function getMyParcelOnlyRecipient($order_id)
	{
		$order_query = $this->db->query("SELECT recipient_only FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		return $this->getValueFromQuery('recipient_only', $order_query);
	}

	/**
	 * Get myparcel_only_recipient option from frontend checkout
	 * @param int $order_id
	 * @return array options
	 **/
	public function getMyParcelOrderPrices($order_id)
	{
		$order_query = $this->db->query("SELECT prices FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		return $this->getValueFromQuery('prices', $order_query);
	}

	/**
	 * Get extra settings saved from admin order overview
	 * @param int $order_id
	 * @return array options Array('number_of_copies' => 1)
	 **/
	public function getSavedExtraExportSettings($order_id)
	{
		$order_query = $this->db->query("SELECT extra_options FROM `" . DB_PREFIX . self::$table_name . "` WHERE order_id = " . $order_id . " LIMIT 1");
		return $this->getValueFromQuery('extra_options', $order_query);
	}

	/**
	 * Save export settings in database
	 * @param array $settings
	 **/
	public function saveExportSetting($settings)
	{

	}

	/**
	 * Save shipment data
	 * @param int $order_id
	 * @param array $shipment
	 * @return boolean the update is successful or not
	 **/
	public function saveShipmentData ( $order_id, $shipment )
	{
		if ( empty($shipment) || !isset($shipment['shipment_id']) ) {
			return false;
		}

		$shipment_id = $shipment['shipment_id'];
		$shipments = array();
		$shipments[$shipment_id] = $shipment;
        $keep_old_shipments =intval(MyParcel()->settings->general->keep_old_shipments);
		if ( !empty($keep_old_shipments) ) {

			if ( $old_shipments = $this->getSavedMyParcelShipments($order_id) ) {

				if (isset($old_shipments[$shipment_id])) {
					unset($old_shipments[$shipment_id]);
				}

				$shipments = $old_shipments + $shipments;
			}
		}

		$this->update( $order_id, 'shipment_data', $shipments );
		return true;
	}

	/**
	 * Update data in one of the columns of the table
	 * @param string $column_name
	 * @param mixed $value
	 **/
	public function update($order_id, $column_name, $value)
	{
		if ( is_array( $value ) || is_object( $value ) )
			$data = $this->db->escape(json_encode($value, true));
		else
			$data = $value;

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . self::$table_name . " WHERE order_id = " . (int)$order_id);

		if ($query->num_rows) {
			$this->db->query("UPDATE " . DB_PREFIX . self::$table_name . " SET " . $column_name . "='" . $data . "' WHERE order_id =" . $order_id);
		} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . self::$table_name . " SET order_id = " . intval($order_id) . ", " . $column_name . "='" . $data . "'");
		}
	}

	/**
	 * Get column value from rows
	 * @param string $column_name
	 * @param array $order_query
	 * @return string column value
	 **/
	protected function getValueFromQuery($column_name, $order_query)
	{
		if ($order_query->num_rows) {
			$order_row = current($order_query->rows);
			$value = isset($order_row[$column_name]) ? $order_row[$column_name] : null;
			if ($value) {
				$decoded_value = json_decode($value, true);
				if (!empty($decoded_value)) {
					return $decoded_value;
				} else {
					return $value;
				}
			} else if ($value == '0') {
				return 0;
			}
		}
		return null;
	}
}
