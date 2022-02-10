<?php
namespace OCM\Traits\Front;
trait Validator {
    private function _validateProduct($method_products, $cart_products, $rule_type) {
        $status = true;
        $resultant_data = array_intersect($method_products, $cart_products);
        if ($rule_type == 2) {
             if (count($resultant_data) != count($method_products)) {
                $status = false; 
             }
        }
        if ($rule_type == 3) {
            if (!$resultant_data) {
                $status = false; 
            }
        }
        if ($rule_type == 4) {
            if (count($resultant_data) != count($method_products) || count($resultant_data) != count($cart_products)) {
                $status = false; 
            }
        }
        if ($rule_type == 5) {
            if ($resultant_data) {
                $status = false; 
            }
        }
        if ($rule_type == 6) {
            if (!$resultant_data || count($resultant_data) != count($cart_products)) {
                $status = false; 
            }
        }
        if ($rule_type == 7) {
            if ($resultant_data && count($resultant_data) == count($cart_products)) {
                $status = false; 
            }
        }
        return $status;
    }
    private function _validateDimension($dimension, $cart_products, $rule_type) {
        $is_valid = true;
        foreach ($cart_products as $product) {
            if ($product['width_self'] > $dimension['width'] || $product['height_self'] > $dimension['height'] || $product['length_self'] > $dimension['length'] || $product['weight_self'] > $dimension['weight']) {
                $is_valid = false;
                break;
            }
        }
        return $is_valid;
    }
    private function _validatePostal($postcodes, $deliver_postal, $rule_type) {
        $status = false;
        foreach($postcodes as $postcode) {
            if (!$postcode) continue;
            /* regex ifrst otherwise dash in rex can interfere range*/
            if (substr($postcode,0,1) == '/') {
                if (preg_match($postcode, $deliver_postal)) {
                    $status = true; 
                    break;
                }
            }
            /* Postal Range - Only Numeric */
            elseif (strpos($postcode,'-') !== false && substr_count($postcode,'-') == 1) {
                list($start_postal,$end_postal) = explode('-',$postcode); 
                $start_postal = (int)$start_postal;
                $end_postal = (int)$end_postal;
                if ( $deliver_postal >= $start_postal &&  $deliver_postal <= $end_postal) {
                    $status = true;
                }
            }
           /* Range postal code with prefix*/
            elseif (strpos($postcode,'-') !== false && substr_count($postcode,'-') == 2) {
                list($prefix,$start_postal,$end_postal) = explode('-',$postcode);
                $start_postal = (int)$start_postal;
                $end_postal = (int)$end_postal;
                if ($start_postal <= $end_postal) {
                    for($i = $start_postal;$i <= $end_postal; $i++) {
                        if (preg_match('/^'.str_replace(array('\*','\?'),array('(.*?)','[a-zA-Z0-9]'),preg_quote($prefix.$i)).'$/i',$deliver_postal)) {
                            $status = true; 
                            break; 
                        }
                    }
                }
            }
            /* Range postal code with prefix and sufiix*/
            elseif (strpos($postcode,'-') !== false && substr_count($postcode,'-') == 3) {
                list($prefix,$start_postal,$end_postal,$sufiix) = explode('-',$postcode); 
                $start_postal = (int)$start_postal;
                $end_postal = (int)$end_postal;
                if ($start_postal <= $end_postal) {
                    for($i = $start_postal; $i <= $end_postal; $i++) {
                        if (preg_match('/^'.str_replace(array('\*','\?'),array('(.*?)','[a-zA-Z0-9]'),preg_quote($prefix.$i.$sufiix)).'$/i',$deliver_postal)) {
                            $status = true;  
                            break;
                        }
                    }
                }
            }
            /* wildcards use code*/
            elseif (strpos($postcode,'*') !== false || strpos($postcode,'?') !== false) {
                if (preg_match('/^'.str_replace(array('\*','\?'),array('(.*?)','[a-zA-Z0-9]'),preg_quote($postcode)).'$/i',$deliver_postal)) {
                    $status = true;
                    break;
                }
            }
            /* Simple equality check */
            else {
                if ($deliver_postal == strtolower($postcode)) {
                    $status = true;
                    break;
                } 
            }
        }
        $rule_type = ($rule_type == 'inclusive') ? true : false;
        return ($status === $rule_type);
    }
}