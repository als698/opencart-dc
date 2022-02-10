<?php
namespace OCM\Traits\Back\Controller;
trait Product {
    public function getOption() {
        $json = array();
        if (isset($this->request->get['filter_name'])) {
            $this->load->language('catalog/option');
            $this->load->model('catalog/option');
            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );
            $options = $this->model_catalog_option->getOptions($filter_data);
            foreach ($options as $option) {
                $option_value_data = array();
                if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox') {
                    $option_values = $this->model_catalog_option->getOptionValues($option['option_id']);
                    foreach ($option_values as $option_value) {
                        $json[] = array(
                            'option_value_id' => $option_value['option_value_id'],
                            'name'            => strip_tags(html_entity_decode($option['name'], ENT_QUOTES, 'UTF-8')).'&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.strip_tags(html_entity_decode($option_value['name'], ENT_QUOTES, 'UTF-8'))
                        );
                    }
                }
            }
        }
        $sort_order = array();
        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }
        array_multisort($sort_order, SORT_ASC, $json);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function getAttribute($data = array()) {
        $json = array();
        if (isset($this->request->get['filter_name'])) {
            $this->load->language('catalog/attribute');
            $this->load->model('catalog/attribute');
            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );
            $attributes = $this->model_catalog_attribute->getAttributes($filter_data);
            foreach ($attributes as $attribute) {
                $json[] = array(
                    'attribute_id' => $attribute['attribute_id'],
                    'name'         => strip_tags(html_entity_decode($attribute['attribute_group'], ENT_QUOTES, 'UTF-8')).'&nbsp;&nbsp;&gt;&nbsp;&nbsp;'.strip_tags(html_entity_decode($attribute['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }
        $sort_order = array();
        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }
        array_multisort($sort_order, SORT_ASC, $json);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function getCustomer() {
        $json = array();
        $filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
        $filter_data = array('filter_name' => $filter_name, 'start' => 0, 'limit' => 15);

        $cg_path = (VERSION >= '2.1.0.1') ? 'customer' : 'sale';
        $this->load->model($cg_path . '/customer');
        $results = $this->{'model_' . $cg_path . '_customer'}->getCustomers($filter_data);
        foreach ($results as $result) {
            $name = strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')) . ' (ID: '.$result['customer_id'].' - '.$result['customer_group'].')';
            $json[] = array(
                'customer_id'  => $result['customer_id'],
                'name'         => $name
            );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function getLocation() {
        $json = array();
        $filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
        $rows = $this->db->query("select distinct location from " . DB_PREFIX . "product WHERE location !='' AND location LIKE '%" . $this->db->escape($filter_name) . "%' ORDER BY location ASC LIMIT 0,5")->rows;

        foreach ($rows as $single) {
            $json[] = array(
                'name' => $single['location'],
                'id' => md5(trim($single['location']))
            );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function getProducts() {
        $this->load->model($this->ext_path);
        $filter = isset($this->request->post['filter']) ? $this->request->post['filter'] : array();
        $is_filtered = false;
        $filter_data = array();
        $filter_data['sort'] = 'pd.name';
        $filter_data['order'] = 'ASC';

        if ($filter['field'] && $filter['keyword']) {
            $filter_data['filter_' . $filter['field']] = $filter['keyword'];
            $is_filtered = true;
        }
        if ($filter['category_id']) {
            $filter_data['filter_category'] = $filter['category_id'];
            $is_filtered = true;
        }
        if ($filter['manufacturer_id']) {
            $filter_data['filter_manufacturer'] = $filter['manufacturer_id'];
            $is_filtered = true;
        }
        if (!$is_filtered) {
            $filter_data['limit'] = 50;
            $filter_data['start'] = 0;
        }
        $json = $this->{$this->ext_key}->getProducts($filter_data);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
    public function fetchCategoy() {
        $this->load->model('catalog/category');
        $xselected = isset($this->request->post['ocm_selected']) ? $this->request->post['ocm_selected'] : array();
        $inc_child = isset($this->request->post['inc_child']) ? true : false;
        if ($inc_child) {
            $xselected = $this->getSubCat($xselected);
        }
        $json = array();
        foreach ($xselected as $category_id) {
           $category_info = $this->model_catalog_category->getCategory($category_id);
           $json[] = array(
              'category_id' => $category_info['category_id'],
              'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
            );
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
    private function getSubCat($categories) {
        $childs = array();
        $rows = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "category_path` WHERE path_id IN (" . implode(',', $categories) . ")")->rows;
        foreach ($rows as $row) {
           $childs[] = $row['category_id'];
        }
        return $childs;
    }
    public function fetchZone() {
        $country = isset($this->request->post[$this->meta['name']]) && isset($this->request->post[$this->meta['name']]['country']) && $this->request->post[$this->meta['name']]['country'] ? $this->request->post[$this->meta['name']]['country'] : array();
        $json = $this->getZones($country);
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    private function getZones($countries) {
        if (!$countries && $this->config->get('config_country_id')) {
            $countries = array($this->config->get('config_country_id'));
        }
        if (!$countries) return array();
        /* truncate upto max 5 counties */
        if (count($countries) > 10) {
            $countries = array_slice($countries, 0, 10);
        }
        return $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id in (".implode(',', $countries).")")->rows;
    }
    public function getCustomFields() {
        $return = array();
        $groups = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_field_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC")->rows;
        foreach ($groups as $group) {
            $fields = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_field_value_description WHERE custom_field_id = '" . (int)$group['custom_field_id'] . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name ASC")->rows;
            foreach ($fields as $field) {
                $return[] = array(
                    'custom_field_value_id' => $field['custom_field_value_id'],
                    'name' => $group['name'] . ' - ' . $field['name']
                ); 
            }
        }
        return $return;
    }
}