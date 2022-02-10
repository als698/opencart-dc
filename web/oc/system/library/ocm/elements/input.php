<?php
namespace OCM\Elements;
final class Input extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '<input type="{type}" name="{name}" value="{preset}" placeholder="{placeholder}" class="form-control ' . $params['plain_name'] . '" id="{id}" />';
        return $element;
    }
}