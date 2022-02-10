<?php
namespace OCM\Traits\Front;
trait Grouping {
    private function findGroup($group_method, $group_type, $group_limit, $group_name ='') {
        $language_id = $this->config->get('config_language_id');
        $currency_code = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        $return = array();
        $replacer_name = array();
        $replacer_price = array();
        if ($group_type == 'lowest') {
            $lowest = array();
            $lowest_sort = array();
            foreach($group_method as $group_id=>$method) {
                $lowest_sort[$group_id] = $method['cost'];
                $lowest[$group_id] = $method;
                array_push($replacer_name, $method['title']);
                array_push($replacer_price, $this->currency->format((float)$method['cost'], $currency_code, false, true));
            }
            array_multisort($lowest_sort, SORT_ASC, $lowest);
            for($i=0;$i<$group_limit;$i++) {
                if (isset($lowest[$i]) && is_array($lowest[$i]) && $lowest[$i]) {   
                    $return[$lowest[$i]['xkey']] = $lowest[$i]; 
                }
            }
        }
        if ($group_type == 'highest') {
            $highest = array();
            $highest_sort = array();
            foreach($group_method as $group_id => $method) {
                $highest_sort[$group_id] = $method['cost'];
                $highest[$group_id] = $method;
                array_push($replacer_name, $method['title']);
                array_push($replacer_price, $this->currency->format((float)$method['cost'], $currency_code, false, true));
            }
            array_multisort($highest_sort, SORT_DESC, $highest);
            for($i=0; $i<$group_limit; $i++) {
                if (isset($highest[$i]) && is_array($highest[$i]) && $highest[$i]) {    
                    $return[$highest[$i]['xkey']] = $highest[$i]; 
                }
            } 
        } 
        if ($group_type == 'average') {
            $sum = 0;
            foreach($group_method as $group_id => $method) {
                $sum += $method['cost'];
                array_push($replacer_name, $method['title']);
                array_push($replacer_price, $this->currency->format((float)$method['cost'], $currency_code, false, true));
            }
            if (count($group_method) > 1) {
                $group_method[0]['cost']=$sum/count($group_method); 
                $group_method[0]['text']=$this->currency->format($this->tax->calculate($group_method[0]['cost'], $group_method[0]['tax_class_id'], $this->config->get('config_tax')),$currency_code);
            }
            $return[$group_method[0]['xkey']]= $group_method[0];
        } 
        if ($group_type == 'sum') {
            $sum = 0;
            foreach($group_method as $group_id => $method) {
                $sum += $method['cost'];
                array_push($replacer_name, $method['title']);
                array_push($replacer_price, $this->currency->format((float)$method['cost'], $currency_code, false, true));
            }
            $group_method[0]['cost'] = $sum;
            $group_method[0]['text'] = $this->currency->format($this->tax->calculate($group_method[0]['cost'], $group_method[0]['tax_class_id'], $this->config->get('config_tax')),$currency_code);
            $return[$group_method[0]['xkey']]= $group_method[0];  
        }
        if ($group_name) {
            $replacer_name_price = array();
            foreach ($replacer_name as $key => $value) {
                $replacer_name_price[] = $value .'-' . $replacer_price[$key];
            }
            $keywords = array('@$','@','$');
            $replacer = array();
            $replacer[] = str_replace('$', '____', implode('+', $replacer_name_price)); //backup existng $ and replace later so don't get mengled with placeholder $
            $replacer[] = implode('+', $replacer_name);
            $replacer[] = str_replace('$', '____', implode('+', $replacer_price));
            $group_name = str_replace($keywords, $replacer, $group_name);
            $group_name = str_replace('____', '$', $group_name);
        }
        if (count($return) == 1) {
            foreach($return as $key => $method) {
                // update mask if it was avail
                if (!empty($method['mask'])) {
                    $return[$key]['text'] = $method['mask'];
                }
                if ($group_name) {
                    $return[$key]['title'] = $group_name;
                }
            }
        }
        return $return;
    }
}