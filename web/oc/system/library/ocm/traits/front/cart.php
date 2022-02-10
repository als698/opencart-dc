<?php
namespace OCM\Traits\Front;
trait Cart {
    private function isValidateCart($product_id, $option = array(), $recurring_id = 0, $quantity = 1) {
        $this->load->model('catalog/product');
        $this->load->language('checkout/cart');
        $json = array();
        $product_info = $this->model_catalog_product->getProduct($product_id);
        if ((int)$quantity < $product_info['minimum']) {
            $quantity = $product_info['minimum'];
        }
        $product_options = $this->model_catalog_product->getProductOptions($product_id);
        foreach ($product_options as $product_option) {
            if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                $json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
            }
        }
        $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);
        if ($recurrings) {
            $recurring_ids = array();
            foreach ($recurrings as $recurring) {
                $recurring_ids[] = $recurring['recurring_id'];
            }
            if (!in_array($recurring_id, $recurring_ids)) {
                $json['error']['recurring'] = $this->language->get('error_recurring_required');
            }
        }
        // this will give customer to choose non-required options
        if (!isset($json['error']['option']) && $product_options && !isset($this->request->post['ocm_option'])) {
            $json['error']['option'] = true;
        }
        return $json;
    }
    private function refreshCart($product_info = array()) {
        $this->load->language('checkout/cart');
        $return = array();
        $total = 0;
        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
            $product_taxes = $this->cart->getTaxes();
            $xtotals = $this->ocm->getTotals($product_taxes);
            $total = $xtotals['total'];
        }
        $items_count = $this->cart->countProducts();
        $return['items_count'] = $items_count;
        $return['total'] = sprintf($this->language->get('text_items'), $items_count + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
        if ($product_info) {
            $return['success'] = sprintf($this->language->get('success_add_to_cart'), $this->url->link('product/product', 'product_id=' . $product_info['product_id']), $product_info['name'], $this->url->link('checkout/cart'));
        }
        return $return;
    }
}