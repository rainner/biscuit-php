<?php
/**
 * For generating random values
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Util;

class Random {

    /**
     * Random string with both letters and numbers
     */
    public static function string( $length=20 )
    {
        $chars = array_merge( range( '0','9' ), range( 'a','z' ), range( 'A','Z' ) );
        return self::_generate( $length, $chars );
    }

    /**
     * Random numbers only
     */
    public static function number( $length=20 )
    {
        $chars = range( '0','9' );
        return self::_generate( $length, $chars );
    }

    /**
     * Random raw bytes
     */
    public static function bytes( $length=32 )
    {
        return openssl_random_pseudo_bytes( $length );
    }

    /**
     * Random encoded bytes
     */
    public static function encoded( $length=32 )
    {
        return base64_encode( self::bytes( $length ) );
    }

    /**
     * Generates a random string up to a lenght, for given chars
     */
    protected static function _generate( $length=20, $chars=array() )
    {
        $final = array();
        $total = $length;
        shuffle( $chars );

        while( $length-- )
        {
            if( count( $final ) >= $total ) break;
            $final[] = $chars[ array_rand( $chars ) ];
        }
        shuffle( $final );
        return implode( '', $final );
    }

}