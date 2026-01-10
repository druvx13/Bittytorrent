<?php

namespace Tests\Core;

use Smarty;
use Tests\TestCase;

class SmartyTest extends TestCase
{
    public function testSmartyInitialization()
    {
        $smarty = new Smarty();
        $this->assertInstanceOf(Smarty::class, $smarty);
    }

    public function testSmartyTemplateDir()
    {
        $smarty = new Smarty();
        $dir = realpath(__DIR__ . '/../../templates') . '/';
        $smarty->setTemplateDir($dir);
        // Smarty might normalize paths, so verify that the set path is what we expect
        // The previous test failure showed mismatch because one was resolved path and one relative.
        $this->assertEquals([$dir], $smarty->getTemplateDir());
    }
}
