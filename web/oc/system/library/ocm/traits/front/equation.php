<?php
namespace OCM\Traits\Front;
trait Equation {
    private function calculate_string($str, &$condition_status = false) {
        $__eval = function ($str) use(&$__eval) {
            $error = false;
            $div_mul = false;
            $add_sub = false;
            $result = 0;
            $str = preg_replace('/[^\d.+\-*\/()%]/i','',$str);
            $str = rtrim(trim($str, '/*+'),'-');

            /* lets first tackle parentheses */
            if ((strpos($str, '(') !== false &&  strpos($str, ')') !== false)) {
                $regex = '/\(([\d.+\-*\/]+)\)/';
                preg_match($regex, $str, $matches);
                if (isset($matches[1])) {
                 return $__eval(preg_replace($regex, $__eval($matches[1]), $str, 1));
                }
            }
            /* Remove unwanted parentheses */
            $str = str_replace(array('(',')'), '', $str);
            /* now division and multiplication */
            if (strpos($str, '/') !== false ||  strpos($str, '*') !== false || strpos($str, '%') !== false) {
                $div_mul = true;
                $operators = array('*','/', '%');
                while(!$error && $operators) {
                    $operator = array_pop($operators);
                    while($operator && strpos($str, $operator) !== false) {
                       if ($error) {
                          break;
                       }
                       $regex = '/([\d.]+)\\'.$operator.'(\-?[\d.]+)/';
                       preg_match($regex, $str, $matches);
                       if (isset($matches[1]) && isset($matches[2])) {
                              if ($operator=='%') $result = (float)$matches[1] % (float)$matches[2];
                              if ($operator=='+') $result = (float)$matches[1] + (float)$matches[2];
                              if ($operator=='-') $result = (float)$matches[1] - (float)$matches[2]; 
                              if ($operator=='*') $result = (float)$matches[1] * (float)$matches[2]; 
                              if ($operator=='/') {
                                 if ((float)$matches[2]) {
                                    $result = (float)$matches[1] / (float)$matches[2];
                                 } else {
                                    $error = true;
                                 }
                              }
                              $str = preg_replace($regex, $result, $str, 1);
                              $str = str_replace(array('++','--','-+','+-'), array('+','+','-','-'), $str);
                       } else {
                          $error = true;
                       }
                    }
                }
            }
            if (!$error && (strpos($str, '+') !== false ||  strpos($str, '-') !== false)) {
                $add_sub = true;
                preg_match_all('/([\d\.]+|[\+\-])/', $str, $matches);
                if (isset($matches[0])) {
                    $result = 0;
                    $operator = '+';
                    $tokens = $matches[0];
                    $count = count($tokens);
                    for ($i=0; $i < $count; $i++) { 
                         if ($tokens[$i] == '+' || $tokens[$i] == '-') {
                            $operator = $tokens[$i];
                        } else {
                            $result = ($operator == '+') ? ($result + (float)$tokens[$i]) : ($result - (float)$tokens[$i]);
                        }
                    }
                }
            }
            if (!$error && !$div_mul && !$add_sub) {
                $result = (float)$str;
            }
            return $error ? 0 : $result;
        };
        if (strpos($str, '?') !== false) {
            preg_match('/(.*)\?(.*):(.*)/', $str, $matches);
            if (count($matches) == 4) {
                $__is_condition_true = function ($str) use ($__eval, &$condition_status) {
                    $components = preg_split('/([&|]{1,2})/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
                    $is_success = false;
                    if (count($components) > 1) {
                        if ($components[1] == '&&' || $components[1] == '&') {
                            $is_success = true;
                        } 
                    }
                    $prev_value = false;
                    $prev_operator = '|';
                  //  print_r($components);
                    foreach ($components as $component) {
                        $component = trim($component);
                        if ($component == '&&' || $component == '&') {
                            $is_success &= $prev_value;
                            $prev_operator = '&';
                        } else if ($component == '||' || $component == '|') {
                            $is_success |= $prev_value;
                            $prev_operator = '|';
                            if ($is_success) {
                                break;
                            }
                        } else {
                            preg_match('/(.+?)([!<>=]+)(.+)/', $component, $matches);
                            if (count($matches) == 4) {
                                $left = $__eval($matches[1]);
                                $right = $__eval($matches[3]);
                                $cond = trim($matches[2]);
                                if ($cond =='===' || $cond =='==') {
                                    $prev_value = ($left == $right);
                                } else if ($cond =='!==' || $cond =='!=') {
                                    $prev_value = ($left != $right);
                                } else if ($cond =='>') {
                                    $prev_value = ($left > $right);
                                } else if ($cond =='<') {
                                    $prev_value = ($left < $right);
                                } else if ($cond =='<=') {
                                    $prev_value = ($left <= $right);
                                } else if ($cond =='>=') {
                                    $prev_value = ($left >= $right);
                                } else {
                                    $prev_value = false;
                                }
                            } else {
                                $negation = false;
                                if (substr($component, 0,1) === '!') {
                                    $negation = true;
                                    $component = trim($component, '!');
                                }
                                $prev_value = !!$component;
                                if ($negation) {
                                    $prev_value = !$prev_value;
                                }
                            }
                        }
                    }
                    $is_success = $prev_operator === '|' ? ($is_success | $prev_value) : ($is_success & $prev_value);
                    return $is_success;
                };
                $condition_status = $__is_condition_true($matches[1]);
                return $condition_status ? $__eval($matches[2]) : $__eval($matches[3]);
            } else {
                return 0;
            }
        } else {
            return $__eval($str);
        }
    }
}