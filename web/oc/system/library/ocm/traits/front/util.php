<?php
namespace OCM\Traits\Front;
trait Util {
    private function array_intersect_faster($array1, $array2) {
        $is_found = false;
        foreach ($array1 as $key) {
           if (in_array($key, $array2)) {
                $is_found = true;
                break;
            }
        }
        return $is_found;
    }
    private function tiniestCalculator($num1, $num2, $operator) {
        if ($operator == '+') return $num1 + $num2;
        if ($operator == '-') return $num1 - $num2;
        if ($operator == '*') return $num1 * $num2;
        if ($operator == '/') {
           if (!$num2) $num2 = 1;
           return $num1 / $num2 ;
        }
    }
    private function fixRounding($cart_data) {
        $keys = array('sub', 'total', 'grand', 'grand_shipping', 'grand_wtax');
        $currency_code = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        $place = (int)$this->currency->getDecimalPlace($currency_code);
        if (!$place) $place = 2;
        foreach ($keys as $key) {
            if (isset($cart_data[$key]) && $cart_data[$key]) {
                $cart_data[$key] = round($cart_data[$key], $place);
            }
        }
        return $cart_data;
    }
}