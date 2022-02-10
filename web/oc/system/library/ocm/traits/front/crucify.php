<?php
namespace OCM\Traits\Front;
trait Crucify {
    private function _crucify($rules, $data, $product_and_or = false, $ingore_product_rule = false, $only_address_rule = false) {
        $status = true;
        $product_status = false;
        $product_query = array();
        $product_rules = array();
        $debugging = array();
        foreach ($rules as $name => $rule) {
            if ($only_address_rule && !$rule['address_rule']) {
                continue;
            }
            if ($ingore_product_rule && $rule['product_rule']) {
                if (!empty($rule['product_query'])) {
                    $product_query[] = $rule['product_query'];
                }
                continue;
            }
            $_debug_hint = $rule['compare_with'] !== 'products' ? $data[$rule['compare_with']] : '';
            $debug_value = is_array($_debug_hint) ? implode(',', $_debug_hint) : $_debug_hint;
            if ($rule['type'] == 'equal') {
                if ($data[$rule['compare_with']] === (boolean)$rule['false_value']) {
                    $debugging[$name] = $name . '('.$debug_value.')';
                    $status = false;
                    break;
                }
            }
            if ($rule['type'] == 'in_array') {
                if (in_array($data[$rule['compare_with']], $rule['value']) === (boolean)$rule['false_value']) {
                    $debugging[$name] = $name . '('.$debug_value.')';
                    $status = false;
                    break;
                }
            }
            if ($rule['type'] == 'intersect') {
                if ((boolean)$this->array_intersect_faster($data[$rule['compare_with']], $rule['value']) === (boolean)$rule['false_value']) {
                    $debugging[$name] = $name . '('.$debug_value.')';
                    $status = false;
                    break;
                }
            }
            if ($rule['type'] == 'in_between') {
                if ($data[$rule['compare_with']] < $rule['start'] ||  $data[$rule['compare_with']] > $rule['end']) {
                    $debugging[$name] = $name . '('.$debug_value.')';
                    $status = false;
                    break;
                }
            }
            if ($rule['type'] == 'in_array_not_equal') {
                if ($data[$rule['not_equal_with']] == $rule['not_equal_value'] && in_array($data[$rule['compare_with']], $rule['value']) === (boolean)$rule['false_value']) {
                    $debugging[$name] = $name . '('.$debug_value.')';
                    $status = false;
                    break;
                }
            }
            if ($rule['type'] == 'function') {
                if (!property_exists($this, $rule['func']) && !method_exists($this, $rule['func'])) {
                    continue;
                }
                $_return = $this->{$rule['func']}($rule['value'], $data[$rule['compare_with']], $rule['rule_type']);
                if ($rule['product_rule'] && $product_and_or) {
                    $product_status |= $_return;
                    $product_rules[$name] = $_return;
                } else {
                    if ($_return === (boolean)$rule['false_value']) {
                        $debugging[$name] = $name . '('.$debug_value.')';
                        $status = false;
                        break;
                    }
                }
            }
        }
        /* check or_mode for product rules */
        if ($product_and_or && $product_rules && !$product_status) {
            $status = false;
            foreach ($product_rules as $key => $value) {
                if (!$value) {
                    $debugging[$key] = $key;
                }
            }
        }

        return array(
            'status'        => $status,
            'product_query' => $product_query,
            'debugging'     => $debugging
        );
    }
}