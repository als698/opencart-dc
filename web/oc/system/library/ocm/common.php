<?php
/* Commmon methods are used by both front and back */
namespace OCM;
final class Common {
    private $registry;
    public function __construct($registry) {
        $this->registry = $registry;
        $this->request = $registry->get('request');
        $this->config = $registry->get('config');
    }
    public function __get($name) {
        return $this->registry->get($name);
    }
    public function getConfig($key, $prefix = '') {
        $prefix = VERSION >= '3.0.0.0' ? $prefix .'_' : '';
        $key = $prefix . $key;
        return $this->config->get($key);
    }
    public function getExtPath($type) {
        if ($type === 'shipping') {
            $key = (VERSION >= '2.3.0.0') ? 'extension/shipping/' : 'shipping/';
        } else if ($type === 'total') {
            $key = (VERSION >= '2.3.0.0') ? 'extension/total/' : 'total/';
        } else if ($type === 'module') {
            $key = (VERSION >= '2.3.0.0') ? 'extension/module/' : 'module/';
        }
        else if ($type === 'payment') {
            $key = (VERSION >= '2.3.0.0') ? 'extension/payment/' : 'payment/';
        }
        return $key;
    }
    public function getSiteURL() {
        $store_url = $this->request->server['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER;
        if (defined('HTTP_CATALOG')) {
            $store_url = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        }
        return $store_url;
    }
    public function getVar($name, $more = array()) {
        $value = isset($this->session->data[$name]) ? $this->session->data[$name] : '';
        $value = isset($this->request->post[$name]) ? $this->request->post[$name] : $value;
        $value = isset($this->request->get[$name]) ? $this->request->get[$name] : $value;
        if (!$value && $more) {
            foreach ($more as $type => $name) {
                if ($type == 's') {
                    $value = isset($this->session->data[$name]) ? $this->session->data[$name] : $value;
                }
                if ($type == 'p') {
                    $value = isset($this->request->post[$name]) ? $this->request->post[$name] : $value;
                }
                if ($type == 'g') {
                    $value = isset($this->request->get[$name]) ? $this->request->get[$name] : $value;
                }
                if ($value) {
                    break;
                }
            }
        }
        return $value;
    }
    public function curlReq($url, $method = 'GET', $data = false, $header = array(), $param = array()) {
        $curl = curl_init();
        switch ($method) {
            case "POST":
            if (is_array($data)) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }  
            break; 
            default:
            if ($data) {
                $url = rtrim($url, '?');
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSLVERSION, 6);

        if (!empty($header) && is_array($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header); 
        }
        if (!empty($param['auth'])) {
           curl_setopt($curl, CURLOPT_USERPWD, $param['auth']);
        }
        if (!empty($param['ua'])) {
           curl_setopt($curl, CURLOPT_USERAGENT, $param['ua']);
        }
        $result = curl_exec($curl);
        if (isset($param['debug']) && $param['debug']) {
            curl_setopt($curl, CURLOPT_HEADER, true);
            $this->log->write('Curl Debug: ' . curl_error($curl));
        }
        curl_close($curl);
        return $result;
    }
    public function toCurlHeader($params) {
        if (!$params) return array();
        $return = array();
        foreach ($params as $param) {
            $return[] = $param['name'] . ':' . $param['value'];
        }
        return $return;
    }
    public function toCurlData($params) {
        if (!$params) return array();
        $return = array();
        foreach ($params as $param) {
            $return[$param['name']] = $param['value'];
        }
        return $return;
    }
}