<?php
namespace OCM\Elements;
class Base {
    public function __construct() {
        $this->params = array();
        $this->total_col = 12;
        $this->props = array('element', 'title', 'label', 'name', 'placeholder', 'id', 'type', 'preset', 'value', 'style', 'help', 'required', 'label_col', 'checked', 'selected', 'visible', 'class', 'attr', 'more', 'rows');
    }
    private function sanitize() {
        $this->reset();
        $this->params['id'] .= 'ID_POSTFIX';
        if (!$this->params['placeholder']) {
            $this->params['placeholder'] = $this->params['title'];
        }
        if (!is_numeric($this->params['label_col'])) {
            $this->params['label_col'] = 3;
        }
        if (!$this->params['rows']) {
            $this->params['rows'] = 6;
        }
        if ($this->params['class']) {
            $this->params['class'] = ' ' . $this->params['class'];
        }
        if ($this->params['attr']) {
            $this->params['attr'] = 'ocm-attr="' . $this->params['attr'] . '"'; 
        }
        if ($this->params['more']) {
            $this->params['more'] = '<div class="ocm-more"><a href="#" rel="'.$this->params['plain_name'].'">{text_help}</a><div class="ocm-more-container"></div></div>';
        }
        $this->params['name'] = $this->getSymbolicName($this->params['name']);
        $this->params['required'] = $this->params['required'] ? ' required' : '';
        $this->params['checked'] = 'checked="checked"';
        $this->params['selected'] = 'selected="selected"';
        if ($this->params['visible']) {
            $this->params['visible'] = ' ocm-visible';
        }
    }
    private function reset() {
        foreach ($this->props as $keyword) {
            if (!isset($this->params[$keyword])) {
                $this->params[$keyword] = '';
            }
        }
    }
    public function getSymbolicName($name) {
        return preg_replace('/(^|\[|$)/', '!!$1', $name, 2);
    }
    public function setVal($params, $data) {
        if (!isset($params['preset'])) {
            $params['preset'] = isset($data[$params['plain_name']]) ? $data[$params['plain_name']] : '';
            if (is_array($params['preset']) && isset($params['keys']) && is_array($params['keys'])) {
                $params['preset'] = $this->getValIn($params['name'], $params['preset'], $params['keys']);
            }
        }
        return $params;
    }
    public function getValIn($name, $value, $keys) {
        $max = count($keys) - 1;
        for ($i=0; $i <= $max; $i++) {
            $key = $keys[$i];
            $value = isset($value[$key]) ? $value[$key] : ($i == $max ? '' : array());
        }
        return $value;
    }
    public function getRow() {
        $right_col = ($this->total_col - $this->params['label_col']);
        if ($right_col > 0 && $right_col < 12) {
            $row  = '<div class="form-group row{required}{class}{visible}" {attr}>';
            $row .= $this->getLevel($this->params);
            $row .=   '<div class="col-sm-' . $right_col . '">';
            $row .=       '{element}';
            $row .=       '{more}';
            $row .=    '</div>';
            $row .= '</div>';
        } else {
            $row  = '<div class="ocm-full-row{required}{class}{visible}" {attr}>';
            if ($this->params['label_col'] > 0) {
                $row .=   $this->getLevel($this->params);
            }
            $row .=  '{element}';
            $row .=  '{more}';
            $row .= '</div>';
        }
        return $row;
    }
    public function getLevel() {
        $col_class = $this->params['label_col'] < 12 ? 'col-sm-' . $this->params['label_col'] : 'ocm-full-label';
        $level  = '<label class="'.$col_class.' control-label col-form-label" {style} for="{id}">';
        if ($this->params['help']) {
            $level .= '<span data-toggle="tooltip" data-html="true" title="{help}">{title}</span>';
        } else {
            $level .= '{title}';
        }
        $level .= '</label>';
        return $level;
    }
    public function render($params) {
        $this->params = $params;
        $this->sanitize();
        $markup = (isset($params['row']) && $params['row']) ? $params['row'] : $this->getRow();
        $placeholder = array();
        $replacer = array();
        foreach ($this->props as $attr) {
           if (is_array($this->params[$attr])) {
                continue;
           }
           $placeholder[] = '{' . $attr . '}';
           $replacer[] = $this->params[$attr];
        }
        return str_replace($placeholder, $replacer, $markup);
    }
}