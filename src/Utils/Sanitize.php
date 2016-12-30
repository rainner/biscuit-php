<?php
/**
 * Static methods for sanitizing common values
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use Closure;

class Sanitize {

    /**
     * Returns a string
     */
    public static function toString( $value=null )
    {
        return ( is_string( $value ) || is_numeric( $value ) ) ? trim( $value ) : "";
    }

    /**
     * Returns an array
     */
    public static function toArray( $value=null )
    {
        return is_array( $value ) ? $value : [];
    }

    /**
     * Returns a closure
     */
    public static function toClosure( $value=null )
    {
        return ( $value instanceof Closure ) ? $value : function(){};
    }

    /**
     * Converts a numeric value to a signed float number
     */
    public static function toFloat( $value=null )
    {
        $value = Utils::replace( $value, "/[^\d\-\.]+/" );
        $value = is_numeric( $value ) ? $value + 0 : 0;
        return (float) $value;
    }

    /**
     * Converts a numeric value to a signed integer value
     */
    public static function toNumber( $value=null )
    {
        $value = Utils::replace( $value, "/[^\d\-\.]+/" );
        $value = is_numeric( $value ) ? $value + 0 : 0;
        return (int) $value;
    }

    /**
     * Returns a boolean
     */
    public static function toBool( $value=null )
    {
        if( is_bool( $value ) )
        {
            return $value;
        }
        if( is_string( $value ) )
        {
            $value = strtoupper( trim( $value ) );
            if( preg_match( "/^(1|Y|YES|ON|ENABLED|TRUE)$/u", $value ) ) return true;
            if( preg_match( "/^(0|N|NO|OFF|DISABLED|FALSE)$/u", $value ) ) return false;
        }
        if( is_numeric( $value ) )
        {
            return ( intval( $value ) > 0 ) ? true : false;
        }
        return false;
    }

    /**
     * Normalize, strip and encode a string to be used as safe text
     */
    public static function toText( $value="" )
    {
        $value = self::toString( $value );
        $value = html_entity_decode( $value );
        $value = filter_var( $value, FILTER_SANITIZE_STRING,  FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_ENCODE_LOW );
        $value = trim( $value );
        return $value;
    }

    /**
     * Only allow alpha-numerical characters and spaces
     */
    public static function toAlnum( $value=null )
    {
        $value = Utils::replace( $value, "/[^a-zA-Z0-9]+/u" );
        return $value;
    }

    /**
     * Remove white-space from a string and only allow single spaces
     */
    public static function toSingleSpaces( $value=null )
    {
        $value = Utils::replace( $value, "/[\t\r\n\s\h\v]+/", " " );
        $value = Utils::replace( $value, "/\s\s+/", " " );
        $value = trim( $value );
        return $value;
    }

    /**
     * Convert all words to be capitalized
     */
    public static function toCaps( $value=null, $seps=[] )
    {
        $value = self::toSingleSpaces( $value );
        $seps  = array_merge( array( ".", "_", "-", "/", "[", "(", ":" ), $seps );

        foreach( $seps as $sep )
        {
            $sep   = trim( $sep );
            $value = str_replace( $sep, $sep." ", $value );
            $value = str_replace( $sep." ", $sep, ucwords( $value ) );
        }
        return $value;
    }

    /**
     * Convert all characters to uppercase
     */
    public static function toUpperCase( $value=null )
    {
        $value = self::toSingleSpaces( $value );
        $value = strtoupper( $value );
        return $value;
    }

    /**
     * Convert all characters to lowercase
     */
    public static function toLowerCase( $value=null )
    {
        $value = self::toSingleSpaces( $value );
        $value = strtolower( $value );
        return $value;
    }

    /**
     * Convert value to camelCase style string
     */
    public static function toCamelCase( $value=null )
    {
        $value  = Utils::replace( $value, "/[^a-zA-Z0-9\ ]+/u", " " );
        $words  = explode( " ", self::toSingleSpaces( $value ) );

        if( count( $words ) > 1 )
        {
            $first  = strtolower( array_shift( $words ) );
            $output = $first ." ". ucwords( strtolower( implode( " ", $words ) ) );
            return str_replace( " ", "", $output );
        }
        return implode( "", $words );
    }

    /**
     * Convert value to FullCamelCase style string
     */
    public static function toFullCamelCase( $value=null )
    {
        $value  = Utils::replace( $value, "/[^a-zA-Z0-9\ ]+/u", " " );
        $words  = explode( " ", self::toSingleSpaces( $value ) );

        if( count( $words ) > 1 )
        {
            $output = ucwords( strtolower( implode( " ", $words ) ) );
            return str_replace( " ", "", $output );
        }
        return implode( "", $words );
    }

    /**
     * String to be used as a key [a-zA-Z0-9_.]
     */
    public static function toKey( $value=null )
    {
        $value = Utils::replace( $value, "/[^\w\.]+/u", "_" );
        $value = Utils::replace( $value, "/\_\_+/u", "_" );
        $value = trim( $value, "._ " );
        return $value;
    }

    /**
     * String to be used as URL slug [a-zA-Z0-9_-]
     */
    public static function toSlug( $value=null )
    {
        $value = Utils::replace( $value, "/[^\w\-]+/u", "-" );
        $value = Utils::replace( $value, "/\_\_+/u", "_" );
        $value = Utils::replace( $value, "/\-\-+/u", "-" );
        $value = trim( $value, "_- " );
        return $value;
    }

    /**
     * String to be used as parameter
     */
    public static function toParam( $value=null )
    {
        $value = html_entity_decode( self::toString( $value ) );
        $value = Utils::replace( $value, "/[^\w\.\,\-\=\*\@\#\:\?\(\)\ \\]+/u" );
        $value = Utils::replace( $value, "/\,\,+/u", "," );
        $value = Utils::replace( $value, "/\.\.+/u", "." );
        $value = Utils::replace( $value, "/\=\=+/u", "=" );
        $value = Utils::replace( $value, "/\*\*+/u", "*" );
        $value = Utils::replace( $value, "/\@\@+/u", "@" );
        $value = Utils::replace( $value, "/\#\#+/u", "#" );
        $value = Utils::replace( $value, "/\\\\+/u", "\\" );
        $value = self::toSingleSpaces( trim( $value, ".,= " ) );
        return $value;
    }

    /**
     * Adds backticks around words to be used as table/column names in a SQL query
     */
    public static function toSqlName( $value=null )
    {
        $value = self::toParam( $value );
        $value = Utils::replace( $value, "/\b(?!.[A-Z]+)(?!as|AS)([\w]+)\b/u", "`$1`" );
        $value = Utils::replace( $value, "/([\:\?\'\"]+)`\b([\w]+)\b`([\'\"]+)?/u", "$1$2$3" );
        return $value;
    }

    /**
     * Filesystem or URL path
     */
    public static function toPath( $value=null )
    {
        $value = self::toString( $value );
        $value = rtrim( str_replace( "\\", "/", trim( $value ) ), "/" );
        $value = Utils::replace( $value, "/\/\/+/", "/" );
        return $value;
    }

    /**
     * File extension
     */
    public static function toExtension( $value=null )
    {
        $parts = explode( ".", self::toString( $value ) );
        $value = trim( array_pop( $parts ) );
        $value = Utils::replace( $value, "/[^a-zA-Z0-9]+/", "" );
        return strtolower( $value );
    }

    /**
     * Common safe characters used in titles, strips whitespace
     */
    public static function toTitle( $value=null )
    {
        $value = Utils::replace( $value, "/[^\w\!\@\#\$\%\^\&\*\(\)\_\+\-\=\{\}\[\]\:\;\"\"\,\.\/\?\ ]+/ui" );
        $value = Utils::replace( $value, "/\.[a-zA-Z0-9]+$/ui" );
        $value = self::toSingleSpaces( $value );
        return $value;
    }

    /**
     * String to be used as a person's name
     */
    public static function toName( $value=null )
    {
        $value = Utils::replace( $value, "/[^\p{L}\'\-\ ]+/ui", " " );
        $value = Utils::replace( $value, "/\'\s+/u", "'" );
        $value = self::toSingleSpaces( $value );
        return $value;
    }

    /**
     * Common characters present in a phone number
     */
    public static function toPhone( $value=null )
    {
        $value = Utils::replace( $value, "/[^0-9\+]+/", "-" );
        $value = Utils::replace( $value, "/\-\-+/u", "-" );
        $value = trim( $value, "_- " );
        return $value;
    }

    /**
     * Common allowed characters for an e-mail address
     */
    public static function toEmail( $value=null )
    {
        $value = Utils::replace( $value, "/\@\@+/u", "@" );
        $value = Utils::replace( $value, "/[^\d\p{L}\@\.\_\-\!\#\$\%\&\+\=]+/u" );
        return $value;
    }

    /**
     * Common allowed characters for URL address
     */
    public static function toUrl( $value=null )
    {
        $value = self::toText( $value );
        $value = Utils::replace( $value, "/[^\d\p{L}\.\_\-\@\#\?\%\&\=\+\/\:\;\~\(\)\{\}\[\]\"\"\\]+/u" );
        $value = str_replace( "\\", "/", trim( $value ) );
        return $value;
    }

    /**
     * Url hostname
     */
    public static function toHostname( $value=null )
    {
        $value = self::toString( $value );

        if( $host = parse_url( $value, PHP_URL_HOST ) )
        {
            return $host;
        }
        return $value;
    }

    /**
     * Twitter style @handle (15 characters limit)
     */
    public static function toHandle( $value=null, $prefix="@" )
    {
        $value = Utils::replace( $value, "/[^\w]/u" );
        $value = substr( $value, 0, 15 );
        return $prefix . $value;
    }

    /**
     * Twitter style #hashtag (140 characters limit)
     */
    public static function toHashtag( $value=null, $prefix="#" )
    {
        $value = Utils::replace( $value, "/[^\d\p{L}\_]/u" );
        $value = substr( $value, 0, 140 );
        return $prefix . $value;
    }

    /**
     * IP v4 address
     */
    public static function toIp( $value=null )
    {
        $value = Utils::replace( $value, "/[^\d\.]+/u", " " );
        $value = self::toSingleSpaces( $value );
        $list  = explode( " " , trim( $value, ". " ) );
        return array_shift( $list );
    }

    /**
     * IP v6 address
     */
    public static function toIpv6( $value=null )
    {
        $value = Utils::replace( $value, "/[^a-fA-F0-9\:]+/i" );
        $value = Utils::replace( $value, "/\:\:{3,}/", "::" );
        $value = trim( $value, ":" );
        return $value;
    }



}