<?php
namespace OCM\Traits\Front;
trait Common_params {
    private function _getCommonParams($address) {
        $param = array();
        $customer_group_id = $this->ocm->common->getVar('customer_group_id');
        if (!$customer_group_id && !empty($this->request->post['order_data']['customer_group_id'])) {
            $customer_group_id = $this->request->post['order_data']['customer_group_id'];
        }
        else if (!$customer_group_id && $this->customer->isLogged()) {
            $customer_group_id = $this->customer->getGroupId();
        } elseif (isset($this->session->data['customer']) && !empty($this->session->data['customer']['customer_group_id'])) {
            $customer_group_id = $this->session->data['customer']['customer_group_id'];
        } elseif (isset($this->session->data['guest']) && !empty($this->session->data['guest']['customer_group_id'])) {
            $customer_group_id = $this->session->data['guest']['customer_group_id'];
        } else if (!$customer_group_id) {
            $customer_group_id = 0;
        }
        
        $store_id = $this->ocm->common->getVar('store_id');
        if (!$store_id) {
            $store_id = $this->config->get('config_store_id');
        }

        $payment_method = isset($this->session->data['payment_method']['code']) ? $this->session->data['payment_method']['code'] : '';
        if (isset($this->session->data['default']['payment_method']['code'])) $payment_method = $this->session->data['default']['payment_method']['code'];
        $payment_method = isset($this->request->post['payment_method']) && $this->request->post['payment_method'] ? $this->request->post['payment_method'] : $payment_method;

        $shipping_method = isset($this->session->data['shipping_method']['code']) ? $this->session->data['shipping_method']['code'] : '';
        if (isset($this->session->data['default']['shipping_method']['code'])) $shipping_method = $this->session->data['default']['shipping_method']['code'];
        $shipping_method = isset($this->request->post['shipping_method']) && $this->request->post['shipping_method'] ? $this->request->post['shipping_method'] : $shipping_method;
        if (strpos($shipping_method, 'xshippingpro') !== false) {
            $shipping_method = explode('_', $shipping_method)[0];
        }
        $shipping_parts = explode('.', $shipping_method);
        $shipping_base = array_shift($shipping_parts);

        /* currency */
        $currency_code = isset($this->session->data['currency']) ? $this->session->data['currency'] : $this->config->get('config_currency');
        $currency_id = $this->currency->getId($currency_code);

        /* Coupon code */
        $coupon_code = '';
        if (isset($this->session->data['default']['coupon']) && $this->session->data['default']['coupon']) {
            $coupon_code = $this->session->data['default']['coupon'];
        }
        if (isset($this->session->data['coupon']) && $this->session->data['coupon']) {
            $coupon_code = $this->session->data['coupon'];
        }
        // some module create array to apply multiple coupons so just take one
        if (is_array($coupon_code)) {
            $coupon_code = array_pop($coupon_code);
        }
        if ($coupon_code) {
            $coupon_code = strtolower($coupon_code);
        }
        $param['customer_id']       = $this->customer->getId();
        $param['custom_field']      = $address['custom_field'];
        $param['store_id']          = $store_id;
        $param['customer_group_id'] = $customer_group_id;
        $param['payment_method']    = $payment_method;
        $param['shipping_method']   = $shipping_method;
        $param['shipping_base']     = $shipping_base;
        $param['coupon_code']       = $coupon_code;
        $param['city']              = $address['city'];
        $param['country_id']        = $address['country_id'];
        $param['zone_id']           = $address['zone_id'];
        $param['postcode']          = $address['postcode'];
        $param['currency_id']       = $currency_id;
        $param['time']              = date('G');
        $param['date']              = date('Y-m-d');
        $param['day']               = date('w');
        return $param;
    }
}