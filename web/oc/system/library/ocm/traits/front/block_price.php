<?php
namespace OCM\Traits\Front;
trait Block_price {
    private function getPrice($rates, $target_value, $percent_of) {
        $ranges = $rates['ranges'];
        $status = false;
        $cost = 0;
        $block = 0;
        $end = 0;
        $cumulative = 0;
        $target_value = round($target_value, 8);
        foreach($ranges as $range) {
            $start = $range['start'];
            $end = $range['end'];
            if ($start && !$end) {
                $end = PHP_INT_MAX;
            }
            $cost = $range['percent'] ? ($range['value'] * $percent_of) : $range['value'];
            if ($start <= $target_value && $target_value <= $end) {
                $status = true; 
                $end = $target_value;
            }
            $block = $range['block'];
            $partial = $range['partial'];
            if ($block > 0) {
                //incorrect block seting, reset its value to 1
                if ($block >= $end) {
                    $block = 1;
                }
                /* round to complete block for iteration purpose. 
                  For negetive value, round to previous round and for positive value round to next round.
                */
                if (!$partial) {
                    if(is_float($end) && fmod($end, $block) != 0) {
                        $end = $cost < 0 ? ($end - fmod($end, $block)) : ($end - fmod($end, $block)) + $block;
                    }
                    else if($block >= 1 && ($end % $block) != 0) {
                       $end =  $cost < 0 ? ($end - ($end % $block)) : ($end - ($end % $block)) + $block; 
                    }
                }
                $no_of_blocks = 0;
                if ($start == 0 && !$partial && $block >= 1) {
                    $start = 1;
                }
                while($start <= $end) {
                    if ($partial) {
                        $no_of_blocks =  ($end-$start) >= $block ? ($no_of_blocks + 1) : ($no_of_blocks + ($end - $start) / $block);
                    } else {
                        $no_of_blocks++;
                    }
                    $start += $block;
                    //todo optimize, adjust no_of_block when block is less than 1
                    if (!$partial && $block < 1 && $start > $end) {
                        $no_of_blocks--;
                    }
                }
                $cost = ($no_of_blocks * $cost);
            }
            $cumulative += $cost;
            if ($status) break;
        }
         /* if not found and additional price was set */
        if (!$status && !empty($rates['additional']) && $rates['additional']['max'] >= $target_value) {
            $additional = $rates['additional']['percent'] ? ($rates['additional']['value'] * $percent_of) : $rates['additional']['value'];
            $additional_per = $rates['additional']['block'];
            while($end < $target_value) {
                $cost += $additional;
                $cumulative += $additional;
                $end += $additional_per;
            }
            $status = true;
        }
        return array(
            'cost'        => $status ? $cost : 0,
            'cumulative'  => $cumulative,
            'status'      => $status
        );
    }
}