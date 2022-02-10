<?php
namespace OCM\Traits\Back\Controller;
trait Crud {
    public function quick_save() {
        $this->load->language($this->ext_path);
        $this->load->model($this->ext_path);
        $json = array();
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $save = array();
            if(isset($this->request->post[$this->meta['name']]) && isset($this->request->post['tab_id'])) {
                $save['method_data'] = json_encode($this->request->post[$this->meta['name']]);
                $save['tab_id'] = $this->request->post['tab_id'];
                $save['sort_order'] = (int)$this->request->post['sort_order'];
                $this->{$this->ext_key}->addData($save);
                if (method_exists($this, 'onSave')) {
                    $this->onSave($save);
                }
                $json['success'] = true;
            } else {
                $json['error'] = 'error! - unable to save';
            }
        } else {
            $json['error'] = $this->language->get('error_permission');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
    public function save_general() {
        $this->load->language($this->ext_path);
        $this->load->model($this->ext_path);
        $this->load->model('setting/setting');
        $json = array();
        /* Delete old cache */
        $this->cache->delete('ocm.' . $this->meta['name']);
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $save = $this->ocm->setting->editSetting($this->setting);
            if (method_exists($this, 'onValidateGeneral')) {
                $this->onValidateGeneral($save);
            }
            $this->model_setting_setting->editSetting($save['key'], $save['value']);
             // save sorting if available 
            $sorted = isset($this->request->post['sorted']) ? $this->request->post['sorted'] : array();
            if (method_exists($this->{$this->ext_key}, 'saveSort') || property_exists($this->{$this->ext_key}, 'saveSort')) {
                $this->{$this->ext_key}->saveSort($sorted);
            }
            if (method_exists($this, 'onSaveGeneral')) {
                $this->onSaveGeneral($save);
            }
            $json['success'] = true;
        } else{
            $json['error']=$this->language->get('error_permission');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function delete() {
        $this->load->language($this->ext_path);
        $this->load->model($this->ext_path);
        $json = array();
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if($this->request->post['tab_id']) {
                $method_data = array(); 
                if (method_exists($this->{$this->ext_key}, 'getDataByTabId') || property_exists($this->{$this->ext_key}, 'getDataByTabId')) {
                    $method_data = $this->{$this->ext_key}->getDataByTabId($this->request->post['tab_id']);
                }
                $this->{$this->ext_key}->deleteData($this->request->post['tab_id']);
                if (method_exists($this, 'onDelete')) {
                    $this->onDelete($method_data);
                }
                $json['success'] = true;
            }
            else {
               $json['error']='error! - unable to delete';
            }
        } else {
            $json['error'] = $this->language->get('error_permission');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
    public function clearCache() {
        $json = array();
        /* Delete old cache */
        $this->cache->delete('ocm.' . $this->meta['name']);
        $json['success'] = true;
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
}