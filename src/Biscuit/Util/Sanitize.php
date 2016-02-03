<?php
/**
 * Handles sanitization of string values.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Util;

class Sanitize {

    /**
     * Strips HTML from a string
     */
    public static function toText( $value=null )
    {
        $value = html_entity_decode( $value );
        $value = strip_tags( $value );
        $value = trim( $value );
        return $value;
    }

    /**
     * Strips HTML and encode unsafe characters from a string
     */
    public static function toSafeText( $value=null )
    {
        $value = filter_var( $value, FILTER_SANITIZE_STRING, FILTER_FLAG_EMPTY_STRING_NULL );
        $value = trim( $value );
        return $value;
    }

    /**
     * Only allow alpha-numerical characters and spaces
     */
    public static function toAlnum( $value=null )
    {
        $value = self::toText( $value );
        $value = self::_filter( '/[^a-zA-Z0-9]+/i', $value );
        return $value;
    }

    /**
     * Convert all characters to uppercase
     */
    public static function toUpperCase( $value=null )
    {
        $value = strtoupper( $value );
        $value = self::_unwhite( $value );
        return $value;
    }

    /**
     * Convert all characters to lowercase
     */
    public static function toLowerCase( $value=null )
    {
        $value = strtolower( $value );
        $value = self::_unwhite( $value );
        return $value;
    }

    /**
     * Convert all words to be capitalized
     */
    public static function toCaps( $value=null, $separators=array() )
    {
        $separators = array_merge( array( '.', '_', '-', '/' ), $separators );
        $value      = self::toText( $value );
        $value      = self::_unwhite( $value );

        foreach( $separators as $sep )
        {
            $sep   = trim( $sep );
            $value = str_replace( $sep, $sep." ", $value );
            $value = str_replace( $sep." ", $sep, ucwords( $value ) );
        }
        return $value;
    }

    /**
     * Convert value to camelCase style string
     */
    public static function toCamelCase( $value=null )
    {
        $value = self::_filter( '/[^a-zA-Z0-9\ ]+/u', $value, ' ' );
        $words  = explode( ' ', self::_unwhite( $value ) );
        $first  = strtolower( array_shift( $words ) );
        $output = $first .' '. ucwords( strtolower( implode( ' ', $words ) ) );
        return str_replace( ' ', '', $output );
    }

    /**
     * Convert value to FullCamelCase style string
     */
    public static function toFullCamelCase( $value=null )
    {
        $value = self::_filter( '/[^a-zA-Z0-9\ ]+/u', $value, ' ' );
        $value = ucwords( strtolower( self::_unwhite( $value ) ) );
        return str_replace( ' ', '', $value );
    }

    /**
     * String to be used as a key [a-zA-Z0-9_.]
     */
    public static function toKey( $value=null )
    {
        $value = self::_filter( '/[^\w\.]+/u', $value, '_' );
        $value = self::_filter( '/\_\_+/u', $value, '_' );
        $value = self::_filter( '/\.\.+/u', $value, '.' );
        $value = trim( $value, '. ' );
        return $value;
    }

    /**
     * String to be used as URL slug [a-zA-Z0-9_-]
     */
    public static function toSlug( $value=null )
    {
        $value = self::_filter( '/[^\w\-]+/u', $value, '-' );
        $value = self::_filter( '/\_\_+/u', $value, '_' );
        $value = self::_filter( '/\-\-+/u', $value, '-' );
        $value = trim( $value, '_- ' );
        return $value;
    }

    /**
     * Common safe characters used in titles, strips whitespace
     */
    public static function toTitle( $value=null )
    {
        $value = self::toText( $value );
        $value = self::_filter( '/[^\w\!\@\#\$\%\^\&\*\(\)\_\+\-\=\{\}\[\]\:\;\"\'\,\.\/\?\ ]+/ui', $value );
        $value = self::_filter( '/\.[a-zA-Z0-9]+$/ui', $value );
        $value = self::_unwhite( $value );
        return $value;
    }

    /**
     * String to be used as a person's name
     */
    public static function toName( $value=null )
    {
        $value = self::_filter( '/[^\p{L}\'\-\ ]+/ui', $value, ' ' );
        $value = self::_unwhite( $value );
        return $value;
    }

    /**
     * Whole number
     */
    public static function toNumber( $value=null )
    {
        $value = self::_filter( '/[^0-9\-]+/', $value );
        $value = self::_assign( $value, 0 );
        return (int) $value;
    }

    /**
     * Decimal number
     */
    public static function toFloat( $value=null )
    {
        $value = self::_filter( '/[^0-9\-\.]+/', $value );
        $value = self::_assign( $value, 0 );
        return (float) $value;
    }

    /**
     * Serializes a value to string
     */
    public static function toString( $value=null )
    {
        if( $value === null )     return 'null';
        if( $value === true )     return 'true';
        if( $value === false )    return 'false';
        if( is_array( $value ) )  return json_encode( $value );
        if( is_object( $value ) ) return serialize( $value );
        return trim( $value );
    }

    /**
     * Unserializes a value to a type
     */
    public static function toType( $value=null )
    {
        $tmp = strtolower( trim( $value ) );

        if( $tmp === 'null' )     return null;
        if( $tmp === 'true' )     return true;
        if( $tmp === 'false' )    return false;
        if( is_numeric( $tmp ) )  return $tmp + 0;

        if( is_string( $value ) )
        {
            if( $array = @json_decode( $value, true ) )
            {
                return $array;
            }
            if( $object = @unserialize( $value ) )
            {
                return $object;
            }
        }
        return $value;
    }

    /**
     * Conver a string or number value to timestamp
     */
    public static function toTime( $value=null )
    {
        if( is_string( $value ) )
        {
            return strtotime( trim( $value ) );
        }
        if( is_numeric( $value ) )
        {
            return intval( $value );
        }
        return time();
    }

    /**
     * Dollar currency value
     */
    public static function toDollar( $value=null, $symbol='$' )
    {
        $value = self::_filter( '/[^0-9\-\.]+/', $value );
        $value = self::_assign( $value, 0.00 );
        $value = number_format( $value, 2, '.', ',' );
        return $symbol . $value;
    }

    /**
     * Common characters present in a phone number
     */
    public static function toPhone( $value=null )
    {
        $value = self::_filter( '/[^0-9\+\-\.]+/', $value );
        return $value;
    }

    /**
     * Common allowed characters for an e-mail address
     */
    public static function toEmail( $value=null )
    {
        $value = self::_filter( '/\@\@+/ui', $value, '@' );
        $value = self::_filter( '/[^\d\p{L}\@\.\_\-\!\#\$\%\&\+\=]+/u', $value );
        return $value;
    }

    /**
     * Common allowed characters for URL address
     */
    public static function toUrl( $value=null )
    {
        $value = str_replace( '\\', '/', trim( $value ) );
        $value = self::_filter( '/[^\d\p{L}\.\_\-\@\#\?\%\&\=\+\/\:\;\~\(\)\{\}\[\]\"\']+/u', $value );
        return $value;
    }

    /**
     * Url hostname
     */
    public static function toHostname( $value=null )
    {
        $value = parse_url( trim( $value ), PHP_URL_HOST );
        return $value;
    }

    /**
     * Twitter style @handle (15 characters limit)
     */
    public static function toHandle( $value=null, $prefix='@' )
    {
        $value = self::_filter( '/[^\w]/u', $value );
        $value = substr( $value, 0, 15 );
        return $prefix . $value;
    }

    /**
     * Twitter style #hashtag (140 characters limit)
     */
    public static function toHashtag( $value=null, $prefix='#' )
    {
        $value = self::_filter( '/[^\d\p{L}\_]/u', $value );
        $value = substr( $value, 0, 140 );
        return $prefix . $value;
    }

    /**
     * Characters present in an MD5 hash string
     */
    public static function toMd5( $value=null )
    {
        $value = self::_filter( '/[^a-fA-F0-9]+/', $value );
        $value = substr( $value, 0, 32 );
        return $value;
    }

    /**
     * IP v4 address
     */
    public static function toIp( $value=null )
    {
        $value = self::_filter( '/[^0-9\.\/]+/', $value );
        $value = self::_filter( '/\.\.+/', $value, '.' );
        $value = self::_filter( '/\/\/+/', $value, '/' );
        return $value;
    }

    /**
     * IP v6 address
     */
    public static function toIpv6( $value=null )
    {
        $value = self::_filter( '/[^a-fA-F0-9\:]+/i', $value );
        $value = self::_filter( '/\:\:+/', $value, ':' );
        $value = trim( $value, ':' );
        return $value;
    }

    /**
     * Filesystem or URL path
     */
    public static function toPath( $value=null )
    {
        $value = rtrim( str_replace( '\\', '/', trim( $value ) ), '/' );
        $value = self::_filter( '/\/\/+/', $value, '/' );
        return $value;
    }

    /**
     * File extension
     */
    public static function toExtension( $value=null )
    {
        $parts = explode( '.', trim( $value ) );
        $value = trim( array_pop( $parts ) );
        $value = preg_replace( '/[^a-zA-Z0-9]+/', '', $value );
        return strtolower( $value );
    }

    /**
     * Common values to be translated into boolean
     */
    public static function toBool( $value=null )
    {
        if( is_bool( $value ) )
        {
            return $value;
        }
        if( is_numeric( $value ) )
        {
            $value = intval( $value );
            return ( $value > 0 ) ? true : false;
        }
        if( is_string( $value ) )
        {
            $value = trim( $value );
            if( preg_match( '/^(1|on|yes|enabled|Y)$/ui', $value ) ) return true;
            if( preg_match( '/^(0|off|no|disabled|N)$/ui', $value ) ) return false;
        }
        return $value;
    }

    /**
     * Auto format HTML links found in a string
     */
    public static function toHtml( $value=null )
    {
        $value = trim( $value );

        $value = preg_replace(
            '/(([a-zA-Z0-9\+\-\.]+)\@([a-zA-Z0-9\.\-]+)\.([a-zA-Z]{2,6}))/is',
            '<a title="Send e-mail" href="mailto:$1" target="_blank">$1</a>',
            $value
        );
        $value = preg_replace(
            '/(([1+-.]{2,3})?\(?([\d]{3})\)?[-. ]?([\d]{3})[-. ]?([\d]{4}))/is',
            '<a title="Call number" href="tel:$1" target="_blank">$1</a>',
            $value
        );
        $value = preg_replace(
            '/((((https?|ftp):\/\/)|(www\.))([\w\_\-\.]+)\.([a-z]{2,6})([\w\/\-\_\.\,\?\=\+\%\&\(\)\[\]\:\;\#\|]+)?)/is',
            '<a title="Open link" href="$1" target="_blank">$1</a>',
            $value
        );
        $value = preg_replace(
            '/[^\w]\@([\w\-]+)/is',
            '<a title="@$1 on Twitter" href="http://www.twitter.com/$1" target="_blank">@$1</a>',
            $value
        );
        $value = preg_replace(
            '/[^\/]\#([\w\-]+)/is',
            '<a title="#$1 on Twitter" href="https://www.twitter.com/search?q=%23$1" target="_blank">#$1</a>',
            $value
        );
        return ' '.$value.' ';
    }

    /**
     * Filter a value with a custom pattern
     */
    protected static function _filter( $pattern=null, $value=null, $replace='' )
    {
        return preg_replace( $pattern, $replace, trim( $value ) );
    }

    /**
     * Remove multiple whitespace from a value
     */
    protected static function _unwhite( $value=null, $replace=' ' )
    {
        return trim( preg_replace( '/[\t\r\n\s\h\v]+/', $replace, $value ) );
    }

    /**
     * Assign a default value if current value is empty
     */
    protected static function _assign( $value=null, $default=null )
    {
        return !empty( $value ) ? $value : $default;
    }

}