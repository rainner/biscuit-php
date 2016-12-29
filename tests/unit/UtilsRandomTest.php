<?php
/**
 * Tests
 */
use Biscuit\Utils\Random;

class UtilsRandomTest extends TestCase {

    public function testClassMethods()
    {
        $randLength = 20;
        $randString = Random::string( $randLength );
        $randNumber = Random::number( $randLength );
        $randBytes  = Random::bytes( $randLength );

        $this->assertEquals( $randLength, strlen( $randString ) );
        $this->assertEquals( $randLength, strlen( $randNumber ) );
        $this->assertEquals( $randLength, strlen( $randBytes ) );
    }
}