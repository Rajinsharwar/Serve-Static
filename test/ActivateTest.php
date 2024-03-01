<?php
// ActivateTest.php
require_once __DIR__ . '/../class/Activate.php';

class ActivateTest extends \PHPUnit\Framework\TestCase {

    public function testOneMethod() {
        $instance = new Activate();
        $ex = 5;
    
        self::assertEquals($ex, $a);
    }
}
