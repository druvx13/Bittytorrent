<?php

namespace Tests\Core;

use App\Core\Hooks;
use Tests\TestCase;

class HooksTest extends TestCase
{
    public function testAddHook()
    {
        $hooks = new Hooks();
        $hooks->add_hook('test_tag', function() { return 'success'; });
        $this->assertTrue($hooks->hook_exist('test_tag'));
    }

    public function testExecuteHook()
    {
        $hooks = new Hooks();
        $hooks->add_hook('test_tag', function($arg) { return $arg . '_processed'; });
        $result = $hooks->execute_hook('test_tag', 'data');
        $this->assertEquals('data_processed', $result);
    }

    public function testAddPage()
    {
        $hooks = new Hooks();
        $hooks->add_page('test_page', 'test.php', 'test.html');
        $this->assertEquals('test_page', $hooks->addnewpage['test_page']['name']);
        $this->assertEquals('test.php', $hooks->addnewpage['test_page']['phpFile']);
    }
}
