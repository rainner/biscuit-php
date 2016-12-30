<?php
/**
 * Tests
 */
use Biscuit\Crypt\Password;

class CryptPasswordTest extends TestCase {

    public function testPasswordHashCompare()
    {
        $pwPlain  = "l337p@zzW0rD";
        $pwHashed = Password::hash( $pwPlain );
        $pwMatch  = Password::verify( $pwPlain, $pwHashed );

        $this->assertEquals( true, $pwMatch );
    }
}