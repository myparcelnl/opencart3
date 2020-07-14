<?php

class MyParcel_Setting
{
	public function __get($name)
	{
		switch ($name) {
			case 'general':
				return new MyParcel_Setting_General();
			case 'export':
				return new MyParcel_Setting_Export();
			case 'checkout':
				return new MyParcel_Setting_Checkout();
		}
	}
}

class MyParcel_Setting_General
{
	public function __get($field_name)
	{
		$registry = MyParcel::$registry;
		$config = $registry->get('config');
		$settings = $config->get('module_myparcelnl_fields_general');

		if ($settings && isset($field_name) && array_key_exists($field_name,$settings)) {
			switch ($field_name) {
				case 'log_api_communication':
				case 'shipment_directly':
				case 'order_status_automation':
					return (intval($settings[$field_name]) == 1);
				default:
					return $settings[$field_name];
			}
		}else{
			return false;
		}
	}

}

class MyParcel_Setting_Export
{
	public function __get($field_name)
	{
		$registry = MyParcel::$registry;
		$config = $registry->get('config');
		$settings = $config->get('module_myparcelnl_fields_export');
		if ($settings && isset($field_name) && array_key_exists($field_name,$settings)){
			return $settings[$field_name];
		}else{
			return false;
		}
	}

}

class MyParcel_Setting_Checkout
{
	public function __get($field_name)
	{
		$registry = MyParcel::$registry;
		$config = $registry->get('config');
		$settings = $config->get('module_myparcelnl_fields_checkout');
		if ($settings && isset($field_name) && array_key_exists($field_name,$settings)) {
			switch ($field_name) {
				case 'mailbox_enabled':
					return (intval($settings[$field_name]) == 1);
				default:
					return $settings[$field_name];
			}
			return $settings[$field_name];
		}else{
			return false;
		}
	}

}

return new MyParcel_Setting();