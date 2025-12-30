<?php
namespace BittyTorrent\Torrent;

class Decoder {
    private $data;
    private $len;
    private $pos;

    public function __construct($file) {
        if (file_exists($file)) {
            $this->data = file_get_contents($file);
            $this->len = strlen($this->data);
            $this->pos = 0;
        }
    }

    public function decode() {
        if (!$this->data) return null;
        try {
            return $this->decodeEntry();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function decodeEntry() {
        if ($this->pos >= $this->len) return null;
        $char = $this->data[$this->pos];
        if ($char === 'd') {
            $this->pos++;
            $dict = [];
            while ($this->data[$this->pos] !== 'e') {
                $key = $this->decodeEntry();
                $val = $this->decodeEntry();
                if ($key === null || $val === null) break;
                $dict[$key] = $val;
            }
            $this->pos++;
            return $dict;
        } elseif ($char === 'l') {
            $this->pos++;
            $list = [];
            while ($this->data[$this->pos] !== 'e') {
                $val = $this->decodeEntry();
                if ($val === null) break;
                $list[] = $val;
            }
            $this->pos++;
            return $list;
        } elseif ($char === 'i') {
            $this->pos++;
            $end = strpos($this->data, 'e', $this->pos);
            $int = substr($this->data, $this->pos, $end - $this->pos);
            $this->pos = $end + 1;
            return (int)$int;
        } elseif (ctype_digit($char)) {
            $colon = strpos($this->data, ':', $this->pos);
            $len = (int)substr($this->data, $this->pos, $colon - $this->pos);
            $str = substr($this->data, $colon + 1, $len);
            $this->pos = $colon + 1 + $len;
            return $str;
        }
        return null;
    }
}
