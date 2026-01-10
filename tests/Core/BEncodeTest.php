<?php

namespace Tests\Core;

use App\Core\BEncode;
use Tests\TestCase;

require_once __DIR__ . '/../../libs/Core/BEncode.php';

class BEncodeTest extends TestCase
{
    public function testBEncodeInteger()
    {
        $data = 123;
        $expected = "i123e";
        // Call the function directly without root namespace if require_once worked,
        // but BEncode() is in global namespace in BEncode.php?
        // Let's check BEncode.php content again.
        // It says: if (!function_exists('BEncode')) { function BEncode... }
        // It is outside namespace App\Core? No, it is IN namespace App\Core because I put it there.
        // So I should call App\Core\BEncode() or import it.
        $this->assertEquals($expected, \App\Core\BEncode($data));
    }

    public function testBEncodeString()
    {
        $data = "test";
        $expected = "4:test";
        $this->assertEquals($expected, \App\Core\BEncode($data));
    }

    public function testBEncodeList()
    {
        $data = ["test", 123];
        $expected = "l4:testi123ee";
        $this->assertEquals($expected, \App\Core\BEncode($data));
    }

    public function testBEncodeDictionary()
    {
        $data = ["key" => "value"];
        $expected = "d3:key5:valuee";
        $this->assertEquals($expected, \App\Core\BEncode($data));
    }
}
