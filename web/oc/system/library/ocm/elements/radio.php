<?php
namespace OCM\Elements;
final class Radio extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '';
        foreach ($params['options'] as $option) {
            $checked = $option['value'] == $params['preset'] ? '{checked}' : '';
            $element .= '<label class="radio-inline">
                            <input type="radio" ocm-on="' . $params['id'] . '" name="{name}" value="'.$option['value'].'" ' . $checked . ' />&nbsp; '. $option['name'] .
                        '</label>';
        }
        return $element;
    }
}

