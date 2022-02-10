<?php
namespace OCM\Elements;
final class Select extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '<select ocm-on="' . $params['id'] . '" name="{name}" class="form-control" id="{id}">';
        foreach ($params['options'] as $option) {
            $selected = $option['value'] == $params['preset'] ? '{selected}' : '';
            $element .= '<option ' . $selected . ' value="' . $option['value'] . '">' . $option['name'] . '</option>';
        }
        $element .= '</select>';
        return $element;
    }
}