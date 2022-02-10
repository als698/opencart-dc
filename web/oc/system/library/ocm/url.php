<?php
namespace OCM;
final class Url {
    public function __construct($registry) {
        $this->session = $registry->get('session');
        $this->url = $registry->get('url');
        $this->ocm_meta = $registry->get('ocm_meta');
    }
    public function getToken() {
        $data = array();
        if (isset($this->session->data['user_token']) && VERSION >= '3.0.0.0') {
            $data['key'] = 'user_token';
            $data['value'] = $this->session->data['user_token'];
        } else if (isset($this->session->data['token'])) {
            $data['key'] = 'token';
            $data['value'] = $this->session->data['token'];
        }
        return $data;
    }
    public function link($route, $url = '', $secure = true) {
        $token = $this->getToken();
        if($token) {
            $url .= ($url ? '&' : '') . ($token['key'] . '=' . $token['value']);
        }
        return $this->url->link($route, $url, $secure);
    }
    public function getExtensionURL() {
        return $this->link($this->ocm_meta['path'] . $this->ocm_meta['name'], '', true);
    }
    public function getExtensionsURL() {
        $url = '';
        if (VERSION >= '3.0.0.0'){
            $route = 'marketplace/extension';
            $url .= 'type='.$this->ocm_meta['type'];
        } else if (VERSION >= '2.3.0.0') {
            $route = 'extension/extension';
            $url .= 'type='.$this->ocm_meta['type'];
        } else {
            $route = 'extension/' . $this->ocm_meta['type'];
        }
        return $this->link($route, $url);
    }
    public function getModificationURL() {
        $url = '';
        if (VERSION >= '3.0.0.0'){
            $route = 'marketplace/modification';
        } else {
            $route = 'extension/modification';
        }
        return $this->link($route, $url);
    }
    public function getLangImage($languages) {
        $dir = VERSION >= '2.2.0.0' ? 'language/' : 'view/image/flags/';
        foreach($languages as $index => $language) {
            $languages[$index]['image'] = $dir . (VERSION >= '2.2.0.0' ? $language['code'].'/'.$language['code'].'.png' : $language['image']);
            if (defined('_JEXEC') && VERSION >= '3.0.0.0') { //joomla fix
                $oc_url = $this->request->server['HTTPS'] ? HTTPS_IMAGE : HTTP_IMAGE;
                $oc_url = str_replace('/image/', '/admin/', $oc_url);
                $languages[$index]['image'] = $oc_url . $languages[$index]['image'];
            }
        }
        return $languages;
    }
}