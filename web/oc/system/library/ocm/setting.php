<?php
namespace OCM;
final class Setting {
    public function __construct($registry) {
        $this->config = $registry->get('config');
        $this->request = $registry->get('request');
        $this->ocm_meta = $registry->get('ocm_meta');
        $this->prefix = VERSION >= '3.0.0.0' ? $this->ocm_meta['type'] .'_' : '';
    }

    public function editSetting($setting) {
        $data = array();
        foreach ($setting as $key => $default) {
            $key = $this->prefix . $key;
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = '';
            }
        }
        return array(
            'key' => $this->prefix . $this->ocm_meta['name'],
            'value' => $data
        );
    }

    public function getSetting($setting, $languages = array()) {
        $data = array();
        foreach ($setting as $key => $default) {
            $key = $this->prefix . $key;
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = $this->config->get($key);
            }
            if (!$data[$key] && $default) {
                if (is_array($default)) {
                    foreach ($default as $_key => $_value) {
                        if ($_key === '__LANG__') {
                            foreach ($languages as $language) {
                                if (!isset($data[$key][$language['language_id']]) || !$data[$key][$language['language_id']]) {
                                    $data[$key][$language['language_id']] = $_value;
                                }
                            }
                        } else {
                            $data[$key][$_key] = $_value; 
                        }
                    }
                } else {
                    $data[$key] = $default;
                }
            }
        }
        return $data;
    }
}