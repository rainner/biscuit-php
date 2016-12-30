<?php
/**
 * Tests
 */
class CryptEncryptionTest extends TestCase {

    public function testEncryptDecrypt()
    {
        $encKey    = "swordfish";
        $encMethod = "AES-128-CBC";
        $encData   = "this is some plain-text data to be encrypted and decrypted.";
        $encPlain  = "";
        $encCrypt  = "";

        $crypt = new Biscuit\Crypt\Encryption();
        $crypt->setCryptKey( $encKey );
        $crypt->setCryptMethod( $encMethod );

        $encCrypt = $crypt->encrypt( $encData, true );
        $encPlain = $crypt->decrypt( $encCrypt, true );

        $this->assertEquals( $encKey, $crypt->getCryptKey() );
        $this->assertEquals( $encMethod, $crypt->getCryptMethod() );
        $this->assertEquals( $encData, $encPlain );
    }
}