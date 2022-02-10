<?php
namespace OCM\Traits\Back\Controller;
trait Curl {
    private function curlTab($curl, $placeholders = '', $test = true, $debug = false) {
        $return = '';
        $curl_tabs = array(
            'tab-curl-setting' => 'Setting',
            'tab-curl-header'  => 'Headers',
            'tab-curl-body'    => 'Body',
            'tab-curl-auth'    => 'Authentication'
        );
        if ($test) {
            $curl_tabs['tab-curl-test'] = 'Test SMS';
        }
        $fields = array('header', 'body');
        foreach ($fields as $field) {
            if (!isset($curl[$field])) {
                $curl[$field] = array();
            }
        }
        $return .= '<div class="curl-builder">';
        $return .= $this->ocm->misc->getTabs('curl-tab', $curl_tabs);
        $return .= '<div class="tab-content">';

        $return .= '<div class="tab-pane active" id="tab-curl-setting">';
        $return .= $this->ocm->form->get('input', array('name' => 'curl[url]', 'title' => 'Request URL', 'preset' => $curl['url']));
        $post_options = $this->ocm->form->getOptions(array(
            'JSON' => 'JSON',
            'POST' => 'POST',
            'GET'  => 'GET'
        ), 'none');
        $return .= $this->ocm->form->get('select', array('name' => 'curl[method]', 'title' => 'Method Type', 'options' => $post_options, 'preset' => $curl['method']));

        if ($debug) {
            $debug_options = $this->ocm->form->getOptions(array(
                '0'  => 'Disabled',
                '1'  => 'Enabled'
            ), 'none');
            $return .= $this->ocm->form->get('select', array('name' => 'curl[debug]', 'title' => 'Debug Log', 'options' => $debug_options, 'preset' => $curl['debug']));
        }
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="tab-curl-header">';
        $return .= $this->getCurlParams('header', $curl['header']);
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="tab-curl-body">';
        $return .= $placeholders;
        $return .= $this->getCurlParams('body', $curl['body']);
        $return .= '</div>'; 

        $return .= '<div class="tab-pane" id="tab-curl-auth">';
        $return .= $this->ocm->misc->getHelpTag('Authentication headers will be sent automatically if you provide.');
        $type_options = $this->ocm->form->getOptions(array(
            'none'    => 'None',
            'basic'   => 'Basic',
            'bearer'  => 'Bearer Token',
        ), 'none');
        $return .= $this->ocm->form->get('select', array('name' => 'curl[auth][type]', 'title' => 'Auth Type', 'options' => $type_options, 'preset' => $curl['auth']['type']));

        $visible = $curl['auth']['type'] == 'basic';
        $return .= $this->ocm->form->get('input', array('name' => 'curl[auth][user]', 'title' => 'Username', 'preset' => $curl['auth']['user'], 'visible' => $visible, 'class' => 'ocm-hide curl_auth_type basic'));
        $return .= $this->ocm->form->get('input', array('name' => 'curl[auth][password]', 'title' => 'Password', 'preset' => $curl['auth']['password'], 'visible' => $visible, 'class' => 'ocm-hide curl_auth_type basic'));

        $visible = $curl['auth']['type'] == 'bearer';
        $return .= $this->ocm->form->get('input', array('name' => 'curl[auth][token]', 'title' => 'Token', 'preset' => $curl['auth']['token'], 'visible' => $visible, 'class' => 'ocm-hide curl_auth_type bearer'));
        $return .= '</div>';

        if ($test) {
            $return .= '<div class="tab-pane" id="tab-curl-test">';
            $return .= $this->ocm->misc->getHelpTag('To test your SMS API, please enter a phone number and click the button `Send SMS`');
            $return .= '<div id="curl-debug"></div>';
            $return .= $this->ocm->form->get('input', array('name' => 'sms_phone', 'title' => 'Phone Number'));
            $return .= $this->ocm->form->get('textarea', array('name' => 'sms_content', 'title' => 'Message', 'preset' => 'This is a test SMS.'));
            $return .= '<div class="curl-btns">'. $this->ocm->misc->getButton(array('type' => 'primary', 'title' => 'Send SMS', 'class' => 'btn-test-curl', 'icon' => 'fa-envelope fa-envelope-open')) .'</div>';
            $return .= '</div>';
        }
        $return .= '</div>';
        $return .= '</div>';
        return $return;
    }
    private function getCurlParams($type, $params) {
        $return = '';
        $table_body = '';
        foreach ($params as $index => $param) {
            $table_body .= '<tr rel="' . $index .'">' 
                            .'<td class="text-left"><input size="15" type="text" class="form-control" name="curl[' . $type . ']['.$index.'][name]" value="' . $param['name'] . '" /></td>'
                            .'<td class="text-left"><input size="15" type="text" class="form-control" name="curl[' . $type . ']['.$index.'][value]" value="' . $param['value'] . '" /></td>'
                            .'<td class="text-right"><a class="btn btn-sm btn-danger ocm-row-remove"><i class="fa fas fa-trash fa-trash-alt"></i></a></td>'
                        .'</tr>';
        }
        if (!$params) $table_body .= '<tr class="no-row"><td colspan="3">No params are added.</td></tr>';

        $table_headings = array(
            array(
                'title'  => 'Name'
            ),
            array(
                'title'  => 'Value'
            ),
            array(
                'title'  => 'Action'
            )
        );
        $table_footer = '<tfoot>
                           <td colspan="7" class="text-right">&nbsp;';
        $table_footer .= $this->ocm->misc->getButton(array('type' => 'primary', 'title' => 'Add', 'class' => 'add-' . $type .'-row ' . $type, 'icon' => 'fa-plus-circle'));
        $table_footer .= '</tr>
                        </tfoot>';
        $return .= $this->ocm->misc->getTableSkeleton($table_headings, $table_body, $table_footer);
        return $return;
    }
    private function getDefaultCurlParams() {
        return array(
            'url'    => '',
            'method' => 'JSON',
            'debug'  => false,
            'header' => array(),
            'body'   => array(),
            'auth'   => array(
                'type'      => 'none',
                'user'      => '',
                'password'  => '',
                'token'     =>  ''
            )
        );
    }
}