<?php
namespace OCM\Elements;
final class Datetime extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    private function getElement($params) {
        $format = 'YYYY-MM-DD HH:mm';
        if (isset($params['date']) && $params['date']) {
            $format = 'YYYY-MM-DD';
        }
        if (isset($params['time']) && $params['time']) {
            $format = 'HH:mm';
        }
        $element = '<div class="input-group date">'
                .'<input type="text" name="{name}" value="{preset}" placeholder="{placeholder}" class="form-control ' . $params['plain_name'] . '" autocomplete="off" data-date-format="'. $format . '" id="{id}" />'
                .'<div class="input-group-append input-group-addon">'
                   .'<div class="input-group-text"><i class="fa fas fa-calendar"></i></div>'
                .'</div>'
            .'</div>';
        return $element;
    }
}