<?php
/**
 * Handles encoding/decoding and working with JSON data.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Data;

use Biscuit\Util\Utils;

class Json {

	/**
	 * Checks is a string is an encoded json object
	 */
	public static function isJson( $string=null )
	{
	    if( !is_string( $string ) )
	    {
	        return false;
	    }
	    $string = trim( $string );
	    $left   = substr( $string, 0, 1 );
		$right  = substr( $string, -1 );

	    if( !$left || !$right )
	    {
	        return false;
	    }
	    if( $left !== '{' && $left !== '[' )
	    {
	        return false;
	    }
	    if( $right !== '}' && $right !== ']' )
	    {
	        return false;
	    }
	    if( function_exists( 'json_last_error' ) )
	    {
	    	@json_decode( $string );
	    	return json_last_error() === JSON_ERROR_NONE;
	    }
	    return false;
	}

	/**
	 * Encodes a PHP array into a JSON string
	 */
	public static function encode( $data=null, $stripslashes=false )
	{
		$output = '';

		if( is_string( $data ) )
		{
			$output = trim( $data );
		}
		else if( is_array( $data ) )
		{
			$string = @json_encode( $data );

			if( !empty( $string ) )
			{
				$output = trim( $string );
			}
		}
		if( !empty( $stripslashes ) )
		{
			return stripslashes( $output );
		}
		return $output;
	}

	/**
	 * Decodes a JSON string into a PHP array
	 */
	public static function decode( $string=null, $default=array() )
	{
		$output = array();

		if( is_array( $string ) )
		{
			$output = $string;
		}
		else if( is_string( $string ) )
		{
			$string = trim( $string );
			$string = get_magic_quotes_runtime() ? stripslashes( $string ) : $string;
			$data   = @json_decode( $string, true );

			if( is_array( $data ) )
			{
				$output = $data;
			}
		}
		if( !empty( $default ) && is_array( $default ) )
		{
			$output = array_merge( $default, $output );
		}
		return $output;
	}

	/**
	 * Merges data with an encoded JSON string
	 */
	public static function merge( $string='', $data=array() )
	{
		$decoded = self::decode( $string );
		$data    = Utils::deepMerge( $decoded, $data );
		return self::encode( $data );
	}

}







