<?php
namespace OCM\Elements;
final class Radiorow extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '';
        $layout = isset($params['layout']) && $params['layout'] ? $params['layout'] : 'vertical';
        foreach ($params['options'] as $option) {
            $checked = $option['value'] == $params['preset'] ? '{checked}' : '';
            $class = $layout === 'horizontal' ? 'class="radio-inline"' : '';
            if ($layout === 'vertical') {
                $element .= '<div class="radio">';
            }
            $element .= '<label '.$class.'>
                                <input type="radio" ocm-on="' . $params['id'] . '" name="{name}" value="'.$option['value'].'" ' . $checked . ' />&nbsp; '. $option['name'] .
                            '</label>';
            if ($layout === 'vertical') {
                $element .= ' </div>';
            }
        }
        return $element;
    }
}