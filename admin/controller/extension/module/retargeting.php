<?php
/**
  *  Retargeting Tracker for OpenCart 3.x
  *  @author Carol-Theodor Pelu
  *  admin/controller/extension/module/retargeting.php
*/

class ControllerExtensionModuleRetargeting extends Controller {

    private $error = array();

    /*
     *  Index method
     */
    public function index() {

        $this->load->language('extension/module/retargeting');

        $this->load->model('setting/setting');
        $this->load->model('setting/event');
        $this->load->model('localisation/language');
        $this->load->model('design/layout');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['languages'] = $this->model_localisation_language->getLanguages();
        $data['layouts']   = $this->model_design_layout->getLayouts();

        /*
         * Pull all layouts from DataBase
         */
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_retargeting', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        /* Translated strings */
        $data['heading_title']     = $this->language->get('heading_title');
        $data['text_edit']         = $this->language->get('text_edit');
        $data['text_enabled']      = $this->language->get('text_enabled');
        $data['text_disabled']     = $this->language->get('text_disabled');
        $data['text_token']        = $this->language->get('text_token');
        $data['entry_status']      = $this->language->get('entry_status');
        $data['entry_apikey']      = $this->language->get('entry_apikey');
        $data['entry_token']       = $this->language->get('entry_token');
        $data['entry_email']       = $this->language->get('entry_email');
        $data['entry_cart_button'] = $this->language->get('entry_cart_button');
        $data['entry_image']       = $this->language->get('entry_image');
        $data['button_save']       = $this->language->get('button_save');
        $data['button_cancel']     = $this->language->get('button_cancel');

        /*
         * Populate the errors array
         */
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($thi->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/retargeting', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/retargeting', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['user_token'] = $this->session->data['user_token'];

        /*
         * Populate custom variables
         */
        if (isset($this->request->post['module_retargeting_status'])) {
            $data['module_retargeting_status'] = $this->request->post['module_retargeting_status'];
        } else {
            $data['module_retargeting_status'] = $this->config->get('module_retargeting_status');
        }

        if (isset($this->request->post['module_retargeting_apikey'])) {
            $data['module_retargeting_apikey'] = $this->request->post['module_retargeting_apikey'];
        } else {
            $data['module_retargeting_apikey'] = $this->config->get('module_retargeting_apikey');
        }

        if (isset($this->request->post['module_retargeting_token'])) {
            $data['module_retargeting_token'] = $this->request->post['module_retargeting_token'];
        } else {
            $data['module_retargeting_token'] = $this->config->get('module_retargeting_token');
        }

        /* 1. setEmail */
        if (isset($this->request->post['module_retargeting_setEmail'])) {
            $data['module_retargeting_setEmail'] = $this->request->post['module_retargeting_setEmail'];
        } else {
            $data['module_retargeting_setEmail'] = $this->config->get('module_retargeting_setEmail');
        }

        /* 2. addToCart */
        if (isset($this->request->post['module_retargeting_addToCart'])) {
            $data['module_retargeting_addToCart'] = $this->request->post['module_retargeting_addToCart'];
        } else {
            $data['module_retargeting_addToCart'] = $this->config->get('module_retargeting_addToCart');
        }

        /* 3. clickImage */
        if (isset($this->request->post['module_retargeting_clickImage'])) {
            $data['module_retargeting_clickImage'] = $this->request->post['module_retargeting_clickImage'];
        } else {
            $data['module_retargeting_clickImage'] = $this->config->get('module_retargeting_clickImage');
        }

        /*
         * Common admin area items
         */
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/retargeting', $data));
    }

    public function install() {

        $this->load->model('setting/event');
        $this->load->model('design/layout');
        $this->load->model('setting/setting');

        foreach ($this->model_design_layout->getLayouts() as $layout) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "layout_module SET
                    layout_id = '{$layout['layout_id']}',
                    code = 'retargeting',
                    position = 'content_bottom',
                    sort_order = '99'
                ");
        }

        $this->model_setting_event->addEvent(
            'retargeting_add_order',
            'catalog/model/checkout/order/addOrderHistory/after',
            'extension/module/retargeting/eventAddOrderHistory'
        );
    }

    public function uninstall() {

        $this->load->model('design/layout');
        $this->load->model('setting/setting');

        $this->db->query("DELETE FROM " . DB_PREFIX . "layout_module WHERE code = 'retargeting'");
        $this->model_setting_setting->deleteSetting('retargeting');
        $this->model_setting_event->deleteEvent('retargeting');
    }

    /*
     * Validate method
     */
    public function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/retargeting')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->post['module_retargeting_apikey']) || strlen(utf8_decode($this->request->post['module_retargeting_apikey'])) < 3) {
            $this->error['warning'] = $this->language->get('error_apikey');
        }

        if (!isset($this->request->post['module_retargeting_token']) || strlen(utf8_decode($this->request->post['module_retargeting_token'])) < 3) {
            $this->error['warning'] = $this->language->get('error_token');
        }

        if (!isset($this->request->post['module_retargeting_token']) || strlen(utf8_decode($this->request->post['module_retargeting_apikey'])) < 3 &&
            !isset($this->request->post['module_retargeting_token']) || strlen(utf8_decode($this->request->post['module_retargeting_token'])) < 3) {
            $this->error['warning'] = $this->language->get('error_code');
        }

        return !$this->error;
    }
}
