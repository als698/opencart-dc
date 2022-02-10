<?php
namespace OCM\Traits\Front;
trait Address {
    private function _replenishAddress($address = array(), $precedence = 's') {
        $fields = array('zone_id', 'country_id', 'city', 'postcode');
        /* Xshippingpro estimator */
        if (isset($this->request->post['_xestimator'])) {
            $_xestimator = $this->request->post['_xestimator'];
            foreach ($fields as $field) {
                if (!isset($address[$field])) {
                    $address[$field] = '';
                }
                if (!$address[$field] && !empty($_xestimator[$field])) {
                    $address[$field] = $_xestimator[$field];
                }
            }
        }
        $sessions = $precedence === 's' ? array('shipping_address', 'payment_address') : array('payment_address', 'shipping_address');
        foreach ($sessions as $key) {
            foreach ($fields as $field) {
                if (!isset($address[$field])) {
                    $address[$field] = '';
                }
                if (!$address[$field]
                    && isset($this->session->data[$key])
                    && !empty($this->session->data[$key][$field])) {
                        $address[$field] = $this->session->data[$key][$field];
                }
            }
        }
        /* Still country emptry, set default one */
        if (!$address['country_id']) {
            $address['country_id'] = $this->config->get('config_country_id');
        }
        if (!$address['zone_id']) {
            $address['zone_id'] = $this->config->get('config_zone_id');
        }
        /* all option has failed for postal and city, lets fetch from address book */
        if (!$address['postcode'] && !$address['city'] && $this->customer->isLogged()) {
            $this->load->model('account/address');
            $customer_address = $this->model_account_address->getAddress($this->customer->getAddressId());
            if ($customer_address) {
                $address['postcode'] = $customer_address['postcode'];
                $address['city'] = $customer_address['city'];
            }
        }
        $address['city'] = strtolower(trim($address['city']));
        $address['postcode'] = strtolower(trim($address['postcode']));
        
        $custom_field = array();
        $sessions[] = 'guest';
        foreach ($sessions as $key) {
            if (isset($this->session->data[$key]) && isset($this->session->data[$key]['custom_field'])) {
                if (is_array($this->session->data[$key]['custom_field'])) {
                    foreach ($this->session->data[$key]['custom_field'] as $_custom_field) {
                        if (is_array($_custom_field)) {
                            $custom_field = array_merge($custom_field, $_custom_field);
                        } else {
                            $custom_field[] = $_custom_field;
                        }
                    }
                } else {
                    $custom_field[] = $this->session->data[$key]['custom_field'];
                }
            }
        }
        $address['custom_field'] = $custom_field;
        return $address;
    }
}