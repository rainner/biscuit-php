<?php
/**
 * Static methods for validating common value types
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use Closure;

class Validate {

    /**
     * Key strings
     */
    public static function isKey( $value=null )
    {
        return preg_match( "/^[^\d][\w\.]+$/u", $value ) === 1 ? true : false;
    }

    /**
     * Slug strings
     */
    public static function isSlug( $value=null )
    {
        return preg_match( "/^[\w\-]+$/u", $value ) === 1 ? true : false;
    }

    /**
     * at least 2+ characters long full names
     */
    public static function isName( $value=null )
    {
        return preg_match( "/^(?=.{2,})([\p{L}\'\-\ ]+)$/ui", $value ) === 1 ? true : false;
    }

    /**
     * MD5 hashes
     */
    public static function isMd5( $value=null )
    {
        return preg_match( "/^[a-f0-9]{32}$/i", $value ) === 1 ? true : false;
    }

    /**
     * Numeric values
     */
    public static function isNumber( $value=null )
    {
        return ( is_numeric( $value ) || preg_match( "/^[0-9\-\,\.]+$/i", $value ) ) ? true : false;
    }

    /**
     * Integer numbers
     */
    public static function isInteger( $value=null )
    {
        return is_int( is_numeric( $value ) ? $value + 0 : $value ) ? true : false;
    }

    /**
     * Float/Double numbers
     */
    public static function isFloat( $value=null )
    {
        return is_float( is_numeric( $value ) ? $value + 0 : $value ) ? true : false;
    }

    /**
     * Boolean values
     */
    public static function isBool( $value=null )
    {
        if( is_bool( $value ) ) return true;
        if( is_numeric( $value ) && preg_match( "/^(0|1)$/u", trim( $value ) ) === 1 ) return true;
        if( is_string( $value ) && preg_match( "/^(Y|N|ON|OFF|YES|NO|TRUE|FALSE)$/u", strtoupper( trim( $value ) ) ) === 1 ) return true;
        return false;
    }

    /**
     * Zipcode values
     */
    public static function isZipcode( $value=null )
    {
        return preg_match( "/^(\d{5}(\-\d{4})?)$/i", $value ) === 1 ? true : false;
    }

    /**
     * Phone numbers
     */
    public static function isPhone( $value=null )
    {
        return preg_match( "/^([1+-. ]{2,3})?\(?([\d]{3})\)?[-. ]?([\d]{3})[-. ]?([\d]{4})$/", $value ) === 1 ? true : false;
    }

    /**
     * Common e-mail addresses
     */
    public static function isEmail( $value=null )
    {
        return preg_match( "/^([\d\p{L}\.\_\-\!\#\$\%\&\+\=]+)@([\d\p{L}\-\.]+)(\.[\p{L}]{2,63})$/u", $value ) === 1 ? true : false;
    }

    /**
     * 2-15 characters long Twitter style @handle
     */
    public static function isHandle( $value=null )
    {
        return preg_match( "/^(?=.{2,15}$)(@){1}([\w]+)$/u", $value ) === 1 ? true : false;
    }

    /**
     * 2-140 characters long Twitter style #hashtag
     */
    public static function isHashtag( $value=null )
    {
        return preg_match( "/^(?=.{2,140}$)(#|\x{ff03}){1}([\d\p{L}\_]*[_\p{L}][\d\p{L}\_]*)$/u", $value ) === 1 ? true : false;
    }

    /**
     * 6+ characters long, must contain both letters and numbers, everything else is optional
     */
    public static function isPassword( $value=null )
    {
        return preg_match( "/^(?=.{6,})(?=.*[0-9])(?=.*[\p{L}]).*$/u", $value ) === 1 ? true : false;
    }

    /**
     * IP v4 address
     */
    public static function isIp( $value=null )
    {
        return filter_var( $value, FILTER_VALIDATE_IP ) ? true : false;
    }

    /**
     * IP v6 address
     */
    public static function isIpv6( $value=null )
    {
        return filter_var( $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? true : false;
    }

    /**
     * Valid web URL
     */
    public static function isUrl( $value=null )
    {
        $host = parse_url( $value, PHP_URL_HOST );

        if( !empty( $host ) )
        {
            return true;
        }
        return false;
    }

    /**
     * External path URL
     */
    public static function isExternal( $value=null )
    {
        $host = parse_url( $value, PHP_URL_HOST );
        $site = parse_url( "http://".$_SERVER["HTTP_HOST"], PHP_URL_HOST );

        if( !empty( $host ) )
        {
            return ( $host !== $site ) ? true : false;
        }
        return false;
    }

    /**
     * Test value against a type
     */
    public static function isType( $value=null, $type="" )
    {
        if( !empty( $type ) )
        {
            $type    = strtolower( trim( $type ) );
            $test    = strtolower( trim( gettype( $value ) ) );
            $closure = ( $value instanceof Closure );

            if( is_object( $value ) && $type !== "object" )
            {
                if( preg_match( "/^(callable|closure|function)$/", $type ) )
                {
                    return ( is_callable( $value ) || $closure ) ? true : false;
                }
            }
            if( is_string( $value ) && $type !== "string" )
            {
                if( preg_match( "/^(class|object)$/", $type ) )
                {
                    return ( class_exists( $value ) ) ? true : false;
                }
                if( preg_match( "/^(function|callable|method)$/", $type ) )
                {
                    return ( function_exists( $value ) || is_callable( $value ) ) ? true : false;
                }
                if( preg_match( "/^(file|link|symlink)$/", $type ) )
                {
                    return ( is_file( $value ) || is_link( $value ) ) ? true : false;
                }
                if( preg_match( "/^(folder|directory)$/", $type ) )
                {
                    return ( is_dir( $value ) ) ? true : false;
                }
            }
            if( $type === "true"  && $value === true )  return true;
            if( $type === "false" && $value === false ) return true;
            if( $type === "null"  && $value === null )  return true;
            return ( $test === $type );
        }
        return false;
    }


}