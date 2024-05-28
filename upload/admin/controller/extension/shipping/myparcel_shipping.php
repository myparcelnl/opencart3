<?php
require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

class ControllerExtensionShippingMyparcelShipping extends Controller {
	private $error = array();
	public function index() {
		$data = array();
		$data['myparcel'] = MyParcel($this->registry);

		$this->load->language(MyParcel()->getShippingPath() . 'myparcel_shipping');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_myparcel_shipping', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');

		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_cost'] = $this->language->get('entry_cost');
		$data['entry_tax_class'] = $this->language->get('entry_tax_class');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(MyParcel()->getModuleListPath(), 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(MyParcel()->getShippingPath() . 'myparcel_shipping', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link(MyParcel()->getShippingPath() . 'myparcel_shipping', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link(MyParcel()->getModuleListPath(), 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

		if (isset($this->request->post['shipping_myparcel_shipping_title'])) {
			$data['shipping_myparcel_shipping_title'] = $this->request->post['shipping_myparcel_shipping_title'];
		} else {
			$data['shipping_myparcel_shipping_title'] = $this->config->get('shipping_myparcel_shipping_title');
		}

		if (isset($this->request->post['shipping_myparcel_shipping_cost'])) {
			$data['shipping_myparcel_shipping_cost'] = $this->request->post['shipping_myparcel_shipping_cost'];
		} else {
			$data['shipping_myparcel_shipping_cost'] = $this->config->get('shipping_myparcel_shipping_cost');
		}

        if (isset($this->request->post['shipping_myparcel_shipping_tax_class_id'])) {
            $data['shipping_myparcel_shipping_tax_class_id'] = $this->request->post['shipping_myparcel_shipping_tax_class_id'];
        } else {
            $data['shipping_myparcel_shipping_tax_class_id'] = $this->config->get('shipping_myparcel_shipping_tax_class_id');
        }

        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['shipping_myparcel_shipping_status'])) {
			$data['shipping_myparcel_shipping_status'] = $this->request->post['shipping_myparcel_shipping_status'];
		} else {
			$data['shipping_myparcel_shipping_status'] = $this->config->get('shipping_myparcel_shipping_status');
		}

		if (isset($this->request->post['shipping_myparcel_shipping_sort_order'])) {
			$data['shipping_myparcel_shipping_sort_order'] = $this->request->post['shipping_myparcel_shipping_sort_order'];
		} else {
			$data['shipping_myparcel_shipping_sort_order'] = $this->config->get('shipping_myparcel_shipping_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

        if (version_compare(VERSION, '2.2.2.0', '>=')) {
            $this->response->setOutput($this->load->view(MyParcel()->getShippingPath() . 'myparcel_shipping', $data));
        } else {
            $this->response->setOutput($this->load->view(MyParcel()->getShippingPath() . 'myparcel_shipping.tpl', $data));
        }
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', MyParcel()->getShippingPath() . 'myparcel_shipping')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('shipping_myparcel_shipping', array(
			'shipping_myparcel_shipping_title' => 'Shipping & Handling',
            'shipping_myparcel_shipping_cost' => 0,
            'shipping_myparcel_shipping_tax_class_id' => 0,
			'shipping_myparcel_shipping_status' => 1,
			'shipping_myparcel_shipping_sort_order' => 0
		));
	}
}