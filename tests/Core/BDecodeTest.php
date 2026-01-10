<?php

namespace Tests\Core;

use App\Core\BDecode;
use Tests\TestCase;

class BDecodeTest extends TestCase
{
    public function testBDecodeInteger()
    {
        $file = sys_get_temp_dir() . '/test_bdecode_int';
        file_put_contents($file, "i123e");
        $decoder = new BDecode($file);
        $this->assertEquals(123, $decoder->result);
        unlink($file);
    }

    public function testBDecodeString()
    {
        $file = sys_get_temp_dir() . '/test_bdecode_str';
        file_put_contents($file, "4:test");
        $decoder = new BDecode($file);
        $this->assertEquals("test", $decoder->result);
        unlink($file);
    }

    public function testBDecodeList()
    {
        $file = sys_get_temp_dir() . '/test_bdecode_list';
        file_put_contents($file, "l4:testi123ee");
        $decoder = new BDecode($file);
        $this->assertEquals(["test", 123], $decoder->result);
        unlink($file);
    }
}
