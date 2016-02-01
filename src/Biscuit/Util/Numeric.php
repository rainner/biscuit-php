<?php
/**
 * For working with numbers and currency values.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Util;

class Numeric {

    /**
     * Get a human readable size from total bytes
     */
    public static function toSize( $bytes=0, $decimals=2, $append='b' )
    {
        $bytes    = intval( $bytes );
        $letters  = array( '','K','M','G','T','P' );
        $factor   = (int) 0 + floor( ( strlen( trim( $bytes ) ) - 1 ) / 3 );
        $decimals = ( $bytes < 1024 ) ? 0 : $decimals;
        return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) .' '. $letters[ $factor ] . trim( $append );
    }

    /**
     * Return singular or plutal noun based on a number
     */
    public static function toNoun( $count=0, $singular='', $plutal='' )
    {
        $word = ( intval( $count ) === 1 ) ? trim( $singular ) : trim( $plutal );
        return trim( number_format( $count ) .' '. $word );
    }

    /**
     * Converts the current number to integer
     */
    public static function toInt( $value=0 )
    {
        $value = preg_replace( '/^[^\d]+/i', '', trim( $value ) );
        $value = intval( $value );
        return $value;
    }

    /**
     * Converts the current number to float
     */
    public static function toFloat( $value=0 )
    {
        if( strstr( $value , ',' ) )
        {
            $value = str_replace( '.', '',  $value  );  // replace dots (thousand seps) with blancs
            $value = str_replace( ',', '.', $value  );  // replace ',' with '.'
        }
        if( preg_match( '/([\d\.]+)/i', $value, $match ) ){ $value = (float) @$match[0]; }
        else{ $value = (float) $value; }
        return $value;
    }

    /**
     * Converts the current number to be formatted with a deciamal dot
     */
    public static function toDecimal( $value=0, $places=2 )
    {
        $value = number_format( $value, $places, '.', '' );
        $value = self::toFloat( $value );
        return $value;
    }

    /**
     * Extracts the first 5 digits of the current number to be used as a zip-code
     */
    public static function toZip5( $value=0 )
    {
        $value = preg_replace( '/[^0-9]+/', '', trim( $value ) );
        $value = substr( ''.$value, 0, 5 );
        $value = self::toInt( $value );
        return $value;
    }

    /**
     * Will check a zipcode string and return 4 digit zip extension, if it has one
     */
    public static function toZip4( $value=0 )
    {
        $value = preg_replace( '/[^0-9\-]+/i', '', $value );
        $value = preg_match( '/\-[0-9]{4}$/', $value ) ? substr( ''.$value, -4, 4 ) : '';
        return $value;
    }

    /**
	 * Adds leading zeros to the begining of a number if it is less than a length
	 */
	public static function padZeros( $value=0, $length=2 )
	{
        if( intval( $length ) > 0 )
        {
            $value = self::unpadZeros( $value );
            $value = sprintf( '%0'.$length.'d', $value );
        }
		return $value;
	}

    /**
	 * Removes leading zeros from a number
	 */
	public static function unpadZeros( $value=0 )
	{
		$value = ltrim( $value, '0' );
		return $value;
	}

    /**
     * Get percentage of added rate
     */
    public static function getRatePercent( $value=0.0, $rate=0.0 )
    {
        $value  = self::toFloat( $value );
        $rate   = self::toFloat( $rate );
        $output = 0;

        if( !empty( $value ) && !empty( $rate ) )
        {
            $output = round( ( $rate / $value ) * 100, 2 );
        }
        return $output;
    }

	/**
     * Get amount of added rate
	 */
	public static function getRateValue( $value=0, $rate=0.00 )
	{
        $value  = self::toFloat( $value );
		$rate   = self::toFloat( $rate );
		$output = 0;

		if( !empty( $value ) && !empty( $rate ) )
		{
			$output = round( $value * $rate, 2 );
		}
		return $output;
	}

    /**
	 * Returns a number of days for a unix timestamp ahead of the current time
	 */
    public static function getExpireDays( $future=0 )
    {
        $time = time();
        if( empty( $future ) || $future <= $time ) return 0;
        $expire = ( intval( $future ) - $time );
        $days = floor( $expire / ( 60 * 60 * 24 ) );
        return $days;
    }

    /**
     * Takes numbers as arguments and adds them
     */
    public static function add()
    {
        $args  = func_get_args();
        $value = 0;

        foreach( $args as $number )
        {
            if( is_numeric( $number ) )
            {
                $value += floatval( $number );
            }
        }
        return $value;
    }

    /**
     * Takes numbers as arguments and subtracts them
     */
    public static function subtract()
    {
        $args  = func_get_args();
        $value = 0;

        if( count( $args ) )
        {
            $value = floatval( array_shift( $args ) );

            foreach( $args as $number )
            {
                $value -= floatval( $number );
            }
        }
        return $value;
    }
}

