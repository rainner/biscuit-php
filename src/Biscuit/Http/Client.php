<?php
/**
 * Provides information about the request client.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Http;

use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;

class Client {

    /**
     * Returns the client IP address
     */
    public static function getIp( $default='' )
    {
        $keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_INCAP_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_REAL_IP',
            'HTTP_PROXY',
            'HTTP_PROXY_CONNECTION',
            'HTTP_VIA',
            'REMOTE_ADDR',
        );
        foreach( $keys as $key )
        {
            if( !empty( $_SERVER[ $key ] ) )
            {
                $list  = explode( ',', trim( $_SERVER[ $key ], ', ' ) );
                $first = trim( array_shift( $list ) );

                if( $first = filter_var( $first, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) )
                {
                    return $first;
                }
            }
        }
        return $default;
    }

    /**
     * Returns the client IP hostname
     */
    public static function getIpHost( $ip='', $default='' )
    {
        if( !empty( $ip ) )
        {
            if( $host = gethostbyaddr( $ip ) )
            {
                return $host;
            }
        }
        return $default;
    }

    /**
     * Returns the client port number
     */
    public static function getPort( $default=0 )
    {
        return self::_server( 'REMOTE_PORT', $default );
    }

    /**
     * Returns the client user agent string
     */
    public static function getAgent( $default='' )
    {
        return self::_server( 'HTTP_USER_AGENT', $default );
    }

    /**
     * Returns the client connection type
     */
    public static function getConnection( $default='' )
    {
        return self::_server( 'HTTP_CONNECTION', $default );
    }

    /**
     * Returns the referer URL
     */
    public static function getReferer( $default='' )
    {
        return self::_server( 'HTTP_REFERER', $default );
    }

    /**
     * Returns the HTTP authentication username (if any)
     */
    public static function getUsername( $default='' )
    {
        return self::_server( 'PHP_AUTH_USER', $default );
    }

    /**
     * Returns the HTTP authentication password (if any)
     */
    public static function getPassword( $default='' )
    {
        return self::_server( 'PHP_AUTH_PW', $default );
    }

    /**
     * Generates a fixed hash that represents the current state of the client connection
     */
    public static function getHash( $append='' )
    {
        $values   = array();
        $values[] = self::getIp( 'no-address' );
        $values[] = self::getIpHost( $values[ 0 ], 'no-hostname' );
        $values[] = self::getAgent( 'no-useragent' );
        $values[] = self::getConnection( 'no-connection' );

        if( !empty( $append ) && is_string( $append ) )
        {
            $values[] = trim( $append );
        }
        return hash( 'sha256', implode( ' @ ', $values ) );
    }

    /**
     * Checks if a SERVER key is available, or use default value
     */
    private static function _server( $key='', $default='' )
    {
        $key     = strtoupper( trim( $key ) );
        $default = trim( $default );

        if( array_key_exists( $key, $_SERVER ) )
        {
            return Sanitize::toText( $_SERVER[ $key ] );
        }
        return $default;
    }



}