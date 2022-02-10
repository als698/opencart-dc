<?php
namespace OCM\Traits\Front;
trait Product_options {
    public function getOptions() {
        $ext_lang = $this->load->language($this->ext_path);
        $prodct_lang = $this->load->language('product/product');
        $this->load->model('tool/image');
        $this->load->model('catalog/product');
        $data = array();
        $data = array_merge($data, $ext_lang, $prodct_lang);
        $product_id = isset($this->request->get['product_id']) ? $this->request->get['product_id'] : 0;
        $data['options'] = array();
        $stock_avail = false;
        $product_info = $this->model_catalog_product->getProduct($product_id);
        foreach ($this->model_catalog_product->getProductOptions($product_id) as $option) {
            $product_option_value_data = array();
            foreach ($option['product_option_value'] as $option_value) {
                if (!$option_value['subtract'] || $option_value['quantity'] > 0 || $this->config->get('config_stock_checkout')) {
                    /* apply discount on option price if available  */
                    $is_free_option = false; 
                    if (method_exists($this->{$this->name}, 'getOptionPrice')) {
                        $_return = $this->{$this->name}->{'getOptionPrice'}($option_value['price'], $product_id, $option_value['price_prefix'], $option_value['option_value_id']);
                        if ($_return !== false) {
                            $option_value['price'] = $_return['price'];
                            $is_free_option = !$_return['price'];
                        }
                    }
                    /* end of option discount */
                    if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
                        $price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
                    } else {
                        $price = false;
                    }
                    $image = !empty($option_value['ciopimage']) ? $option_value['ciopimage'] : $option_value['image']; // patch for `option image` module
                    $product_option_value_data[] = array(
                        'product_option_value_id' => $option_value['product_option_value_id'],
                        'option_value_id'         => $option_value['option_value_id'],
                        'name'                    => $option_value['name'],
                        'image'                   => $this->model_tool_image->resize($image, 80, 80),
                        'price'                   => $price,
                        'price_prefix'            => $option_value['price_prefix'],
                        'ocm_pre_selected'        => $is_free_option
                    );
                }
            }
            if ($product_option_value_data || ($option['type'] != 'radio' && $option['type'] != 'checkbox' && $option['type'] != 'select')) {
                $data['options'][] = array(
                    'product_option_id'    => $option['product_option_id'],
                    'product_option_value' => $product_option_value_data,
                    'option_id'            => $option['option_id'],
                    'name'                 => $option['name'],
                    'type'                 => $option['type'],
                    'value'                => $option['value'],
                    'required'             => $option['required']
                );
                $stock_avail = true;
            }
        }
        if (VERSION < '3.0.0.0') {
            $data['datepicker'] = 'en';
        }
        $data['name'] = $product_info['name'];
        $data['recurrings'] = $this->model_catalog_product->getProfiles($product_id);
        $data['no_stock'] = !$stock_avail && !$data['recurrings'] ? $this->language->get('error_no_stock') : '';
        $path = ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm_options';
        $this->response->setOutput($this->ocm->view($path, $data));
    }
}