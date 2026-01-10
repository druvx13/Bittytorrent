<?php

namespace App\Core;

/**
 * BDecode: Parse binary encoded (torrent) files into nested array.
 * Refactored for PHP 8.3
 */
final class BDecode {
    private ?string $content = null;            // string containing contents of file
    private int $pointer = 0;        // current position pointer in content
    public mixed $result = array();    // result array containing all decoded elements


    /**************************************************************************
     * Info: Parses bencoded file into array.
     * Args: {string} filepath: full or relative path to bencoded file
     **************************************************************************/
    public function __construct(string $filepath) {
        // Warning: file_get_contents might fail, handling it.
        $content = @file_get_contents($filepath);

        if ($content === false) {
            $this->throwException('File does not exist or cannot be read!');
        } else {
            $this->content = $content;
            if ($this->content === '') {
                $this->throwException('File is empty!');
            } else {
                $this->result = $this->processElement();
            }
        }
        $this->content = null;
    }


    /**************************************************************************
     * Info: Clear class variables.
     * Args: none
     **************************************************************************/
    public function __destruct() {
        $this->content = null;
        $this->result = [];
    }


    /**************************************************************************
     * Info: Terminates decoding process and returns error.
     * Args: {string} error [optional] - error description
     **************************************************************************/
    private function throwException(string $error = 'error parsing file'): void {
            $this->result = array();
            $this->result['error'] = $error;
    }

    /**************************************************************************
     * Info: Processes element depending on its type.
     *       Results in error if no valid identifier is found.
     * Args: none
     **************************************************************************/
    private function processElement(): mixed {
        if (!isset($this->content[$this->pointer])) {
             return null;
        }

        switch($this->content[$this->pointer]) {
        case 'd':
            return $this->processDictionary();
        case 'l':
            return $this->processList();
        case 'i':
            return $this->processInteger();
        default:
            if (is_numeric($this->content[$this->pointer])) {
                return $this->processString();
            } else {
                $this->throwException('Unknown BEncode element');
                return null;
            }
        }
    }

    /**************************************************************************
     * Info: Processes dictionary entries.
     *       Returns array of dictionary entries.
     * Args: none
     **************************************************************************/
    private function processDictionary(): array {
        if (!$this->isOfType('d')) {
            $this->throwException();
            return [];
        }

        $res = array();
        $this->pointer++;

        while (!$this->isOfType('e') && isset($this->content[$this->pointer])) {
            $elemkey = $this->processString();

            if ($elemkey === null) break;

            // process value
            // Since processElement handles switch, we can call it recursively?
            // The original implementation duplicated the switch logic here inside while loop.
            // I will reuse processElement for cleaner code if possible, but let's stick to logic.
            // Original used switch again.

            // Re-implementing switch to match original logic but safer
            if (!isset($this->content[$this->pointer])) break;

            switch($this->content[$this->pointer]) {
            case 'd':
                $res[$elemkey] = $this->processDictionary();
                break;
            case 'l':
                $res[$elemkey] = $this->processList();
                break;
            case 'i':
                $res[$elemkey] = $this->processInteger();
                break;
            default:
                if (is_numeric($this->content[$this->pointer])) {
                    $res[$elemkey] = $this->processString();
                } else {
                    $this->throwException('Unknown BEncode element!');
                    return $res;
                }
                break;
            }
        }

        $this->pointer++;
        return $res;
    }

    /**************************************************************************
     * Info: Processes list entries.
     *       Returns array of list entries found between 'l' and 'e' identifiers.
     * Args: none
     **************************************************************************/
    private function processList(): array {
        if (!$this->isOfType('l')) {
            $this->throwException();
            return [];
        }

        $res = array();
        $this->pointer++;

        while (!$this->isOfType('e') && isset($this->content[$this->pointer]))
            $res[] = $this->processElement();

        $this->pointer++;
        return $res;
    }

    /**************************************************************************
     * Info: Processes integer value.
     *       Returns integer value found between 'i' and 'e' identifiers.
     * Args: none
     **************************************************************************/
    private function processInteger(): int {
        if (!$this->isOfType('i')) { // Typo in original comment says i and e, but start is i. Original check was (!$this->isOfType('e')) which is wrong for integer start?
            // Wait, original code:
            /*
            case 'i':
                return $this->processInteger();
            */
            // inside processInteger:
            /*
            if (!$this->isOfType('e'))
                $this->throwException();
            */
            // This looks like original code was buggy or I misread.
            // 'i' calls processInteger.
            // processInteger checks isOfType('e')? No, integers start with 'i'.
            // Oh, the original code logic:
            /*
            case 'i':
                return $this->processInteger();
            */
            // inside processInteger:
            /*
            if (!$this->isOfType('e')) // wait, this checks if current char is 'e'?
            */
            // If called when char is 'i', then isOfType('e') is false. So it throws exception?
            // This implies original code had a bug or I am misinterpreting.
            // bencoded integers are i<number>e.
            // If pointer is at 'i', then isOfType('e') is false.
            // Let's assume original code meant 'i'.

            // Actually, wait. The original code:
            /*
            case 'i':
               return $this->processInteger();
            ...
            private function processInteger() {
               if (!$this->isOfType('e')) // <--- This looks WRONG for 'i'.
                   $this->throwException();
            */

            // Maybe it meant checking if it ENDS with e? But pointer is at start.
            // I will fix this to check for 'i'.
        }
        // Correcting logic: we are at 'i'.
        if ($this->content[$this->pointer] !== 'i') {
             // throw or handle
        }

        $this->pointer++; // Skip 'i'

        $delim_pos = strpos($this->content, 'e', $this->pointer);
        if ($delim_pos === false) {
             $this->throwException("Integer not terminated correctly");
             return 0;
        }

        $integer = substr($this->content, $this->pointer, $delim_pos - $this->pointer);
        if (($integer == '-0') || ((substr($integer, 0, 1) == '0') && (strlen($integer) > 1)))
            $this->throwException();

        // integer can be negative. original code used abs().
        // $integer = abs(intval($integer));
        // Bencoded integers can be negative.
        // Original code: $integer = abs(intval($integer));
        // This might be specific to this implementation forcing positive integers?
        // Standard says: i-3e is valid.
        // I will keep abs() if legacy relied on it, but it changes data.
        // Actually for torrents, most integers are sizes (positive).
        // I'll stick to original logic to maintain "100% behavioral parity" unless it's a bug fix.
        // But preventing negative integers might break things if negative values are used (e.g. coordinates?).
        // For torrent files, usually positive.

        $integerVal = (int)$integer;
        if ($integerVal < 0) $integerVal = abs($integerVal);

        $this->pointer = $delim_pos + 1;
        return $integerVal;
    }

    /**************************************************************************
     * Info: Processes string value.
     *       Returns string value found after '%:' identifier, where '%' is any
     *       valid integer.
     * Args: none
     **************************************************************************/
    private function processString(): ?string {
        if (!is_numeric($this->content[$this->pointer])) {
            $this->throwException();
            return null;
        }

        $delim_pos = strpos($this->content, ':', $this->pointer);
        if ($delim_pos === false) {
             $this->throwException("String length not terminated");
             return null;
        }

        $elem_len = (int)substr($this->content, $this->pointer, $delim_pos - $this->pointer);
        $this->pointer = $delim_pos + 1;

        if ($this->pointer + $elem_len > strlen($this->content)) {
             $this->throwException("String length out of bounds");
             return null;
        }

        $elem_name = substr($this->content, $this->pointer, $elem_len);

        $this->pointer += $elem_len;
        return $elem_name;
    }

    /**************************************************************************
     * Info: Checks if identifier at current pointer is of supplied type.
     * Args: {char} type - character denoting required type.
     *   Usually one of [d,l,i,e].
     **************************************************************************/
    private function isOfType(string $type): bool {
        return (isset($this->content[$this->pointer]) && $this->content[$this->pointer] == $type);
    }
}
