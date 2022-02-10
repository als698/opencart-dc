<?php
namespace OCM;
final class Util {
    private $registry;
    public function __construct($registry) {
        $this->registry = $registry;
        $this->ocm_meta = $registry->get('ocm_meta');
        $setting_ext = (VERSION >= '3.0.0.0') ? 'setting' : 'extension';
        
        if (VERSION >= '2.0.1.1') {
            $this->load->model($setting_ext . '/event');
            $this->event = $this->{'model_' . $setting_ext . '_event'};
        }
        $this->load->model($setting_ext . '/modification');
        $this->modification = $this->{'model_' . $setting_ext . '_modification'};
        
        $this->ocm_events = array(
            array(
                'trigger' => 'catalog/view/*/after',
                'action'  => ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm/onViewAfter'
            ),
            array(
                'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
                'action'  => ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm/onOrderHistory'
            ),
            array(
                'trigger' => 'catalog/model/' . $setting_ext . '/extension/getExtensions/after',
                'action'  => ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm/onExtensions'
            ),
            array(
                'trigger' => 'catalog/model/*/product/*/after',
                'action'  => ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm/onProductAfter'
            )
        );
        // facebook business extension
        if ($this->config->get((VERSION >= '3.0.0.0' ? 'module_' : '') . 'facebook_business_status')) {
            $this->ocm_events[] = array(
                'trigger' => 'catalog/model/extension/module/facebook_business/getProducts/after',
                'action'  => ((VERSION > '2.2.0.0') ? 'extension/' : '') . 'module/ocm/onFbProductsAfter'
            );
        }
    }
    public function __get($name) {
        return $this->registry->get($name);
    }
    public function addEvents($events) {
        if (VERSION < '2.2.0.0') return false;
        $this->deleteEvents();
        foreach ($events as $event) {
            $this->event->addEvent($this->ocm_meta['name'], $event['trigger'], $event['action']);
        }
    }
    public function deleteEvents() {
        if (VERSION >= '3.0.0.0') {
            $this->event->deleteEventByCode($this->ocm_meta['name']);
        } else {
            $this->event->deleteEvent($this->ocm_meta['name']);
        }
    }
    public function safeDBColumnAdd($tables = array()) {
        foreach($tables as $table => $columns) {
            foreach($columns as $column) {
                if (!$this->db->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . DB_DATABASE . "' AND table_name = '" . DB_PREFIX . $table . "' and column_name='" . $column['name'] . "' LIMIT 1")->row) {
                    $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `" . $column['name'] . "` " . $column['option']); 
                }
            }
        }
    }
    public function isDBBUpdateAvail($tables = array(), $events = array()) {
        // it create issue in case of latency mijoshop so ignore
        if (defined('_JEXEC') && VERSION < '3.0.0.0') { 
            return false;
        }
        $db_status = false;
        foreach($tables as $table => $columns) {
            if(!$this->db->query("SELECT * FROM information_schema.tables WHERE table_schema = '" . DB_DATABASE . "' AND table_name = '" . DB_PREFIX . $table . "' LIMIT 1")->row) {
                $db_status = true;
                break;
            }
            foreach($columns as $column) {
                if (!$this->db->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . DB_DATABASE . "' AND table_name = '" . DB_PREFIX . $table . "' and column_name='" . $column['name'] . "' LIMIT 1")->row){
                   $db_status = true;
                   break;
                }
            }
        }
        $event_status = false;
        if (VERSION >= '2.2.0.0') {
            $rows = $this->db->query("SELECT DISTINCT `trigger` FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->ocm_meta['name'] . "'")->rows;
            $existing_events = array();
            foreach ($rows as $key => $value) {
                $existing_events[] = $value['trigger'];
            }
            $event_status = $this->isEventChanged($events, $existing_events);
        }
        /* common chores for all modules */
        // remove installed file list for OC 3.x
        if (VERSION >= '3.0.0.0') {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "extension_path` WHERE `path` LIKE 'system/library/ocm%'");
        }
        // install common event if not exist
        $is_ocm_event = !empty($this->ocm_meta['event']) && $this->ocm_meta['event'];
        if ($is_ocm_event && VERSION >= '2.2.0.0') {
            $rows = $this->db->query("SELECT `trigger` FROM `" . DB_PREFIX . "event` WHERE `code` = 'ocm'")->rows;
            $existing_events = array();
            foreach ($rows as $key => $value) {
                $existing_events[] = $value['trigger'];
            }
            if ($this->isEventChanged($this->ocm_events, $existing_events)) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'ocm'");
                foreach ($this->ocm_events as $event) {
                    $this->event->addEvent('ocm', $event['trigger'], $event['action']);
                }
            }
        }
        return array(
            'db'    => $db_status,
            'event' => $event_status
        );
    }
    public function removeDBTables($tables = array()) {
        foreach($tables as $table => $columns) {
            $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . $table . "`");
        }
    }
    private function isEventChanged($new, $old) {
        $status = false;
        if (count($new) != count($old)) {
            $status = true;
        } else {
            foreach ($new as $event) {
                if (!in_array($event['trigger'], $old)) {
                    $status = true;
                }
            }
        }
        return $status;
    }
}