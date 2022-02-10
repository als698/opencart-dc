<?php
namespace OCM\Elements;
final class Textarea extends Base {
    public function get($params) {
       $params['element'] = $this->getElement();
       return $this->render($params);
    }
    private function getElement() {
        $element = '<textarea class="form-control" name="{name}" id="{id}" placeholder="{placeholder}" rows="{rows}" cols="70">{preset}</textarea>';
        return $element;
    }
}