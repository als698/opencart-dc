<?php
namespace OCM\Traits\Front;
trait Shortcode {
    private function _xformRender($shortcode) {
        $return = false;
        if ($this->ocm->getConfig('xform_status', 'module')) {
            $xform = new \Xform($this->registry);
            $formId = (int)$shortcode['text'];
            $order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0 ;
            $xform_order_id = isset($this->session->data['xform_order_id']) ? $this->session->data['xform_order_id'] : 0 ;
            $preset = array();
            if ($order_id) {
                $recordId = $xform->getRecordByOrderIds($formId, array($order_id, $xform_order_id));
                if ($recordId) {
                    $record = $xform->getRecordById($recordId);
                    $preset = $record ? $record['edit'] : array();
                }
            }
            $output = $xform->renderForm($formId, $preset, false, true);
            $output .= '<script type="text/javascript">if(window.xform) window.xform.warmUp();</script>';
            $return = array(
                'output' => $output,
                'apply'  => false
            );
        }
        return $return;
    }
    private function _dateRender($shortcode) {
        $placeholder = array('January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sun','Mon','Tue','Wed','Thu','Fri','Sat');
        $replacer = array();
        $replacer = array_merge($replacer, explode('_', $this->language->get('ocm_months')));
        $replacer = array_merge($replacer, explode('_', $this->language->get('ocm_months_short')));
        $replacer = array_merge($replacer, explode('_', $this->language->get('ocm_weekdays')));
        $replacer = array_merge($replacer, explode('_', $this->language->get('ocm_weekdays_short')));
        
        $default = array(
            'format'    => 'd M, Y',
            'offday'    => '',
            'mode'      => 'next',
            'offset'    => 0,
            'same_day'  => false
        );
        $shortcode['attr'] = (array)$shortcode['attr'];
        $param = array_merge($default, $shortcode['attr']);
        $param['offday'] = explode(',', $param['offday']);
        $param['same_day'] = $param['same_day'] === true || $param['same_day'] === 'true';
        $time = time();
        $w = date('w', $time);
        $days = is_numeric($shortcode['text']) ? (int)$shortcode['text'] : $shortcode['text'];
        // if today is NOT in offday list, decrease days by 1
        if (!in_array($w, $param['offday']) && $param['same_day'] && is_numeric($days)) {
            if (isset($param['same_day_limit']) && (int)$param['same_day_limit'] && (int)$param['same_day_limit'] >= date('G', $time)) {
                $days--;
            }
        }
        $skipOffDay = function($time) use ($param) {
            $sign = $param['mode'] === 'next' ? '+' : '-';
            while (true) {
                $time = strtotime($sign . "1 day", $time);
                $w = date('w', $time);
                if (!in_array($w, $param['offday'])) {
                    break;
                }
            }
            return $time;
        };
        $nextDay = function($time, $mode) {
            $sign = $mode === 'next' ? '+' : '-';
            return strtotime($sign . "1 day", $time);
        };
        if (is_numeric($days)) {
            for($i=1; $i <= $days; $i++) {
                $_time = $skipOffDay($time);
                if ($_time !== $time) {
                    $time = $_time;
                    continue;
                }
                $time = $nextDay($time, $param['mode']);
            }
        } else {
            $now = time();
            $star = strpos($days, '*') !== false;
            if ($star) {
                $days = str_replace('*', 'this', $shortcode['text']);
            }
            $time = strtotime($days);
            if ($star && $time < $now) {
                $days = str_replace('*', 'next', $shortcode['text']);
                $time = strtotime($days);
            }
            if ((float)$param['offset']) {
                $offset_secs = 3600 * (float)$param['offset'];
                $diff = $time - $now;

                if ($offset_secs >= $diff) {
                    $time = strtotime($days, $time);
                }
            }
        }
        return array(
            'output' => str_replace($placeholder, $replacer, date($param['format'], $time)),
            'apply'  => true
        );
    }
    private function _eqRender($shortcode, $replacement) {
         $default = array(
            'format'  => false,
            'round'   => false
        );
        $shortcode['attr'] = (array)$shortcode['attr'];
        $param = array_merge($default, $shortcode['attr']);
        $param['format'] = $param['format'] === true || $param['format'] === 'true';
        $param['round'] = $param['round'] === true || $param['round'] === 'true';
        
        $equation = $shortcode['text'];
        $output = '';
        if ($equation) {
            $equation_result = $this->getEquationValue($equation, $replacement['placeholder'], $replacement['replacer']);
            $output = $equation_result['value'];
        }
        if ($output && $param['round']) {
            $output = round($output);
        }
        if ($output !== '' && $param['format']) {
            $currency_code = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
            $output = $this->currency->format($output, $currency_code);
        }
        return array(
            'output' => $output,
            'apply'  => true
        );
    }
}