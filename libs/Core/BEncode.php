<?php

namespace App\Core;

class BEncode {
	// Dictionary keys must be sorted. foreach tends to iterate over the order
	// the array was made, so we make a new one in sorted order. :)
	public function makeSorted(array $array): array {
		// Shouldn't happen!
		if (empty($array))
			return $array;
		$i = 0;
        $keys = [];
		foreach($array as $key => $dummy)
			$keys[$i++] = stripslashes((string)$key);
		sort($keys);

        $return = [];
		for ($i=0; isset($keys[$i]); $i++)
			$return[addslashes($keys[$i])] = $array[addslashes($keys[$i])];
		return $return;
	}

	// Encodes strings, integers and empty dictionaries.
	// $unstrip is set to true when decoding dictionary keys
	public function encodeEntry(mixed $entry, string &$fd, bool $unstrip = false): void {
		if (is_bool($entry)) {
			$fd .= 'de';
			return;
		}
		if (is_int($entry) || is_float($entry)) {
			$fd .= 'i'.$entry.'e';
			return;
		}
		if ($unstrip)
			$myentry = stripslashes((string)$entry);
		else
			$myentry = (string)$entry;
		$length = strlen($myentry);
		$fd .= $length.':'.$myentry;
	}

	// Encodes lists
	public function encodeList(array $array, string &$fd): void {
		$fd .= 'l';
		// The empty list is defined as array();
		if (empty($array)) {
			$fd .= 'e';
			return;
		}
		for ($i = 0; isset($array[$i]); $i++)
			$this->decideEncode($array[$i], $fd);
		$fd .= 'e';
	}

	// Passes lists and dictionaries accordingly, and has encodeEntry handle
	// the strings and integers.
	public function decideEncode(mixed $unknown, string &$fd): void {
		if (is_array($unknown)) {
			if (isset($unknown[0]) || empty($unknown))
				$this->encodeList($unknown, $fd);
			else
				$this->encodeDict($unknown, $fd);
            return;
		}
		$this->encodeEntry($unknown, $fd);
	}

	// Encodes dictionaries
	public function encodeDict(mixed $array, string &$fd): void {
		$fd .= 'd';
		if (is_bool($array)) {
			$fd .= 'e';
			return;
		}
        if (!is_array($array)) {
            // Fallback for unexpected type in dictionary context?
            // Legacy code logic: if (is_bool($array))...
            // If it reaches here and not array, likely unexpected.
            return;
        }

		// NEED TO SORT!
		$newarray = $this->makeSorted($array);
		foreach($newarray as $left => $right) {
			$this->encodeEntry($left, $fd, true);
			$this->decideEncode($right, $fd);
		}
		$fd .= 'e';
	}
}

// Use this function in your own code.
// Making this a global function for compatibility if needed, or helper
if (!function_exists('BEncode')) {
    function BEncode(mixed $array): string {
        $string = '';
        $encoder = new BEncode;
        $encoder->decideEncode($array, $string);
        return $string;
    }
}
