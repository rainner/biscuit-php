<?php
/**
 * Http connection utils.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Connection {

    /**
     * Check the the server is using an HTTPS connection
     */
    public static function isSecure()
    {
        $https = Utils::value( @$_SERVER["HTTPS"], "" );
        $https = strtolower( $https );

        $forward = Utils::value( @$_SERVER["HTTP_X_FORWARDED_SSL"], @$_SERVER["HTTP_X_FORWARDED_PROTO"], "" );
        $forward = strtolower( $forward );

        $port = Utils::value( @$_SERVER["SERVER_PORT"], "" );
        $port = strtolower( $port );

        if( !empty( $https ) && $https !== "off" ) return true;
        if( !empty( $forward ) && $forward === "https" ) return true;
        if( !empty( $port ) && $port === "443" ) return true;
        return false;
    }

    /**
     * Checks if incoming request is an AJAX request
     */
    public static function isAjax()
    {
        $value = Utils::value( @$_SERVER["REQUESTED_WITH"], @$_SERVER["HTTP_REQUESTED_WITH"], @$_SERVER["HTTP_X_REQUESTED_WITH"], "" );
        return ( strtolower( trim( $value ) ) === "xmlhttprequest" ) ? true : false;
    }

    /**
     * Checks if incoming request is a SOAP request
     */
    public static function isSoap()
    {
        $value = Utils::value( @$_SERVER["SOAPACTION"], @$_SERVER["HTTP_SOAPACTION"], "" );
        return !empty( $value ) ? true : false;
    }

    /**
     * Check current request verb
     */
    public static function isMethod( $method )
    {
        return ( strtoupper( trim( $method ) ) === self::getMethod() ) ? true : false;
    }

    /**
     * Resolve the current HTTP request method
     */
    public static function getMethod( $default="GET" )
    {
        $method = Utils::value( @$_SERVER["REQUEST_METHOD"], @$_SERVER["HTTP_X_HTTP_METHOD"], $default );
        return Sanitize::toUpperCase( $method );
    }

    /**
     * Resolve the current HTTP request path
     */
    public static function getPath( $default="/" )
    {
        $path = Utils::value( @$_SERVER["PATH_INFO"], @$_SERVER["ORIG_PATH_INFO"], "" );
        $path = Sanitize::toPath( $path );
        return !empty( $path ) ? $path : $default;
    }

    /**
     * Returns the client IP hostname
     */
    public static function getIpHost( $ip, $default="" )
    {
        $host = @gethostbyaddr( trim( $ip ) );
        return !empty( $host ) ? $host : $default;
    }

    /**
     * Returns the client port number
     */
    public static function getPort( $default=0 )
    {
        return Utils::value( @$_SERVER["REMOTE_PORT"], $default );
    }

    /**
     * Returns the client user agent string
     */
    public static function getAgent( $default="" )
    {
        return Utils::value( @$_SERVER["HTTP_USER_AGENT"], $default );
    }

    /**
     * Returns the client connection type
     */
    public static function getType( $default="" )
    {
        return Utils::value( @$_SERVER["HTTP_CONNECTION"], $default );
    }

    /**
     * Returns the referer URL
     */
    public static function getReferer( $default="" )
    {
        return Utils::value( @$_SERVER["HTTP_REFERER"], $default );
    }

    /**
     * Returns the HTTP authentication username (if any)
     */
    public static function getUsername( $default="" )
    {
        return Utils::value( @$_SERVER["PHP_AUTH_USER"], $default );
    }

    /**
     * Returns the HTTP authentication password (if any)
     */
    public static function getPassword( $default="" )
    {
        return Utils::value( @$_SERVER["PHP_AUTH_PW"], $default );
    }

    /**
     * Generates a fixed hash that represents the current state of the client connection
     */
    public static function getHash( $append="" )
    {
        $ip = self::getIp( "no-address" );

        $values   = [];
        $values[] = self::getIpHost( $ip, $ip );
        $values[] = self::getAgent( "no-useragent" );

        if( !empty( $append ) )
        {
            if( is_array( $append ) )
            {
                $values = array_merge( $values, $append );
            }
            else if( is_string( $append ) )
            {
                $values[] = trim( $append );
            }
        }
        return hash( "sha256", implode( " @ ", $values ) );
    }

    /**
     * Returns the client IP address
     */
    public static function getIp( $default="" )
    {
        $keys = array(
            "HTTP_CLIENT_IP",
            "HTTP_X_FORWARDED_FOR",
            "HTTP_X_FORWARDED",
            "HTTP_INCAP_CLIENT_IP",
            "HTTP_CF_CONNECTING_IP",
            "HTTP_X_CLUSTER_CLIENT_IP",
            "HTTP_FORWARDED_FOR",
            "HTTP_FORWARDED",
            "HTTP_X_REAL_IP",
            "HTTP_PROXY",
            "HTTP_PROXY_CONNECTION",
            "HTTP_VIA",
            "REMOTE_ADDR",
        );
        foreach( $keys as $key )
        {
            if( !empty( $_SERVER[ $key ] ) )
            {
                foreach( explode( ",", trim( $_SERVER[ $key ], ", " ) ) as $ip )
                {
                    if( !empty( $ip ) )
                    {
                        return $ip;
                    }
                }
            }
        }
        return $default;
    }

}