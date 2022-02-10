<?php
namespace OCM;
use OCM\Elements\Bare;
use OCM\Elements\Input;
use OCM\Elements\Textarea;
use OCM\Elements\Checkbox;
use OCM\Elements\Radio;
use OCM\Elements\Select;
use OCM\Elements\Checkgroup;
use OCM\Elements\Autofill;
use OCM\Elements\Range;
use OCM\Elements\Datetime;
use OCM\Elements\Checkrow;
use OCM\Elements\Radiorow;
use OCM\Elements\Inputgroup;
use OCM\Elements\Image;
final class Form {
    private $registry;
    private $bare;
    private $input;
    private $textarea;
    private $select;
    private $checkbox;
    private $checkrow;
    private $checkgroup;
    private $radio;
    private $radiorow;
    private $range;
    private $datetime;
    private $inputgroup;
    private $image;
    private $autofill;
    private $lang_in_elems = array(
        'text_for_all',
        'text_checked_all',
        'text_unchecked_all',
        'text_checked_show',
        'text_free_search',
        'text_batch_select',
        'text_remove_all',
        'text_start',
        'text_end',
        'text_help',
        'text_ocm_inclusive',
        'text_ocm_exclusive',
        'text_ocm_mode'
    );
    public function __construct($registry) {
        $this->registry = $registry;
        $this->langs    = array();
        $this->values   = array();
        $this->options  = array();
        $this->params = array();
        $this->basename = '';
        $this->basenameType = 'array';
        $this->id = '';

        $this->bare = new Bare();
        $this->input = new Input();
        $this->textarea = new Textarea();
        $this->select = new Select();
        $this->checkbox = new Checkbox();
        $this->checkrow  = new Checkrow();
        $this->checkgroup  = new Checkgroup();
        $this->radio  = new Radio();
        $this->radiorow  = new Radiorow();
        $this->range  = new Range();
        $this->datetime  = new Datetime();
        $this->inputgroup  = new Inputgroup();
        $this->image  = new Image();
        $this->autofill  = new Autofill();
    }
    public function __get($name) {
        return $this->registry->get($name);
    }
    public function setParam($key, $value) {
        $this->params[$key] = $value;
    }
    public function setBasename($basename, $type = 'array') {
        $this->basename = $basename;
        $this->basenameType = $type;
        return $this;
    }
    public function setLangs($langs) {
        $this->langs = $langs;
        return $this;
    }
    /* Old value for retension data */
    public function setPreset($preset) {
        $this->preset = $preset;
        return $this;
    }
    public function setIDPostfix($postfix) {
        $this->id = $postfix;
        return $this;
    }
    public function setOptions($options) {
        $this->options = $options;
        return $this;
    }
    public function getOptions($options, $id_key) {
        $return = array();
        if (!is_array($options)) {
            echo 'Error: ' . $options . ' is not an array'; 
            return $return;
        }
        foreach ($options as $key => $option) {
            $name = $id_key == 'none' ? $option : (isset($option['name']) ? $option['name'] : (isset($option['label']) ? $option['label'] : $option['title']));
            $return[] = array(
                'value' => $id_key == 'none' ? $key : $option[$id_key],
                'name'  => $name
            );
        }
        return $return;
    }
    public function getFrom($array) {
        $return = '';
        foreach ($array as $type => $params) {
            $return .= $this->get($type, $params);
        }
        return $return;
    }
    public function get($type, $params) {
        if (!is_array($params)) {
            $params = array('name' => $params);
        }
        $params = $this->preRender($type, $params);
        $return = $this->{$type}->get($params);
        return $this->postRender($return);
    }
    private function preRender($type, $params) {
        $plain_name = preg_replace('/\[\w*\]/', '', $params['name']);
        $params['plain_name'] = ($this->basenameType == 'prefix' ? $this->basename : '') . $plain_name;
        if (preg_match_all('/\[(\w+)\]/', $params['name'], $matches, PREG_SET_ORDER)) {
            $keys = array();
            for ($i=0; $i < count($matches) ; $i++) {
                $key = $matches[$i][1];
                $keys[] = $key;
                if (!is_numeric($key)) {
                    $plain_name .= '_' . $key;
                }
            }
            $params['keys'] = $keys;
        }
        
        $title_key = 'entry_' . $plain_name;
        $help_key  = 'help_' . $plain_name;
        $more_key  = 'more_' . $plain_name;
        $placeholder_key  = 'placeholder_' . $plain_name;

        if (!isset($params['title'])) {
            $params['title'] = $this->langs[$title_key];
        }
        if (!isset($params['help']) && isset($this->langs[$help_key])) {
            $params['help'] = $this->langs[$help_key];
        }
        if (isset($this->langs[$more_key])) {
            $params['more'] = $this->langs[$more_key];
        }
        if (isset($this->langs[$placeholder_key])) {
            $params['placeholder'] = $this->langs[$placeholder_key];
        }
        if (!isset($params['all'])) {
            $params['all'] = true;
        }
        if (!isset($params['options']) || !is_array($params['options'])) {
            $params['options'] = isset($this->options[$plain_name]) ? $this->options[$plain_name] : array();
        }
        if (!isset($params['type'])) {
            $params['type'] = 'text';
        }
        if ($type == 'bare') {
            $params['preset'] = '';
        }
        $params['id'] = $plain_name;
        //set additional params
        foreach ($this->params as $key => $value) {
            if (!isset($params[$key])) { //Don't overwrite so set if not yet set
                $params[$key] = $value;
            }
        }
        /* Set old value for retension */
        $params = $this->{$type}->setVal($params, $this->preset);
        return $params;
    }
    private function postRender($return) {
        $placeholder = array();
        $replacer    = array();
        foreach ($this->lang_in_elems as $key) {
           $placeholder[] = '{' . $key . '}';
           $replacer[] = isset($this->langs[$key]) ? $this->langs[$key] : '';
        }
        $return = str_replace($placeholder, $replacer, $return);

        if ($this->basenameType == 'array') {
            $return = preg_replace('/!!(\w+)!!/', $this->basename . '[$1]', $return);
        } else {
            $return = preg_replace('/!!(\w+)!!/', $this->basename . '$1', $return);
        }
        $return = str_replace('ID_POSTFIX', $this->id, $return);
        //reset local params
        $this->params = array();
        return $return;
    }
}