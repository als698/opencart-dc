<?php
namespace OCM\Elements;
final class Checkbox extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    /*Overwritign parent method and setting default value to 1  */
    public function setVal($params, $data) {
        $params = parent::setVal($params, $data);
        /* Default checkbox value 1 */
        if (!isset($params['value'])) {
            $params['value'] = 1;
        }
        return $params;
    }
    private function getElement($params) {
        $checked = $params['value'] == $params['preset'] ? '{checked}' : '';
        $element = '<label class="checkbox-inline">
                    <input type="checkbox" ocm-on="' . $params['id'] . '" id="{id}" name="{name}" value="{value}" ' . $checked . ' />&nbsp;{label}
                </label>';
        return $element;
    }
}