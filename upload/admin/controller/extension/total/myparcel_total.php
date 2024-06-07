<?php
require_once DIR_SYSTEM . 'library/myparcelnl/class_myparcel.php';

class ControllerExtensionTotalMyparcelTotal extends Controller {
	public function index()
	{
		$data = array();
		$data['myparcel'] = MyParcel($this->registry);

		$this->load->language(MyParcel()->getTotalPath() . 'myparcel_total');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			if (isset($this->request->post['total_myparcel_total_sort_order'])) {
				if (!is_numeric($this->request->post['total_myparcel_total_sort_order'])) {
					MyParcel()->notice->add(MyParcel()->lang->get('error_sort_order_must_be_numeric'), 'warning');
				}
			}

			if (!MyParcel()->notice->count('warning')) {
				$this->model_setting_setting->editSetting('total_myparcel_total', $this->request->post);

				$this->session->data['success'] = $this->language->get('text_success');

				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
			}
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

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
			'href' => $this->url->link(MyParcel()->getModuleListPath(), 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(MyParcel()->getTotalPath() . 'myparcel_total', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link(MyParcel()->getTotalPath() . 'myparcel_total', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link(MyParcel()->getModuleListPath(), 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

		if (isset($this->request->post['total_myparcel_total_status'])) {
			$data['total_myparcel_total_status'] = $this->request->post['total_myparcel_total_status'];
		} else {
			$data['total_myparcel_total_status'] = $this->config->get('total_myparcel_total_status');
		}

		if (isset($this->request->post['total_myparcel_total_sort_order'])) {
			$data['total_myparcel_total_sort_order'] = $this->request->post['total_myparcel_total_sort_order'];
		} else {
			$data['total_myparcel_total_sort_order'] = $this->config->get('total_myparcel_total_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
        if (version_compare(VERSION, '2.2.2.0', '>=')) {
            $this->response->setOutput($this->load->view(MyParcel()->getTotalPath() . 'myparcel_total', $data));
        } else {
            $this->response->setOutput($this->load->view(MyParcel()->getTotalPath() . 'myparcel_total.tpl', $data));
        }
	}

	function validate()
	{
		return true;
	}

	function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('total_myparcel_total', array(
			'total_myparcel_total_status' => 1,
			'total_myparcel_total_sort_order' => 2
		));
	}
}