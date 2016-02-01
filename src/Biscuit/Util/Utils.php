<?php
/**
 * Collection of static methods for handling common tasks.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Util;

class Utils {

    /**
     * Returns a fallback value if a given non-numerical value is empty
     */
    public static function getValue( $value=null, $fallback='', $trim=false )
    {
        if( is_null( $value ) || $value === false || $value === '' )
        {
            return $fallback;
        }
        return $trim ? trim( $value ) : $value;
    }

    /**
     * Returns an array
     */
    public static function getArray( $array=null, $default=[] )
    {
        return is_array( $array ) ? $array : $default;
    }

    /**
     * Get a value for an array key, or default value
     */
    public static function getArrayValue( $array=[], $key='', $default='' )
    {
        $array = self::getArray( $array );

        if( !empty( $key ) && array_key_exists( $key, $array ) )
        {
            return $array[ $key ];
        }
        return $default;
    }

    /**
     * Returns a single key/value pair from an associative array by index
     */
    public static function getArrayPair( $array=[], $index=0 )
    {
        $array = self::getArray( $array );
        $total = count( $array );
        $first = 0;
        $last  = ( $total > 0 ) ? ( $total - 1 ) : 0;

        $index = is_numeric( $index ) ? (int) $index : $first;
        $index = ( $index < $first )  ? $first : $index;
        $index = ( $index > $last )   ? $last  : $index;

        $key   = '';
        $value = null;
        $count = 0;

        foreach( $array as $key => $value )
        {
            if( $count === $index ){ break; } $count++;
        }
        return array( $key, $value );
    }

    /**
     * Builds a query string from a given data array
     */
    public static function getQueryString( $data=[], $prefix='' )
    {
        $output = '';

        if( !empty( $data ) && is_array( $data ) )
        {
            $string = http_build_str( $data, $prefix );

            if( !empty( $string ) )
            {
                $output = trim( $string );
            }
        }
        return $output;
    }

    /**
     * Build an attributes string from a given data array
     */
    public static function getAttributes( $data=[] )
    {
        $output = '';

        if( !empty( $data ) && is_array( $data ) )
        {
            foreach( $data as $key => $value )
            {
                if( is_numeric( $key ) ) continue;

                $key   = preg_replace( '/[^\w\-]+/i', '-', $key );
                $key   = preg_replace( '/[\-]+/i', '-', $key );
                $key   = trim( $key, '-' );

                $value = stripslashes( trim( $value ) );
                $value = str_replace( '"', '\\"', $value );
                $value = htmlspecialchars( $value );

                if( !empty( $key ) && !empty( $value ) )
                {
                    $output .= $key . '="'. $value .'" ';
                }
            }
        }
        return trim( $output );
    }

    /**
     * Recursive merging for arrays
     */
    public static function deepMerge( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach( $array2 as $key => &$value )
        {
            if( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) )
            {
                $merged[$key] = self::deepMerge( $merged[$key], $value );
            }
            else
            {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * Add spacers to arry keys so they all have the same string length
     */
    function padArrayKeys( $pairs=array(), $spacer='.', $delimeter=':' )
    {
        $output = array();
        $maxlen = 0;

        foreach( $pairs as $key => $value )
        {
            $l = strlen( $key );
            if( $l > $maxlen ) $maxlen = $l;
        }
        foreach( $pairs as $key => $value )
        {
           $key = str_pad( $key, $maxlen + 3, $spacer, STR_PAD_RIGHT ) . $delimeter;
           $output[ $key ] = $value;
        }
        return $output;
    }


}

