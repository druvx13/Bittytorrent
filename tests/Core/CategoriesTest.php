<?php

namespace Tests\Core;

use App\Core\Categories;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($_ENV['DB_HOST'])) {
            $this->markTestSkipped('Database configuration not found.');
        }
    }

    public function testGetPrefix()
    {
        $categories = new Categories();
        // position format example: 0>1>
        $prefix = $categories->get_prefix('0>1>');
        // explode gives ["0", "1", ""] (count 3). loop from 1 to 2 (< 2). runs once.
        $this->assertEquals("&nbsp;&nbsp;", $prefix);

        $prefix2 = $categories->get_prefix('0>1>2>');
        // explode ["0", "1", "2", ""] (count 4). loop 1 to 3. runs twice.
        $this->assertEquals("&nbsp;&nbsp;&nbsp;&nbsp;", $prefix2);
    }
}
