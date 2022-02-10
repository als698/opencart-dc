<?php
namespace OCM\Traits\Front;
trait Coupon {
    private function sanitizeCoupon($_coupon) {
        // coupon value
        if (!empty($_coupon['discount'])) {
           $discount = trim(trim($_coupon['discount']), '-');
           if (substr($discount, -1) == '%') {
                $discount = rtrim($discount,'%');
                $_coupon['discount'] = (float)$discount;
                $_coupon['type'] = 'P';
            } else {
                $_coupon['discount'] = (float)$discount;
                $_coupon['type'] = 'F';
            }
        } else {
            $_coupon['type'] = 'F';
        }
        if (empty($_coupon['product'])) {
            $_coupon['product'] = array();
        }
        if (empty($_coupon['category'])) {
            $_coupon['category'] = array();
        }
        if (!isset($_coupon['xcoupon_id'])) {
            $_coupon['xcoupon_id'] = 0;
        }
        $_coupon['start_type'] = !isset($_coupon['start_type']) || $_coupon['start_type'] == 'auto' ? 'auto' : 'manual';
        $_coupon['coupon_start'] = !isset($_coupon['coupon_start']) ? date('Y-m-d') : $_coupon['coupon_start'];
        return $_coupon;
    }
    private function insertCoupon($_coupon) {
        $default = array(
            'discount'        => 0,
            'type'            => 'F',
            'logged'          => 0,
            'shipping'        => 0,
            'total'           => 0,
            'uses_total'      => 1,
            'uses_customer'   => 1,
            'coupon_product'  => array(),
            'coupon_category' => array()
        );
        $date_start = $_coupon['start_type'] == 'auto' ? date('Y-m-d') : $_coupon['coupon_start'];
        $coupon_data = array();
        $coupon_data['name']            = $_coupon['name'];
        $coupon_data['code']            = $_coupon['code'];
        $coupon_data['status']          = 1;
        $coupon_data['date_start']      = $date_start;
        $coupon_data['date_end']        = $this->getEndTime((int)$_coupon['coupon_expire'], $_coupon['coupon_expire_type']);
        if ($_coupon['xcoupon_id']) {
            $coupon_data = array_merge($default, $coupon_data);
        } else {
            $coupon_data['type']            = $_coupon['type'];
            $coupon_data['discount']        = $_coupon['discount'];
            $coupon_data['total']           = $_coupon['total'];
            $coupon_data['logged']          = $_coupon['logged'];
            $coupon_data['shipping']        = $_coupon['shipping'];
            $coupon_data['uses_total']      = $_coupon['uses_total'];
            $coupon_data['uses_customer']   = $_coupon['uses_customer'];
            $coupon_data['coupon_product']  = $_coupon['product'];
            $coupon_data['coupon_category'] = $_coupon['category'];
        }
        $coupon_id = $this->addCoupon($coupon_data);
        if ($_coupon['xcoupon_id']) {
            $this->insertXCoupon($coupon_id, $_coupon['xcoupon_id']);
        }
        return $coupon_id;
    }
    private function addCoupon($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "coupon SET name = '" . $this->db->escape($data['name']) . "', code = '" . $this->db->escape($data['code']) . "', discount = '" . (float)$data['discount'] . "', type = '" . $this->db->escape($data['type']) . "', total = '" . (float)$data['total'] . "', logged = '" . (int)$data['logged'] . "', shipping = '" . (int)$data['shipping'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "', uses_total = '" . (int)$data['uses_total'] . "', uses_customer = '" . (int)$data['uses_customer'] . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

        $coupon_id = $this->db->getLastId();
        if (isset($data['coupon_product'])) {
            foreach ($data['coupon_product'] as $product_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "coupon_product SET coupon_id = '" . (int)$coupon_id . "', product_id = '" . (int)$product_id . "'");
            }
        }
        if (isset($data['coupon_category'])) {
            foreach ($data['coupon_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "coupon_category SET coupon_id = '" . (int)$coupon_id . "', category_id = '" . (int)$category_id . "'");
            }
        }
        return $coupon_id;
    }
    private function insertXCoupon($coupon_id, $xcoupon_id) {
        if ($coupon_id && (int)$xcoupon_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "xcoupon_coupons SET coupon_id = ". (int)$coupon_id .", tab_id = " . (int)$xcoupon_id);
        }
    }
    private function getCouponCode() {
        $chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $length = 6;
        $try = 0;
        while(true) {
            $code = $chars[array_rand($chars)] . $chars[array_rand($chars)] . $chars[array_rand($chars)] . rand(100, 32000) . rand(20, 32000) . rand(20, 32000);
            $code = substr($code, 0, $length);
            if (!$this->db->query("SELECT DISTINCT coupon_id FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->db->escape($code) . "'")->row) {
                break;
            }
            $try++;
            if ($try % 100 === 0) {
                $length++;
            }
        }
        return $code;
    }
    private function getEndTime($value, $type) {
        $type_full = array(
            'D' => 'day',
            'W' => 'week',
            'M' => 'month'
        );
        $type = $type_full[$type];
        if ($value > 1) {
            $type .= 's';
        }
        return date('Y-m-d', strtotime("+". $value . " " . $type));
    }
}