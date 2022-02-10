<?php
namespace OCM\Elements;
final class Autofill extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $element = '<input type="text" value="" attr="' . $params['attr'] . '" placeholder="{text_free_search}" id="{id}" class="form-control ocm-autofill" />';
        $element .= '<div name="{name}" class="well well-sm form-control ocm-autofill-box" style="height: 150px; overflow: auto;">';
        foreach ($params['options'] as $option) {
            $element .= '<div value="' . $option['value'] . '" class="ocm-autofill-item"><i class="fa fas fa-minus-circle"></i> '. $option['name'] . '<input type="hidden" name="{name}" value="' . $option['value'] . '" /></div>';
        }
        $element .= '</div>';

        $element .= '<div class="ocm-autofill-option">';
        if (isset($params['browser'])) {
            $element .=  '<a rel="'.$params['browser'].'" name="{name}" href="#" class="ocm-browser">{text_batch_select}</a>';
        }
        $element .=  '<a href="#" class="ocm-remove-all">{text_remove_all}</a>';
        $element .= '</div>';
        return $element;
    }
}