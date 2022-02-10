<?php
namespace OCM\Elements;
final class Bare extends Base {
    /* Provide element keyword via params*/
    public function get($params) {
       return $this->render($params);
    }
}