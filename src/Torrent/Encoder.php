<?php
namespace BittyTorrent\Torrent;

class Encoder {
    public static function encode($data) {
        if (is_array($data)) {
            $ret = '';
            if (array_keys($data) !== range(0, count($data) - 1)) {
                $ret .= 'd';
                ksort($data);
                foreach ($data as $k => $v) {
                    $ret .= self::encode($k) . self::encode($v);
                }
                $ret .= 'e';
            } else {
                $ret .= 'l';
                foreach ($data as $v) {
                    $ret .= self::encode($v);
                }
                $ret .= 'e';
            }
            return $ret;
        } elseif (is_int($data)) {
            return 'i' . $data . 'e';
        } else {
            return strlen($data) . ':' . $data;
        }
    }
}
