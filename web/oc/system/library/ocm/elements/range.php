<?php
namespace OCM\Elements;
final class Range extends Base {
    public function get($params) {
       $params['element'] = $this->getElement($params);
       return $this->render($params);
    }
    /*Overwrite parent method as this is special */
    public function setVal($params, $data) {
        $params['preset_start'] = $data[$params['plain_name'] . '_start'];
        $params['preset_end'] = $data[$params['plain_name'] . '_end'];
        return $params;
    }
    private function getElement($params) {
        if ($params['type'] == 'time') {
            $range = $this->getTime($params);
        } else if ($params['type'] == 'date') {
            $range = $this->getDate($params);
        } else {
            $range = $this->getInput($params);
        }

        $element = '<div class="row">';
        $element .= '  <div class="col-sm-4">';
        $element .= $range['start'];
        $element .= '  </div>';
        $element .= '  <div class="col-sm-4">';
        $element .= $range['end'];
        $element .= '  </div>';
        $element .= '</div>';
        return $element;
    }

    private function getInput($params) {
        $start = '<input type="{type}" placeholder="{text_start}" id="{id}_start" name="' . $this->getSymbolicName($params['name'].'_start'). '" value="' . $params['preset_start'] . '" class="form-control"/>';
        $end = '<input type="{type}" placeholder="{text_end}" id="{id}_end" name="' . $this->getSymbolicName($params['name'].'_end'). '" value="' . $params['preset_end'] . '" class="form-control"/>';
        return array(
            'start' => $start,
            'end' => $end
        );
    }

    private function getTime($params) {
        $start = '<select id="{id}_start" class="form-control" name="' . $this->getSymbolicName($params['name'].'_start'). '">';
        $start .= '<option value="">{text_for_all}</option>';
        for($i = 0; $i <= 23; $i++) { 
            $selected = ($params['preset_start'] == $i && $params['preset_start'] != '') ? '{selected}' : '';
            $start .= '<option ' . $selected . ' value="' . $i . '">'.date("h:i A", strtotime("$i:00")).'</option>';
        } 
        $start .= '</select>';

        $end = '<select id="{id}_end" class="form-control" name="' . $this->getSymbolicName($params['name'].'_end'). '">';
        $end .= '<option value="">{text_for_all}</option>';
        for($i = 0; $i <= 23; $i++) { 
            $selected = ($params['preset_end'] == $i && $params['preset_end'] != '') ? '{selected}' : '';
            $end .= '<option ' . $selected . ' value="' . $i . '">'.date("h:i A", strtotime("$i:00")).'</option>';
        } 
        $end .='</select>';
        return array(
            'start' => $start,
            'end' => $end
        );
    }
    private function getDate($params) {
        $start = '<div class="input-group date">'
                .'<input type="text" name="' . $this->getSymbolicName($params['name'].'_start'). '" value="' . $params['preset_start'] . '" placeholder="{text_start}" data-date-format="YYYY-MM-DD" class="form-control" />'
                .'<div class="input-group-append input-group-addon">'
                   .'<div class="input-group-text"><i class="fa fas fa-calendar"></i></div>'
                .'</div>'
            .'</div>';

        $end = '<div class="input-group date">'
                .'<input type="text" name="' . $this->getSymbolicName($params['name'].'_end'). '" value="' . $params['preset_end'] . '" placeholder="{text_end}" data-date-format="YYYY-MM-DD" class="form-control" />'
                .'<div class="input-group-append input-group-addon">'
                   .'<div class="input-group-text"><i class="fa fas fa-calendar"></i></div>'
                .'</div>'
            .'</div>';
        return array(
            'start' => $start,
            'end' => $end
        );
    }
}