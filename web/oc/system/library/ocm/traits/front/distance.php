<?php
namespace OCM\Traits\Front;
trait Distance {
    private function getDistance($src, $dest, $key, $debug = false) {
        if (!isset($this->session->data['xmap_cache'])) {
            $this->session->data['xmap_cache'] = array();
        }
        $crc32 = crc32($dest);
        if (isset($this->session->data['xmap_cache'][$crc32])) {
            return $this->session->data['xmap_cache'][$crc32];
        }
        $distance = 0;
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins='.rawurlencode($src).'&destinations='.rawurlencode($dest).'&key=' . $key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        if (is_array($response) && $response['status'] == 'OK' && $response['rows']) {
            $distance = isset($response['rows'][0]['elements'][0]['distance']) ? ($response['rows'][0]['elements'][0]['distance']['value'] / 1000) : 0;
        }

        if ($debug) {
            $this->log->write('Map API (URL: '.$url.') (Distance: '.$distance.') and Response: '. print_r($response, true));
        }
        $this->session->data['xmap_cache'][$crc32] = $distance;
        return $distance;
    }
}