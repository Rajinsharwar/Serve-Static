<?php
// ActivateTest.php

// Import the necessary namespaces
use ServeStatic\Activate;
use PHPUnit\Framework\TestCase;

class ActivateTest extends TestCase {

    public function testOneMethod() {
        // Arrange
        $instance = new Activate();
        $expected = 5;

        // Act
        $result = $instance->one($expected);

        // Assert
        $this->assertEquals($expected, $result);
    }
}
