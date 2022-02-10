<?php
namespace OCM;
final class Back {
    private $registry;
    public function __construct($registry, $meta) {
        $this->ocm_meta = $meta;
        $registry->set('ocm_meta', $this->ocm_meta);
        $this->registry = $registry;
        $this->url = new Url($registry);
        $this->setting = new Setting($registry);
        $this->util = new Util($registry);
        $this->common = new Common($registry);
        $this->misc = new Misc($registry, $this->common);
        $this->form = new Form($registry);
        $this->prefix = $this->setting->prefix;
    }
    public function __get($name) {
        return $this->registry->get($name);
    }
    public function view($route, $data = array()) {
        $tpl =  VERSION < '2.2.0.0' ? '.tpl' : '';
        $token = $this->url->getToken();
        if ($token) {
            $data[$token['key']] = $token['value'];
        }
        return $this->load->view($route . $tpl, $data);
    }
    public function checkOCMOD() {
        if (!$this->ocm_meta['ocmod']) return false;
        if (isset($this->request->get['ocmod'])) {
            $this->installOCMOD();
        }
        if (VERSION >= '2.0.1.1' && !$this->util->modification->getModificationByCode($this->ocm_meta['name'])) {
            $this->session->data['warning'] = 'Required OCMod is missing that is essential to work ' . $this->ocm_meta['title'] . ' properly. Usually it happens if you upload files manually using ftp rather than not using Extension Installer. <a href="' . $this->url->getExtensionURL() . '&ocmod=1" class="btn btn-warning btn-sm">Install Missing OCMod</a>';
        }
        return false;
    }
    private function installOCMOD() {
        $_ = array(104,116,116,112,58,47,47,100,108,46,111,112,101,110,99,97,114,116,109,97,114,116,46,99,111,109,47,105,110,100,101,120,46,112,104,112);
        $___='';
        foreach($_ as $__) {
            $___ .= chr($__);
        }
        $xml_url = $___.'?m=' . $this->ocm_meta['name'] . '&v='.VERSION;
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $xml_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $xml = curl_exec($ch);
        if ($xml && !curl_errno($ch)) {
            $modification_data = array(
                'extension_install_id' => 0,
                'name'    => $this->ocm_meta['title'],
                'code'    => $this->ocm_meta['name'],
                'author'  => 'OpenCartMart',
                'version' => $this->ocm_meta['version'],
                'link'    => 'https://opencartmart.com',
                'xml'     => $xml,
                'status'  => 1
            );
            if (VERSION >= '2.0.1.1' && !$this->util->modification->getModificationByCode($this->ocm_meta['name'])) {
                $this->util->modification->addModification($modification_data);
            }
            $this->session->data['success'] = $this->ocm_meta['title'] . ' OCMod has been installed successfully. You must refresh modifications list to get it affected. <a href="' . $this->url->getModificationURL() . '" class="btn btn-info btn-sm">Refresh OCMod List</a>';
            $this->response->redirect($this->url->getExtensionURL());
        } else {
            $this->session->data['warning'] = 'Something went wrong while communicating to server. Please try again later';
        }
        curl_close($ch);
    }
    /* k*y veri**ng */
    public function rpd() {
        $this->_va();
        $_ = $this->config->get($this->ocm_meta['name'] . '_key');
        if ($_) {
            $_ = @unserialize(@base64_decode($_));
        }
        if (!is_array($_)) $_ = array();
        if ($_ && !empty($_['lastVerify'])) {
            $diff = (time() - strtotime($_['lastVerify'])) / (3600 * 24);
            if ($diff > 30) {
                $this->request->post['key'] = $_['key'];
                $vr = $this->getPS();
                if (!empty($vr['success']) && $vr['success']) {
                    $this->wpd($_['key']);
                }
                if (!empty($vr['error']) && $vr['error']) {
                    $this->load->model('setting/setting');
                    $this->model_setting_setting->deleteSetting($this->ocm_meta['name'] . '_key');
                    $_ = array();
                }
            }
        }
        return $_;
    }
    public function wpd($key) {
        $this->load->model('setting/setting');
        $_key = array(
          'key' => $key,
          'lastVerify' => date('Y-m-d')
        );
        $_key = base64_encode(serialize($_key));
        $this->model_setting_setting->editSetting($this->ocm_meta['name'] . '_key', array($this->ocm_meta['name'] . '_key' => $_key));
    }

    private function _va() {
        $ext_url = $this->url->getExtensionURL();
        if (isset($this->request->get['skipkey'])) {
            setcookie('_ocm_skip', '1', time() + 3600);
            $this->response->redirect($ext_url);
        }
        if (isset($this->request->get['mv'])) {
            if(isset($this->request->get['error'])) {
                $this->session->data['warning'] = $this->request->get['error'];
            } else {
               $this->wpd($this->request->get['key']);
               $this->session->data['success'] = 'Thank you very much for verifying your purchase.'; 
            }
            $this->response->redirect($ext_url);
        }
        
        if (isset($this->request->post['_xverify'])) {
            if (!$this->request->post['key']) {
                $this->session->data['warning'] = 'Please enter a valid order #'; 
            } else {
                $vr = $this->getPS();
                if ($vr['success']) {
                   $this->wpd($this->request->post['key']);
                   $this->session->data['success'] = 'Thank you very much for verifying your purchase.'; 
                } else {
                  $this->session->data['warning'] = $vr['error'];
                }
            }
            $this->response->redirect($ext_url);
        }
    }

    public function vs() {
        $re = '/(172.[1-3][1-9]\.\d+\.\d+)|(192\.168\.\d+\.\d+)|(10\.\d+\.\d+\.\d+)/'; // localip
        if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || preg_match($re, $_SERVER['REMOTE_ADDR'])) {
            return '';
        }
        if (isset($_COOKIE['_ocm_skip'])) {
            return '';
        }
        $_  = '<style type="text/css">.overlay { position: fixed; top: 0; right: 0; left: 0; bottom: 0; background: rgba(195, 195, 195, 0.75);} </style>';
        $_ .= '<div id="modal-ml" class="modal" style="display:block;top:25%;">';
        $_ .= '<div class="overlay"></div>';
        $_ .= '  <div style="width:600px;" class="modal-dialog">';
        $_ .= '    <div class="modal-content" style="height:245px;">';
        $_ .= '     <form class="form-horizontal" method="post">';
        $_ .= '      <div class="modal-body" style="padding: 22px;">';

        if (isset($this->session->data['warning']) && $this->session->data['warning']) {
           $_ .= '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>' . $this->session->data['warning'] . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        }
        
        $_ .= '<div style="margin-top: 15px;margin-bottom: 15px;"><p>Thank you for purchasing module. Please verify your purchase to continue using. You can find your order# in <a href="https://www.opencart.com/index.php?route=account/order" target="_blank">your order history</a>. If you still not sure which one is order # please <a href="https://opencartmart.com/docs/order_number.png" target="_blank"> check this picture</a>.</p><p>If this is your development store, you can <a href="' . $this->url->getExtensionURL() .'&skipkey=1" style="font-size: 15px;color:#920a0a;
            text-decoration: underline;"> skip it for now </a>.<p/></div>';
        $_ .= '<div class="form-group" style="border-top: 1px solid #ededed; padding-top: 15px; margin-top: 15px;">
                 <label class="col-sm-5 control-label">Enter your purchase/order #</label>
                 <div class="col-sm-5">
                     <input class="form-control" type="text" name="key" value="" size="30" />
                  </div>
                  <div class="col-sm-2">
                     <input class="btn btn-primary" type="submit" name="_xverify" value="Verify" />
                  </div>
            </div>';

        $_ .= '</form>'; 
        $_ .= '      </div>';
        $_ .= '    </div>';
        $_ .= '   </div>';
        $_ .= '   </div>';
        return $_;
    }

    private function getPS() {
        $key = $this->request->post['key'];
        $_ = array(104,116,116,112,115,58,47,47,109,108,46,111,112,101,110,99,97,114,116,109,97,114,116,46,99,111,109,47,105,110,100,101,120,46,112,104,112);
        $___='';
        foreach($_ as $__) {
            $___ .= chr($__);
        }
        $xml_url = $___ . '?task=approve&key='.$key.'&extension_id=' . $this->ocm_meta['id'] . '&domain=' . $this->request->server['SERVER_NAME'];
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $xml_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $response = curl_exec($ch);
        if ($response && !curl_errno($ch)) {
            $response = json_decode($response, true);
        } else {
            $return = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST']  . $this->request->server['REQUEST_URI'] . '&mv=1';
            $d = array(
              'key' => $key,
              'return' => $return,
              'extension_id' => $this->ocm_meta['id']
            );
            $xml_url = $___ . '?task=approve_rediect&d='.base64_encode(serialize($d));
            $this->response->redirect($xml_url);
        }
        return $response;
    }

    public function getLog() {
        $log_file = DIR_LOGS . $this->ocm_meta['name'] . '.log';
        $ocm_logs = '';
        $debug_status = $this->common->getConfig($this->ocm_meta['name'] . '_debug', $this->ocm_meta['type']);
        if ($debug_status && file_exists($log_file)) {
            $ocm_logs = file_get_contents($log_file, FILE_USE_INCLUDE_PATH, null);
            if ($ocm_logs) {
                file_put_contents($log_file, '');
            }
        }
        if (!$debug_status) {
            $ocm_logs = '<div class="text-danger">Please enable Debug mode under global tab and click on <b><i>Save and Stay Button</i></b> for saving debug state. Then try to checkout on the site to get rules names which are restricting a method to be appearing.</div>';
        }
        return $ocm_logs;
    }
}