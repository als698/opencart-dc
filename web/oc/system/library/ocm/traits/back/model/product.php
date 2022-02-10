<?php
namespace OCM\Traits\Back\Model;
trait Product {
    public function getBatchProducts($ids = array()) {
        if (!$ids) return array();
        $return = array();
        $sql = "SELECT `p`.`product_id`, `p`.`price`, `pd`.`name` FROM " . DB_PREFIX . "product p";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.product_id IN (".implode(',', $ids).")";
        $products = $this->db->query($sql)->rows;
        foreach ($products as $product) {
            $return[$product['product_id']] = array(
                'name' => $product['name'],
                'price' => $product['price']
            );
        }
        return $return;
    }
    public function getProducts($data = array()) {
        $sql = "SELECT `p`.`product_id`, `p`.`price`, `pd`.`name` FROM " . DB_PREFIX . "product p";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id = p.product_id)";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1'";

        /* TODO  p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' */
        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }
        if (!empty($data['filter_sku'])) {
            $sql .= " AND p.sku = '" . $this->db->escape($data['filter_sku']) . "'";
        }
        if (!empty($data['filter_jan'])) {
            $sql .= " AND p.jan = '" . $this->db->escape($data['filter_jan']) . "'";
        }
        if (isset($data['filter_manufacturer']) && $data['filter_manufacturer'] !== '') {
            $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer'] . "'";
        }
        if (isset($data['filter_category']) && $data['filter_category'] !== '') {
            $sql .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
        }
        $sql .= " GROUP BY p.product_id";
        $sort_data = array(
            'pd.name',
            'p.model',
            'p.quantity',
            'p.price',
            'rating',
            'p.sort_order',
            'p.date_added'
        );
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
           if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
                $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
           } else {
               $sql .= " ORDER BY " . $data['sort'];
           }
        } else {
            $sql .= " ORDER BY p.sort_order";
        }
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC, LCASE(pd.name) DESC";
        } else {
            $sql .= " ASC, LCASE(pd.name) ASC";
        }
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        return $this->db->query($sql)->rows;
    }
    public function getAttribute($attribute_id) {
        return $this->db->query("SELECT *, (SELECT agd.name FROM " . DB_PREFIX . "attribute_group_description agd WHERE agd.attribute_group_id = a.attribute_group_id AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS attribute_group FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND a.attribute_id = '" . (int)$attribute_id . "'")->row;
    }
}