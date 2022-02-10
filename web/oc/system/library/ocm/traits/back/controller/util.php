<?php
namespace OCM\Traits\Back\Controller;
trait Util {
    public function fetchDebug() {
        $json = array();
        $json['log'] = $this->ocm->getLog();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    private function getPlaceholderList($placeholders, $wrap_input = false, $width = '') {
        $return = '<b>Available Placeholders:</b><br><table class="table table-bordered table-hover">';
        foreach ($placeholders as $key => $value) {
            if ($wrap_input) {
                $key = '<input readonly type="text" value="' . $key . '" class="ocm-placeholder" />';
            }
            $return .= ' <tr>
                            <td ' . ($width ? 'width="'.$width.'"' : '') . ' class="text-left">' . $key . '</td>
                            <td>'.$value.'</td>
                        </tr>';
        }
        $return .= '</table>';
        return $return;
    }
    private function forceDownload($content, $filename, $type = 'text/x-csv') {
        $filename = preg_replace('/[^a-zA-Z0-9_\.]/', '', $filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Length: " . strlen($content));
        header("Content-type: " . $type);
        header("Content-Disposition: attachment; filename= $filename");
        echo $content;
        exit;
    }
    private function getMethodList($method_data, $untitled = 'Untitled Item') {
        $language_id = $this->config->get('config_language_id');
        $return = array();
        foreach($method_data as $single_method) {
            $no_of_tab = $single_method['tab_id'];
            $method_data = $single_method['method_data'];
            if (!isset($method_data['display']) || !$method_data['display']) $method_data['display'] = isset($method_data['name'][$language_id]) ? $method_data['name'][$language_id] : $untitled;
            $return[$no_of_tab] = $method_data['display'];
        }
        return $return;
    }
    private function setDefault($method_data, $fields) {
        foreach ($fields as $key => $default) {
            if (is_array($default) && $default) {
                $is_assoc = false;
                foreach ($default as $_key => $_value) {
                    $is_assoc = is_string($_key);
                    break;
                }
                if ($is_assoc) {
                    if (!isset($method_data[$key])) {
                        $method_data[$key] = array();
                    }
                    $method_data[$key] = $this->setDefault($method_data[$key], $default);
                    continue;
                }
            }
            if (!isset($method_data[$key])) {
                $method_data[$key] = $default;
            }
        }
        return $method_data;
    }
    private function setDefaultByLangs($method_data, $languages, $fields) {
        foreach ($languages as $language) {
            $language_id = $language['language_id'];
            foreach ($fields as $key => $default) {
                if (is_array($default) && $default) {
                    if (!isset($method_data[$key])) {
                        $method_data[$key] = array();
                    }
                    $method_data[$key] = $this->setDefaultByLangs($method_data[$key], $languages, $default);
                    continue;
                }
                if (empty($method_data[$key][$language_id])) {
                    $method_data[$key][$language_id] = $default;
                }
            }
        }
        return $method_data;
    }
    private function resetEmptyAll($method_data, $fields_all = array()) {
        foreach ($fields_all as $key => $value) {
            if (empty($method_data[$key]) && empty($method_data[$value])) {
                $method_data[$value] = 1;
            }
        }
        return $method_data;
    }
    private function getLangField($method_data, $key = 'name', $default = 'Untitled Item') {
        $language_id = $this->config->get('config_language_id');
        return isset($method_data[$key][$language_id]) ? $method_data[$key][$language_id] : $default;
    }
}