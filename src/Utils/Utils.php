<?php
/**
 * Utils class
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use Biscuit\Http\Server;

class Utils {

    /**
     * Resolve one of the given arguments as a value, using the last argument as the default value
     */
    public static function value()
    {
        $args   = func_get_args();
        $output = array_pop( $args );

        foreach( $args as $value )
        {
            if( is_string( $value ) ) $value = trim( $value );
            if( is_null( $value ) || $value === "" ) continue;
            $output = $value; break;
        }
        return $output;
    }

    /**
     * Merge arguments together if they are arrays
     */
    public static function merge()
    {
        $output = [];

        foreach( func_get_args() as $arg )
        {
            $output = array_merge( $output, Sanitize::toArray( $arg ) );
        }
        return $output;
    }

    /**
     * Split value into an array of string values
     */
    public static function split( $value=null, $sep="," )
    {
        $output = [];

        if( is_string( $value ) || is_numeric( $value ) )
        {
            $output = explode( $sep, trim( $value, $sep." " ) );
        }
        else if( is_array( $value ) )
        {
            foreach( $value as $next )
            {
                $output = array_merge( $output, self::split( $next ) );
            }
        }
        foreach( $output as $i => $final )
        {
            $output[ $i ] = trim( $final );
        }
        return $output;
    }

    /**
     * Contact array into inline string
     */
    public static function concat( $value )
    {
        $output = "";

        if( is_array( $value ) )
        {
            foreach( $value as $entry )
            {
                $output .= " ".self::concat( $entry );
            }
        }
        else if( is_string( $value ) || is_numeric( $value ) )
        {
            $output .= " ".trim( $value );
        }
        return Sanitize::toSingleSpaces( $output );
    }

    /**
     * Checks if a value exists in a dataset, or itself (fallback)
     */
    public static function exists( $value=null, $dataset=null )
    {
        if( !empty( $value ) )
        {
            if( is_array( $dataset ) )
            {
                if( array_key_exists( $value, $dataset ) )
                {
                    return true; // array key found
                }
                if( in_array( $value, $dataset ) )
                {
                    return true; // value found in array
                }
                return false; // not found in array
            }
            return true; // value not empty
        }
        return false; // value empty
    }

    /**
     * Convert an associative array into a attributes string
     */
    public static function attributes( $list=[] )
    {
        $atts = [];

        if( !empty( $list ) && is_array( $list ) )
        {
            foreach( $list as $key => $value )
            {
                $key = Sanitize::toKey( $key );
                $value = self::escape( $value, '"' );
                if( is_numeric( $key ) ) continue;
                $atts[] = $key .'="'. $value .'"';
            }
        }
        return implode( " ", $atts );
    }

    /**
     * Serializes a value to string
     */
    public static function serialize( $value=null )
    {
        if( $value === null )  return "null";
        if( $value === true )  return "true";
        if( $value === false ) return "false";

        if( is_array( $value ) )
        {
            return json_encode( $value );
        }
        if( is_object( $value ) )
        {
            return serialize( $value );
        }
        return trim( $value );
    }

    /**
     * Unserializes a value to a type
     */
    public static function unserialize( $value=null )
    {
        if( is_string( $value ) )
        {
            $tmp = strtolower( trim( $value ) );
            if( $tmp === "null" )     return null;
            if( $tmp === "true" )     return true;
            if( $tmp === "false" )    return false;
            if( is_numeric( $tmp ) )  return $tmp + 0;

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
     * Search for array keys in a string and replaces them with the value
     */
    public static function render( $value="", $pairs=[], $open="", $close="" )
    {
        $value = Sanitize::toString( $value );
        $pairs = Sanitize::toArray( $pairs );
        $open  = self::value( $open, "%" );
        $close = self::value( $close, "%" );

        foreach( $pairs as $k => $v )
        {
            $value = str_replace( $open.$k.$close, $v, $value );
        }
        return $value;
    }

    /**
     * Filter a value with a custom pattern
     */
    public static function replace( $value="", $pattern="", $replace="" )
    {
        $value   = Sanitize::toString( $value );
        $pattern = Sanitize::toString( $pattern );
        $output  = @preg_replace( $pattern, $replace, $value );
        $value   = is_string( $output ) ? $output : $value;
        return $value;
    }

    /**
     * Escapes a string for safe usage
     */
    public static function escape( $value="", $char="'" )
    {
        $value = Sanitize::toString( $value );
        $value = self::unescape( $value );
        $value = str_replace( $char, "\\".$char, $value );
        return $value;
    }

    /**
     * Remove all back slashes from the working string
     */
    public static function unescape( $value="" )
    {
        $value = Sanitize::toString( $value );
        $value = stripslashes( implode( "", explode( "\\", $value ) ) );
        return $value;
    }

    /**
     * Cleans a string and cuts a section out the end to make it shorter
     */
    public static function shrink( $value="", $length=30, $suffix="..." )
    {
        $value  = Sanitize::toText( $value );
        $length = ( $length - strlen( trim( $suffix ) ) );

        if( strlen( $value ) > $length )
        {
            $value = substr( $value, 0, $length ) . $suffix;
        }
        return $value;
    }

    /**
     * Fills a space to the right of a string with a number of characters
     */
    public static function fill( $value="", $limit=30, $char=".", $last=":" )
    {
        $value  = Sanitize::toText( $value );
        $strlen = strlen( $value ) + strlen( $last );
        $count  = ( $strlen > $limit ) ? ( $strlen - $limit ) : ( $limit - $strlen );
        return $value . str_repeat( $char, $count ) . $last;
    }

    /**
     * Picks a chunk out of some a $(d)ata string between $(b)egin and $(e)nd
     */
    public static function crop( $d="", $b="", $e="" )
    {
        $value = substr( $d, strpos( $d, $b ) + strlen( $b ), strpos( $d, $e, strpos( $d, $b ) ) - strpos( $d, $b ) - strlen( $b ) );
        return $value;
    }

    /**
     * Grab a user"s Gravatar image for a specified email
     */
    public static function gravatar( $email="", $default="" )
    {
        $email   = strtolower( trim( $email ) );
        $default = !empty( $default ) ? urlencode( $default ) : "mm";

        return "https://www.gravatar.com/avatar/".md5( $email )."?".http_build_query( array(
            "s" => "200",
            "r" => "x",
            "d" => $default
        ));
    }

    /**
     * Resolve card type from number
     */
    public static function cardType( $number )
    {
        $number = preg_replace( "/[^\d]/", "", trim( $number ) );

        if( !empty( $number ) )
        {
            if( preg_match( "/^3[47][0-9]{13}$/", $number ) )
            {
                return "American Express";
            }
            if( preg_match( "/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/", $number ) )
            {
                return "Diners Club";
            }
            if( preg_match( "/^6(?:011|5[0-9][0-9])[0-9]{12}$/", $number ) )
            {
                return "Discover";
            }
            if( preg_match( "/^(?:2131|1800|35\d{3})\d{11}$/", $number ) )
            {
                return "JCB";
            }
            if( preg_match( "/^5[1-5][0-9]{14}$/", $number ) )
            {
                return "MasterCard";
            }
            if( preg_match( "/^4[0-9]{12}(?:[0-9]{3})?$/", $number ) )
            {
                return "Visa";
            }
        }
        return "Unknown";
    }

    /**
     * get a md5 hash for card name/data
     */
    public static function cardHash( $name, $number )
    {
        if( !empty( $name ) && !empty( $number ) )
        {
            return md5( $name ." @ ". $number );
        }
        return md5( uniqid( true ) );
    }

    /**
     * Build URL path from arguments
     */
    public static function buildPath()
    {
        $path = [];

        foreach( func_get_args() as $item )
        {
            if( !empty( $item ) )
            {
                $path[] = trim( $item, "/ " );
            }
        }
        return "/".implode( "/", $path );
    }

    /**
     * Take a full path and make relative to the application root
     */
    public static function relativePath( $path )
    {
        if( !empty( $path ) && is_string( $path ) )
        {
            $path   = Sanitize::toPath( $path );
            $root   = Server::getScriptPath();
            $levels = array(
                $root,
                dirname( $root ),
                dirname( dirname( $root ) ),
                dirname( dirname( dirname( $root ) ) )
            );
            foreach( $levels as $level )
            {
                if( empty( $level ) || $levels === "/" ) continue;
                $path = str_replace( $level, "", $path );
            }
        }
        return $path;
    }

    /**
     * Load configuration data from a folder using filename as array key
     */
    public static function loadConfigs( $path )
    {
        $path   = Sanitize::toPath( $path );
        $output = [];

        if( !empty( $path ) && is_dir( $path ) )
        {
            foreach( glob( $path."/*.php" ) as $file )
            {
                $key   = Sanitize::toKey( basename( $file, ".php" ) );
                $value = include_once( $file );
                $output[ $key ] = $value;
            }
        }
        return $output;
    }

    /**
     * Load list of table queries by scanning files from a folder
     */
    public static function loadQueries( $path )
    {
        $path   = Sanitize::toPath( $path );
        $output = [];

        if( !empty( $path ) && is_dir( $path ) )
        {
            foreach( glob( $path."/*.php" ) as $file )
            {
                $table = Sanitize::toKey( basename( $file, ".php" ) );
                $queries = [];
                include_once( $file );

                $output[ $table ] = $queries;
            }
        }
        return $output;
    }

    /**
     * Get list of loaded PHP extensions
     */
    public static function loadedExtensions()
    {
        $list = [];
        foreach( get_loaded_extensions() as $ext )
        {
            $ext = strtolower( trim( $ext ) );
            $list[ $ext ] = $ext;
        }
        ksort( $list );
        return $list;
    }

    /**
     * Check if extension is loaded by name
     */
    public static function hasExtension( $ext )
    {
        if( !empty( $ext ) && is_string( $ext ) )
        {
            $ext = strtolower( trim( $ext ) );
            return array_key_exists( $ext, self::loadedExtensions() );
        }
        return false;
    }

    /**
     * Dump data for debugging
     */
    public static function dump()
    {
        $html = "";

        foreach( func_get_args() as $value )
        {
            $html .= "<div style=\"background: #ffc; margin: 20px; padding: 20px;\"> \n";
            $html .= "  <pre> \n";
            $html .= "      " . print_r( $value, true ) ." \n";
            $html .= "  </pre> \n";
            $html .= "</div> \n";
        }
        echo trim( $html );
        exit;
    }

}
