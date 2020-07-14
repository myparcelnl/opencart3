<?php
class ModelExtensionMyparcelnlShipping extends Model
{
	public function getShippingMethods()
	{
		if(version_compare(VERSION, '3.0.0.0', '>=')) {
			$this->load->model('setting/extension');
			$extensions = $this->model_setting_extension->getInstalled('shipping');
		} else if(version_compare(VERSION, '2.0.0.0', '>=')) {
			$this->load->model('extension/extension');
			$extensions = $this->model_extension_extension->getInstalled('shipping');
		} else {
			$this->load->model('setting/extension');
			$extensions = $this->model_setting_extension->getInstalled('shipping');
		}
		$result = array();

		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/{extension/shipping,shipping}/*.php', GLOB_BRACE);

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				if (in_array($extension, $extensions)) {

					if(version_compare(VERSION, '2.3.0.0', '>=')) {
						$this->load->language('extension/shipping/' . $extension);
					} else {
						$this->load->language('shipping/' . $extension);
					}

					$result[] = array(
						'name' => $this->language->get('heading_title'),
						'code' => $extension,
						'status' => $this->config->get($extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
						'install' => $this->url->link('extension/extension/shipping/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
						'uninstall' => $this->url->link('extension/extension/shipping/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
						'installed' => in_array($extension, $extensions),
						'edit' => $this->url->link('extension/shipping/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
					);
				}
			}
		}

		return $result;
	}
}
