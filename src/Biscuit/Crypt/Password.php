<?php
/**
 * Handles hashing and verifying passwords.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Crypt;

class Password {

    // characters for creating random salt strings
    const SALTCHARS = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    // fallback password hashing algo (will use bcrypt if available)
    const CRYPTALGO = 'sha256';

    /**
     * Hashes a string
     */
    public static function hash( $password='' )
    {
        if( function_exists( 'password_hash' ) && defined( 'PASSWORD_BCRYPT' ) )
        {
            return password_hash( $password, PASSWORD_BCRYPT );
        }
        return self::_pw_hash( $password );
    }

    /**
     * Verifies hashed string against a plaintext one
     */
    public static function verify( $password='', $hashed='' )
    {
        if( function_exists( 'password_verify' ) )
        {
            return password_verify( $password, $hashed );
        }
        return self::_pw_verify( $password, $hashed );
    }

    /**
     * Fallback hashing function
     */
    private static function _pw_hash( $password='' )
    {
        $salt   = '';
        $length = strlen( self::SALTCHARS ) - 1;
        for( $i=0; $i < 22; $i++ ) $salt .= substr( self::SALTCHARS, mt_rand( 0, $length ), 1 );
        return hash( self::CRYPTALGO, $password . $salt ) . $salt;
    }

    /**
     * Fallback hash verify function
     */
    private static function _pw_verify( $password='', $hashed='' )
    {
        $length = strlen( self::SALTCHARS );
        $salt   = substr( $hashed, $length );    // get the salt from the end of the hash
        $hash   = substr( $hashed, 0, $length ); // get the hash without the salt
        return ( $hash == hash( self::CRYPTALGO, $password . $salt ) );
    }
}