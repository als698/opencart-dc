<?php
namespace OCM\Elements;
final class Inputgroup extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        if (!isset($params['attrs'])) $params['attrs'] = '';
        $element = '<div class="input-group xform-' . $params['type'] . '">';
        if (isset($params['addon']) && $params['addon']) {
            $element .= ' <div class="input-group-addon">'.$params['addon'].'</div>';
        }

        $element .= ' <input type="text" name="{name}" value="{preset}" placeholder="{placeholder}" class="form-control ' . $params['plain_name'] .'" ' . $params['attrs'] . ' id="{id}" " />';
        if (isset($params['button']) && $params['button']) {
            $element .= '<span class="input-group-btn">
                            <button type="button" class="btn btn-default">'.$params['button'].'</button>
                         </span>';
        }
        $element .= '</div>';
        return $element;
    }
}