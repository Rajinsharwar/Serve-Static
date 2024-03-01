<?php
// ActivateTest.php
require_once __DIR__ . '/../class/Activate.php';
use PHPUnit\Framework\TestCase;

class ActivateTest extends TestCase {

    public function testOneMethod() {
        $instance = new Activate();
        $a = $instance->one(5);
    
        self::assertEquals(6, $a);
    }
}
