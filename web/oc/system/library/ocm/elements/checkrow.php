<?php
namespace OCM\Elements;
final class Checkrow extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        if (!is_array($params['preset'])) $params['preset'] = array();
        $layout = isset($params['layout']) && $params['layout'] ? $params['layout'] : 'vertical';
        $element = '';
        foreach ($params['options'] as $option) {
            $checked = in_array($option['value'], $params['preset']) ? '{checked}' : '';
            $class = $layout === 'horizontal' ? 'class="checkbox-inline"' : '';
            if ($layout === 'vertical') {
                $element .= '<div class="checkbox">';
            }
            $element .= '  <label '.$class.'>
                                <input type="checkbox" ocm-on="' . $params['id'] . '" name="{name}" value="'.$option['value'].'" ' . $checked . ' />&nbsp; '. $option['name'] .
                            '</label>';
            if ($layout === 'vertical') {
                $element .= ' </div>';
            }
        }
        return $element;
    }
}

