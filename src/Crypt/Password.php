<?php
/**
 * Handles hashing and verifying passwords.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Crypt;

class Password {

    // consts
    const SALTCHARS = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    const CRYPTALGO = "sha256"; // fallback algo for hash() if password_hash not installed

    /**
     * Hashes a string
     */
    public static function hash( $password="" )
    {
        if( function_exists( "password_hash" ) )
        {
            if( defined( "PASSWORD_BCRYPT" ) )
            {
                return password_hash( $password, PASSWORD_BCRYPT );
            }
            return password_hash( $password, PASSWORD_DEFAULT );
        }
        return self::_pw_hash( $password );
    }

    /**
     * Verifies hashed string against a plaintext one
     */
    public static function verify( $password="", $hashed="" )
    {
        if( function_exists( "password_verify" ) )
        {
            return password_verify( $password, $hashed );
        }
        return self::_pw_verify( $password, $hashed );
    }

    /**
     * Fallback hashing function
     */
    private static function _pw_hash( $password="" )
    {
        $salt   = "";
        $length = strlen( self::SALTCHARS ) - 1;
        for( $i=0; $i < 22; $i++ ) $salt .= substr( self::SALTCHARS, mt_rand( 0, $length ), 1 );
        return hash( self::CRYPTALGO, $password . $salt ) . $salt;
    }

    /**
     * Fallback hash verify function
     */
    private static function _pw_verify( $password="", $hashed="" )
    {
        $length = strlen( self::SALTCHARS );
        $salt   = substr( $hashed, $length );    // get the salt from the end of the hash
        $hash   = substr( $hashed, 0, $length ); // get the hash without the salt
        return ( $hash === hash( self::CRYPTALGO, $password . $salt ) ) ? true : false;
    }
}