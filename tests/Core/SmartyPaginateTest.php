<?php

namespace Tests\Core;

use App\Core\SmartyPaginate;
use Tests\TestCase;

class SmartyPaginateTest extends TestCase
{
    public function testConnect()
    {
        SmartyPaginate::disconnect();
        SmartyPaginate::connect();
        $this->assertTrue(SmartyPaginate::isConnected());
    }

    public function testSetTotal()
    {
        SmartyPaginate::disconnect();
        SmartyPaginate::connect();
        SmartyPaginate::setTotal(100);
        $this->assertEquals(100, SmartyPaginate::getTotal());
    }
}
