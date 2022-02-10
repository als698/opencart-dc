<?php
namespace OCM\Traits\Back\Controller;
trait Coupon {
    private function getCouponForm($coupon_info, $discount = true) {
        $return = '';
        $return .= $this->ocm->form->get('input', 'name');
        if ($this->getXCouponStatus()) {
            $return .= $this->ocm->form->get('select', 'xcoupon_id');
        }
        if ($discount) {
            $return .= $this->ocm->form->get('input', array('name' => 'discount', 'class' => 'xcoupon_id'));
        }
        $return .= $this->ocm->form->get('input', array('name' => 'total', 'class' => 'xcoupon_id'));
        $return .= $this->ocm->form->get('radio', array('name' => 'logged', 'class' => 'xcoupon_id'));
        $return .= $this->ocm->form->get('radio', array('name' => 'shipping', 'class' => 'xcoupon_id'));
        /* Products  */
        $products = array();
        foreach ($coupon_info['product'] as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);
            if ($product_info) {
                $products[] = array(
                    'product_id' => $product_id,
                    'name'       => $product_info['name']
                );
            }
        }
        $param = array(
            'name'  => 'product[]',
            'options' => $this->ocm->form->getOptions($products, 'product_id'),
            'attr'  => 'product',
            'browser' => 'product',
            'class'  => 'ocm-visible xcoupon_id'
        );
        $return .= $this->ocm->form->get('autofill', $param);

        /* categories */
        $categories = array();
        foreach ($coupon_info['category'] as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);
            if ($category_info) {
                if ($category_info['path']) $category_info['path'] .=  '&nbsp;&nbsp;&gt;&nbsp;&nbsp;';
                $categories[] = array(
                    'category_id' => $category_id,
                    'name'       => $category_info['path'].$category_info['name']
                );
            }
        }
        $param = array(
            'name'  => 'category[]',
            'options' => $this->ocm->form->getOptions($categories, 'category_id'),
            'attr'  => 'category',
            'browser' => 'category',
            'class'  => 'ocm-visible xcoupon_id'
        );
        $return .= $this->ocm->form->get('autofill', $param);
        $return .= $this->ocm->form->get('radio', array('name' => 'start_type'));
        $visible = $coupon_info['start_type'] == 'manual';
        $return .= $this->ocm->form->get('datetime', array('name' => 'coupon_start', 'date' => true, 'class' => 'ocm-hide start_type manual', 'visible' => $visible));
        $return .= $this->ocm->form->get('input', array('name' => 'coupon_expire'));
        $return .= $this->ocm->form->get('select', array('name' => 'coupon_expire_type'));
        $return .= $this->ocm->form->get('input', array('name' => 'uses_total', 'class' => 'xcoupon_id'));
        $return .= $this->ocm->form->get('input', array('name' => 'uses_customer', 'class' => 'xcoupon_id'));
        return $return;
    }
    private function getCouponDefault() {
        return array(
            'product'       => array(),
            'category'      => array(),
            'total'         => 0,
            'logged'        => 1,
            'shipping'      => 0,
            'uses_total'    => 1,
            'uses_customer' => 1,
            'coupon_expire' => 100,
            'xcoupon_id'    => 0,
            'coupon_start'  => '',
            'start_type' => 'auto'
        );
    }
    private function getXCouponGroups() {
        $coupon_groups = array(
            '0' => 'None'
        );
        if ($this->getXCouponStatus()) {
            $this->load->model('extension/module/xcoupon');
            $method_data = $this->model_extension_module_xcoupon->getData();
            $method_data = $this->getMethodList($method_data);
            $coupon_groups = array_merge($coupon_groups, $method_data);
        }
        return $coupon_groups;
    }
    private function getXCouponStatus() {
        return !!$this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `code` = 'xcoupon'")->row;
    }
}