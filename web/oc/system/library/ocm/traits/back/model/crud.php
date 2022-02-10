<?php
namespace OCM\Traits\Back\Model;
trait Crud {
    public function addData($data) {
        $_is_exist = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->name . "` WHERE tab_id = '" . (int)$data['tab_id'] . "'")->row;
        $root = "`" . DB_PREFIX . $this->name . "` SET method_data= '" . $this->db->escape($data['method_data']) . "', sort_order = '" . (int)$data['sort_order'] . "', tab_id = '" . (int)$data['tab_id'] . "'";
        $sql = $_is_exist ? "UPDATE " . $root . "WHERE tab_id = '" . (int)$data['tab_id'] . "'" : "INSERT INTO " . $root;
        $this->db->query($sql);
        return true;
    }
    public function saveSort($data) {
        if ($data && is_array($data)) {
            foreach ($data as $method) {
                $sql = "UPDATE `" . DB_PREFIX . $this->name . "` SET sort_order = '".$method['sort_order']."'";
                $sql .= " WHERE tab_id = '" . (int)$method['tab_id'] . "'";
                $this->db->query($sql);
            }
        }
    }
    public function getUnCompressedData($data) {
        $data = json_decode($data, true);
        return $data;
    }
    public function getData() {
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->name . "` order by `sort_order` asc")->rows;
        foreach ($rows as &$row) {
            $row['method_data'] = $this->getUnCompressedData($row['method_data']);
        }
        return $rows;
    }
    public function getDataByTabId($tab_id) {
        $row =  $this->db->query("SELECT * FROM `" . DB_PREFIX . $this->name . "` WHERE tab_id = '" . (int)$tab_id . "'")->row;
        if ($row) {
            $row['method_data'] = $this->getUnCompressedData($row['method_data']);
        }
        return $row;
    }
    public function deleteData($tab_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . $this->name . "` WHERE tab_id = '" . (int)$tab_id . "'");
        return true;
    }
}