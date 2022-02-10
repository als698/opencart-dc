<?php
namespace OCM\Elements;
final class Image extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '<a href="" id="thumb-{id}" data-toggle="image" class="img-thumbnail"><img src="' . $params['thumb'] . '" alt="" title="" data-placeholder="' . $params['placeholder'] . '" /></a>';
        $element .= '<input type="hidden" name="{name}" value="{preset}" id="input-{id}" />';
        return $element;
    }
}