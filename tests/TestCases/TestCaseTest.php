<?php

namespace Vendor\YourPackage\Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;

class FooTest extends TestCase
{
    use AssertIsType;

    public function testSomething()
    {
        $this->assertIsBool( $maybeBool );
        self::assertIsNotIterable( $maybeIterable );
    }
}