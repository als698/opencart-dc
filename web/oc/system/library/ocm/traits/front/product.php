<?php
namespace OCM\Traits\Front;
trait Product {
    private function getProductProfile($cart_products, $xmeta) {
        $cart_categories = array();
        $cart_product_ids = array();
        $cart_manufacturers = array();
        $cart_options = array();
        $cart_attributes = array();
        $cart_locations = array();
        $cart_stock_status = array();
        $cart_volume = 0;
        $cart_quantity = 0;
        $cart_weight = 0;
        $cart_sub = 0;
        $cart_total = 0;
        $cart_ean = 0;
        $cart_jan = 0;
        $cart_special = 0;
        $cart_special_tax = 0;
        $cart_lowest = 0;
        $cart_highest = -1;
        $lowest_qnty = 0;
        $highest_qnty = 0;
        $has_shipping = false;
        $out_of_stock = 0;
        $cart_original = 0;
        $non_shippable = 0;
        $tax_data = array();
        $per_manufacturer = array();
        $default = array(
            'tax_class_id'     => 0,
            'weight_class_id'  => 0,
            'length_class_id'  => 0,
            'manufacturer_id'  => 0
        );
        $xdiscounted = $this->ocmprice && method_exists($this->ocmprice, 'getXDiscountedProducts') ? $this->ocmprice->getXDiscountedProducts(false) : array();
        foreach($cart_products as $i => &$product) {
            $product = array_merge($default, $product);
            $skip = false;
            $options = array();
            if (!empty($product['option']) && is_array($product['option'])) {
                foreach($product['option'] as $option) {
                    if (!empty($xmeta['ignore']) && !empty($option['value']) && strpos($option['value'], $xmeta['ignore']) !== false) {
                        $skip = true;
                    }
                    if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox') {
                        $options[] = $option['option_value_id'];
                    }
                }
            }
            if ($skip) {
                unset($cart_products[$i]);
                continue;
            }
            $product['ocm_special'] = isset($product['ocm_special']) ? $product['ocm_special'] : false;
            if (!$product['ocm_special'] && array_key_exists($product['product_id'], $xdiscounted)) {
                $product['ocm_special'] = true;
                if ($xdiscounted[$product['product_id']]['on_total']) {
                    $product['price'] -= $xdiscounted[$product['product_id']]['discount'];
                    $product['total'] -= $xdiscounted[$product['product_id']]['amount'];
                }
            }

            $product['option'] = $options; //store for future use 
            $cart_options = array_merge($cart_options, $options);
            if ($product['shipping']) {
                $has_shipping = true;
            } else {
                $non_shippable += $product['total'];
            }
            if (!$product['stock']) {
                $out_of_stock += $product['quantity'];
            }
            if ($cart_lowest > $product['price'] || !$cart_lowest) {
                $cart_lowest = $product['price'];
                $lowest_qnty = $product['quantity'];
            }
            if ($cart_highest < $product['price']) {
                $cart_highest = $product['price'];
                $highest_qnty = $product['quantity'];
            }
            $cart_product_ids[] = $product['product_id']; 
            $product['product'] = $product['product_id']; /* Use same key for all places */
            $product['stock'] = $product['stock_self'] = (int)$product['stock'];
            $price_with_tax = $this->tax->calculate($product['price'], $product['tax_class_id']);
            $total_with_tax = $price_with_tax * $product['quantity'];

            $weight_class_id = $product['weight_class_id'] ? $product['weight_class_id'] : $this->config->get('config_weight_class_id');
            $weight = $product['shipping'] ? $this->weight->convert($product['weight'], $weight_class_id, $this->config->get('config_weight_class_id')) : 0;

            $product['category'] = array();

            if (!empty($xmeta['category_query'])) {
                $product_categories = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product['product_id'] . "'")->rows;
                if ($product_categories) {
                    foreach($product_categories as $category) {
                        $cart_categories[]=$category['category_id'];  
                        $product['category'][]=$category['category_id']; //store for future use 
                    } 
                }
            }
            $product['attribute'] = array();
            if (!empty($xmeta['attribute_query'])) {
                $product_attributes = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product['product_id'] . "'")->rows;
                if ($product_attributes) {
                    foreach($product_attributes as $attribute) {
                        $cart_attributes[] = $attribute['attribute_id'];  
                        $product['attribute'][] = $attribute['attribute_id']; //store for future use 
                    } 
                }
            }
            $length_class_id    = $product['length_class_id'] ? $product['length_class_id'] : $this->config->get('config_length_class_id');
            $length             = $this->length->convert($product['length'], $length_class_id, $this->config->get('config_length_class_id'));
            $width              = $this->length->convert($product['width'], $length_class_id, $this->config->get('config_length_class_id'));
            $height             = $this->length->convert($product['height'], $length_class_id, $this->config->get('config_length_class_id'));

            $volume             = ($width * $height * $length);
            $cart_volume        += ($volume * $product['quantity']);
            $cart_quantity      += $product['quantity'];
            $cart_sub           += $product['total'];
            $cart_total         += $total_with_tax;
            $cart_weight        += $weight;
            $cart_special       += $product['ocm_special'] ? $product['total'] : 0;
            $cart_special_tax   += $product['ocm_special'] ? $total_with_tax : 0;

            $product['length']          = $product['length'] * $product['quantity'];
            $product['width']           = $product['width'] * $product['quantity'];
            $product['height']          = $product['height'] * $product['quantity'];
            $product['total_with_tax']  = $total_with_tax;
            $product['volume']          = $volume * $product['quantity'];
            $product['weight']          = $weight;
            $product['length_self']     = $length;
            $product['width_self']      = $width;
            $product['height_self']     = $height;
            $product['volume_self']     = $volume; 
            $product['weight_self']     = ($weight / $product['quantity']);
            $product['price_self']      = $product['price'];
            $product['price_self_tax']  = $price_with_tax;
            $product['special_self']    = $product['ocm_special'] ? $product['price'] : 0;

            if (!empty($xmeta['product_query'])) {
                $product_info = $this->db->query("SELECT price, manufacturer_id, location, stock_status_id, jan, ean FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product['product_id'] . "'")->row;
                if ($product_info) {
                    $product['jan'] = (float)$product_info['ean'] * $product['quantity'];
                    $product['ean'] = (float)$product_info['jan'] * $product['quantity'];
                    $cart_ean += $product['jan'];
                    $cart_jan += $product['ean'];
                    if ($product_info['manufacturer_id']) {
                        $cart_manufacturers[] = $product_info['manufacturer_id'];
                        $product['manufacturer'] = $product['manufacturer_id'] = $product_info['manufacturer_id']; //store for future use
                    }
                    $location = trim(strtolower($product_info['location']));
                    if ($location) {
                        $product['location'] = $location; //store for future use
                        $cart_locations[] = $location;
                    }
                    $cart_stock_status[] = $product_info['stock_status_id'];
                    $product['original'] = (float)$product_info['price'];
                    $cart_original += $product['original'] * $product['quantity'];
                }
            }
            //per manufacturer
            if (!isset($per_manufacturer[$product['manufacturer_id']]) && $product['manufacturer_id']) {
                $per_manufacturer[$product['manufacturer_id']] = 0;
            }
            if ($product['manufacturer_id']) {
                $per_manufacturer[$product['manufacturer_id']] += $product['total'];
            }
            /* Tax Data */
            if ($product['tax_class_id']) {
                $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);
                foreach ($tax_rates as $tax_rate) {
                    if (!isset($tax_data[$tax_rate['tax_rate_id']])) $tax_data[$tax_rate['tax_rate_id']] = 0;
                    $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                }
            }
            /* packing information */
            $product['bin'] =array(
                'length' => $product['length_self'] ? $product['length_self'] : 1,
                'width'  => $product['width_self'] ? $product['width_self'] : 1,
                'height' => $product['height_self'] ? $product['height_self'] : 1,
                'volume' => $product['volume_self'] ? $product['volume_self'] : 1,
                'weight' => $product['weight_self'] ? $product['weight_self'] : 0.1,
            );
            $product['bin']['capacity'] = $product['bin']['volume'] * $product['bin']['weight'];
        }
        //add vouchers if available 
        $vouchers = 0;
        if (isset($this->session->data['vouchers']) && is_array($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $cart_sub += $voucher['amount'];
                $cart_total += $voucher['amount'];
                $vouchers += $voucher['amount'];
            }
        }
        $no_product         = count($cart_product_ids); // get cart product with diff option
        $cart_categories    = array_unique($cart_categories);
        $cart_product_ids   = array_unique($cart_product_ids);
        $cart_manufacturers = array_unique($cart_manufacturers);
        $cart_options       = array_unique($cart_options);
        $cart_attributes    = array_unique($cart_attributes);
        $cart_locations     = array_unique($cart_locations);
        //shipping cost
        if (!empty($this->session->data['default']['shipping_method'])) {
            $shipping_method = $this->session->data['default']['shipping_method'];
        } else if (!empty($this->session->data['shipping_method'])) {
            $shipping_method = $this->session->data['shipping_method'];
        } else {
            $shipping_method = array();
        }
        $shipping_cost = 0;
        $shipping_tax  = 0;
        if ($shipping_method) {
            $shipping_cost = (float)$shipping_method['cost'];
            if (!empty($shipping_method['tax_class_id']) && $shipping_cost) {
                $shipping_tax = $this->tax->getTax($shipping_cost, $shipping_method['tax_class_id']);
            }
        }
        $reward = !empty($this->session->data['reward']) ? $this->session->data['reward'] : 0;
        return array(
            'products'       => $cart_products,
            'category'       => $cart_categories,
            'product'        => $cart_product_ids,
            'manufacturer'   => $cart_manufacturers,
            'option'         => $cart_options,
            'attribute'      => $cart_attributes,
            'location'       => $cart_locations,
            'volume'         => $cart_volume,
            'no_package'     => 1,
            'no_block'       => 0,
            'block_asc'      => 0,
            'block_desc'     => 0,
            'no_block_asc'   => 0,
            'no_block_desc'  => 0,
            'no_product'     => $no_product,
            'no_category'    => count($cart_categories),
            'no_manufacturer'=> count($cart_manufacturers),
            'no_location'    => count($cart_locations),
            'quantity'       => $cart_quantity,
            'weight'         => $cart_weight,
            'total'          => $cart_total,
            'sub'            => $cart_sub,
            'vouchers'       => $vouchers,
            'grand'          => $cart_total,
            'grand_shipping' => $cart_total,
            'grand_wtax'     => $cart_total, // update later from module
            'special'        => $cart_special,
            'special_tax'    => $cart_special_tax,
            'sub_special'    => ($cart_sub - $cart_special),
            'total_special'  => ($cart_total - $cart_special_tax),
            'sub_negative'   => $cart_sub,
            'price_self'     => $cart_sub, // let's set an inital value, will update later
            'price_self_tax' => $cart_total, // let's set an initial value, will update later
            'jan'            => $cart_jan,
            'ean'            => $cart_ean,
            'coupon'         => 0,
            'reward'         => $reward,
            'distance'       => 0,
            'dimensional'    => 0,
            'volumetric'     => 0,
            'negative'       => 0,
            'shipping'       => $shipping_cost,
            'shipping_plus'  => ($shipping_cost + $shipping_tax),
            'shipping_tax'   => $shipping_tax,
            'sub_coupon'            => $cart_sub,
            'total_coupon'          => $cart_sub,
            'sub_shipping'          => ($cart_sub + $shipping_cost),
            'total_shipping'        => ($cart_total + $shipping_cost),
            'sub_shipping_plus'     => ($cart_sub + $shipping_cost + $shipping_tax),
            'total_shipping_plus'   => ($cart_total + $shipping_cost + $shipping_tax),
            'per_manufacturer'      => $per_manufacturer,
            'original'              => $cart_original,
            'non_shippable'         => $non_shippable,
            'tax_data'       => $tax_data,
            'stock_status'   => $cart_stock_status,
            'has_shipping'   => $has_shipping,
            'out_of_stock'   => $out_of_stock,
            'multi_category' => true, // remove in future version
            'highest'        => $cart_highest,
            'lowest'         => $cart_lowest,
            'highest_qnty'   => $highest_qnty,
            'lowest_qnty'    => $lowest_qnty,
            'xfeepro'        => array()
        );
    }
    /* If the product has multiple values of the same product, then adjust rules values */
    private function _adjustMultiValues(&$rules, $cart_products) {
        $possible_rules = array('category', 'option', 'attribute');
        $need_to_adjust = array();
        $include = array();
        $exclude = array();
        foreach ($possible_rules as $key) {
            if (isset($rules[$key]) 
                && ($rules[$key]['rule_type'] == 4 || $rules[$key]['rule_type'] == 6 || $rules[$key]['rule_type'] == 7)) {
                $need_to_adjust[$key] = $rules[$key]['value'];
                $include[$key] = array();
                $exclude[$key] = array();
            }
        }
        if ($need_to_adjust) {
            foreach($cart_products as $product) {
                foreach ($need_to_adjust as $key => $value) {
                    if ($this->array_intersect_faster($value, $product[$key])) {
                        $include[$key] = array_merge($include[$key], $product[$key]);
                    } else {
                        $exclude[$key] = array_merge($exclude[$key], $product[$key]);
                    }
                }
            }
            foreach ($need_to_adjust as $key => $rule) {
                $include[$key] = array_unique($include[$key]);
                $include[$key] = array_diff($include[$key], $exclude[$key]);
                if ($include[$key]) {
                    $rules[$key]['_value'] = $rules[$key]['value']; // keep a copy if it requires later
                    $rules[$key]['value'] = $include[$key];
                }
            }
        }
    }
    // if product rules are in OR mode and has several rules of the type 4 or 6, adjust rule's products
    private function _adjustProductsOr(&$rules, $cart_products) {
        $applied = false;
        $count = 0;
        $product_rules = array('category', 'product', 'manufacturer', 'option', 'attribute', 'location');
        foreach ($product_rules as $key) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                if ($rule['rule_type'] == 4 || $rule['rule_type'] == 6) {
                    $_products = array();
                    foreach($cart_products as $product) {
                        $_count_on = ($key == 'category' || $key == 'attribute' || $key == 'option') ? $this->array_intersect_faster($product[$key],$rule['value']) : in_array($product[$key], $rule['value']);
                        if ($_count_on) {
                            $_products[] = $product;
                        }
                    }
                    if ($_products) {
                        foreach ($product_rules as $_key) {
                            if ($key == $_key) continue;
                            if (isset($rules[$_key])) {
                                $_rule = $rules[$_key];
                                if ($_rule['rule_type'] == 4 || $_rule['rule_type'] == 6) {
                                    $applied = true;
                                    $count++;
                                    foreach ($_products as $_product) {
                                        if (!empty($_product[$_key])) {
                                            if ($_key == 'category' || $_key == 'attribute' || $_key == 'option') {
                                                $rules[$_key]['value'] = array_merge($rules[$_key]['value'], $_product[$_key]);
                                            } else {
                                                $rules[$_key]['value'][] = $_product[$_key];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($count == 0) break; // not enough product rules to process
        }
        if ($applied) {
            foreach ($product_rules as $key) {
                if (isset($rules[$key])) {
                    $rule = $rules[$key];
                    if ($rule['rule_type'] == 4 || $rule['rule_type'] == 6) {
                        $rules[$key]['value'] = array_unique($rules[$key]['value']);
                    }
                }
            }
        }
    }
    private function _getApplicableProducts($rules, $cart_data) {
        $_applicable = array(
            'category'     => $cart_data['category'],
            'product'      => $cart_data['product'],
            'manufacturer' => $cart_data['manufacturer'],
            'option'       => $cart_data['option'],
            'attribute'    => $cart_data['attribute'],
            'location'     => $cart_data['location']
        );
        foreach ($_applicable as $key => $value) {
            if (isset($rules[$key])) {
                if ($rules[$key]['rule_type'] == 5 || $rules[$key]['rule_type'] == 7) {
                    $_applicable[$key] = $rules[$key]['value'] ? array_diff($cart_data[$key], $rules[$key]['value']) : $rules[$key]['value'];
                } else {
                    $_applicable[$key] = $rules[$key]['value'];
                }
            }
        }
        return $_applicable;
    }
    private function _getMethodSpecificData($need_specified, $rules, $applicable_cart, $cart_data, $product_or) {
        $_method = array();
        $_method_category = array();
        $_method['quantity'] = $need_specified ? 0 : $cart_data['quantity'];
        $_method['weight'] = $need_specified ? 0 : $cart_data['weight'];
        $_method['total'] = $need_specified ? 0 : $cart_data['total'];
        $_method['sub'] = $need_specified ? 0 : $cart_data['sub'];
        $_method['special'] = $need_specified ? 0 : $cart_data['special'];
        $_method['original'] = $need_specified ? 0 : $cart_data['original'];
        $_method['special_tax'] = $need_specified ? 0 : $cart_data['special_tax'];
        $_method['volume'] = $need_specified ? 0 : $cart_data['volume'];
        $_method['dimensional'] = $need_specified ? 0 : $cart_data['dimensional'];
        $_method['volumetric'] = $need_specified ? 0 : $cart_data['volumetric'];
        $_method['products'] = $need_specified ? array() : $cart_data['products'];
        $_method['no_product'] = $need_specified ? 0 : $cart_data['no_product'];
        $_method['no_category'] = $need_specified ? 0 : $cart_data['no_category'];
        $_method['no_manufacturer'] = $need_specified ? 0 : $cart_data['no_manufacturer'];
        $_method['no_location'] = $need_specified ? 0 : $cart_data['no_location'];
        $_method['jan'] = $need_specified ? 0 : $cart_data['jan'];
        $_method['ean'] = $need_specified ? 0 : $cart_data['ean'];
        $_method['lowest'] = $need_specified ? 0 : $cart_data['lowest'];
        $_method['highest'] = $need_specified ? 0 : $cart_data['highest'];
        $_method['lowest_qnty'] = $need_specified ? 0 : $cart_data['lowest_qnty'];
        $_method['highest_qnty'] = $need_specified ? 0 : $cart_data['highest_qnty'];
        $_method['out_of_stock'] = $need_specified ? 0 : $cart_data['out_of_stock'];
        $_method['per_manufacturer'] = $need_specified ? array() : $cart_data['per_manufacturer'];
        $_method['non_method_quantity'] = 0;
        $_method['non_method_sub'] = 0;
        $_method['non_method_total'] = 0;
        if ($need_specified) {
            foreach($cart_data['products'] as $product) {
                $count_on = !$product_or;
                $force_off = !$product_or;
                foreach ($rules as $key => $rule) {
                    if (!$rule['product_rule']) continue;
                    // ignore product if value is not available and rule type is not `except`
                    if (empty($product[$key])) {
                        if ($rule['rule_type'] == 5 || $rule['rule_type'] == 7) {
                            if ($product_or) {
                                $count_on = true; // if mode is or, set count_on to true as it meets the condition
                            }
                            $force_off = false;
                        }
                        continue;
                    }
                    $_count_on = ($key == 'category' || $key == 'attribute' || $key == 'option') ? $this->array_intersect_faster($product[$key],$applicable_cart[$key]) : in_array($product[$key], $applicable_cart[$key]);
                    $count_on = $product_or ? ($count_on | $_count_on) : ($count_on & $_count_on);
                    /* additional check for rule 5 and 7 i.e except ...*/
                    if ($count_on && ($rule['rule_type'] == 5 || $rule['rule_type'] == 7)) {
                        $_force_off = ($key == 'category' || $key == 'attribute' || $key == 'option') ? $this->array_intersect_faster($product[$key], $rule['value']) : in_array($product[$key], $rule['value']);
                        $force_off = $product_or ? ($force_off | $_force_off) : ($force_off & $_force_off);
                    } else {
                        $force_off = false;
                    }
                }
                if ($count_on && !$force_off) {
                    $_method['products'][]   = $product;
                    $_method['quantity']    += $product['quantity'];
                    $_method['weight']      += $product['weight'];
                    $_method['total']       += $product['total_with_tax'];
                    $_method['sub']         += $product['total'];
                    $_method['special']     += $product['ocm_special'] ? $product['total'] : 0;
                    $_method['special_tax'] += $product['ocm_special'] ? $product['total_with_tax'] : 0;
                    $_method['original']    += isset($product['original']) ? $product['original'] : 0;
                    $_method['jan']         += isset($product['jan']) ? $product['jan'] : 0;
                    $_method['ean']         += isset($product['ean']) ? $product['ean'] : 0;
                    $_method['volume']      += isset($product['volume']) ? $product['volume'] : 0;
                    $_method['dimensional'] += isset($cart_data['product_dimensional'][$product['product_id']]) ? $cart_data['product_dimensional'][$product['product_id']] : 0;
                    $_method['volumetric']  += isset($cart_data['product_volumetric'][$product['product_id']]) ? $cart_data['product_volumetric'][$product['product_id']] : 0;
                    $_method_category       = array_merge($_method_category, $product['category']);
                    $_method['no_product']++;
                    $_method['no_manufacturer'] += isset($product['manufacturer']) ? 1 : 0;
                    $_method['no_location']     += isset($product['location']) ? 1 : 0;
                    if ($_method['lowest'] > $product['price'] || !$_method['lowest']) {
                        $_method['lowest']      = $product['price'];
                        $_method['lowest_qnty'] = $product['quantity'];
                    }
                    if ($_method['highest'] < $product['price']) {
                        $_method['highest']      = $product['price'];
                        $_method['highest_qnty'] = $product['quantity'];
                    }
                    if (!$product['stock']) {
                        $_method['out_of_stock'] += $product['quantity'];
                    }
                    //per manufacturer
                    if (!isset($_method['per_manufacturer'][$product['manufacturer_id']]) && $product['manufacturer_id']) {
                        $_method['per_manufacturer'][$product['manufacturer_id']] = 0;
                    }
                    if ($product['manufacturer_id']) {
                        $_method['per_manufacturer'][$product['manufacturer_id']] += $product['total'];
                    }
                }  else {
                    $_method['non_method_quantity'] += $product['quantity'];
                    $_method['non_method_sub'] += $product['total'];
                    $_method['non_method_total'] += $product['total_with_tax'];
                }
            }
            $_method['no_category'] = count(array_unique($_method_category));
       }
       $_method['sub_coupon']       = ($_method['sub'] + $cart_data['coupon'] + $cart_data['reward']);
       $_method['total_coupon']     = ($_method['total'] + $cart_data['coupon'] + $cart_data['reward']);
       $_method['grand']            = $cart_data['grand'] - $_method['non_method_total'];
       $_method['grand_shipping']   = $cart_data['grand_shipping'] - $_method['non_method_total'];
       $_method['grand_wtax']       = $cart_data['grand_wtax'] - $_method['non_method_sub'];
       $_method['sub_special']      = $_method['sub'] - $_method['special'];
       $_method['total_special']    = $_method['total'] - $_method['special_tax'];
       $_method['sub_negative']     = $_method['sub'] - $cart_data['negative'];
       $_method['price_self']       = $_method['sub'];
       $_method['price_self_tax']   = $_method['total'];
       /* Shipping cost related */
       $_method['shipping']             = $cart_data['shipping'];
       $_method['shipping_plus']        = $cart_data['shipping_plus'];
       $_method['sub_shipping']         = $_method['sub'] + $_method['shipping'];
       $_method['total_shipping']       = $_method['total'] + $_method['shipping'];
       $_method['sub_shipping_plus']    = $_method['sub_shipping'] + $cart_data['shipping_tax'];
       $_method['total_shipping_plus']  = $_method['total_shipping'] + $cart_data['shipping_tax'];

       $_method['vouchers']     = $cart_data['vouchers'];
       $_method['distance']     = $cart_data['distance'];
       $_method['no_package']   = 1;
       $_method['no_block']     = 0;
       $_method['block_asc']    = 0;
       $_method['block_desc']   = 0;
       $_method['no_block_asc'] = 0;
       $_method['no_block_desc'] = 0;
       return $_method;
    }
    private function _calVirtualWeight($cart_products, $factor_value, $over_rule) {
        $dimensional = 0;
        $volumetric = 0;
        $product_dimensional = array();
        $product_volumetric = array();
        foreach ($cart_products as $product) {
            $single_dimensional_weight = ($product['volume'] / $factor_value) * $product['weight'];
            $single_volumetric_weight = ($product['volume'] / $factor_value);

            if ($over_rule && $single_dimensional_weight < $product['weight']) {
                $single_dimensional_weight = $product['weight'];
            }
            if ($over_rule && $single_volumetric_weight < $product['weight']) {
                $single_volumetric_weight = $product['weight'];
            }
            $dimensional += $single_dimensional_weight;
            $volumetric += $single_volumetric_weight;
            $product_dimensional[$product['product_id']] = $single_dimensional_weight;
            $product_volumetric[$product['product_id']] = $single_volumetric_weight;
        }
        return array(
            'dimensional' => $dimensional,
            'volumetric' => $volumetric,
            'product_dimensional' => $product_dimensional,
            'product_volumetric' => $product_volumetric
        );
    }
}