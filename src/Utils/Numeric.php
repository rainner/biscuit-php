<?php
/**
 * For working with numbers and currency values.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

class Numeric {

    /**
     * Get a human readable size from total bytes
     */
    public static function toBytes( $bytes=0, $decimals=2, $append="b" )
    {
        $bytes    = ceil( Sanitize::toFloat( $bytes ) );
        $letters  = array( "","K","M","G","T","P" );
        $factor   = (int) 0 + floor( ( strlen( trim( $bytes ) ) - 1 ) / 3 );
        $decimals = ( $bytes < 1024 ) ? 0 : $decimals;
        return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) ." ". $letters[ $factor ] . trim( $append );
    }

    /**
     * Return singular or plutal noun based on a number
     */
    public static function toNoun( $count=0, $singular="item", $plutal="items" )
    {
        $count = Sanitize::toNumber( $count );
        $word  = ( $count === 1 ) ? trim( $singular ) : trim( $plutal );
        return trim( number_format( $count ) ." ". $word );
    }

    /**
     * Converts a numeric value to a whole number (round)
     */
    public static function toRounded( $value=0 )
    {
        $value = Sanitize::toFloat( $value );
        return (int) round( $value, 0 );
    }

    /**
     * Converts a numeric float/currency value to a signed value in pennies
     */
    public static function toPennies( $value=0 )
    {
        $value = Sanitize::toFloat( $value );
        $value = function_exists( "bcmul" )
            ? bcmul( $value, 100 )
            : number_format( $value * 100, 0, '.', '' );
        return (int) $value;
    }

    /**
     * Calculates percentage amount for value of total
     */
    public static function toPercent( $value=0, $total=0, $places=1, $symbol="%" )
    {
        $value   = Sanitize::toFloat( $value );
        $total   = Sanitize::toFloat( $total );
        $percent = ( $total > 0 ) ? ( $value / $total ) * 100 : 0;
        return trim( number_format( $percent, $places, ".", "" ) . $symbol );
    }

    /**
     * Converts a numeric value to a signed currency string
     */
    public static function toCurrency( $value=0, $decimal=".", $thousand=",", $symbol="$" )
    {
        $value = Sanitize::toFloat( $value );
        return trim( $symbol . number_format( $value, 2, $decimal, $thousand ) );
    }

    /**
     * Extracts the first 5 digits of a zip-code
     */
    public static function toZip5( $value="" )
    {
        $value = preg_replace( "/[^\d]+/", "", trim( $value ) );
        $value = ( strlen( $value ) >= 5 ) ? substr( $value, 0, 5 ) : "";
        return trim( $value );
    }

    /**
     * Extracts the last 4 digits of a zip-code
     */
    public static function toZip4( $value="" )
    {
        $value = preg_replace( "/[^\d\-]+/", "", trim( $value ) );
        $value = preg_match( "/\-[\d]{4}$/u", $value ) ? substr( $value, -4, 4 ) : "";
        return trim( $value );
    }

    /**
     * Adds leading zeros to the begining of a number if it is less than a length
     */
    public static function padZero( $value=0, $length=0 )
    {
        $value = Sanitize::toNumber( $value );
        $value = self::unpadZero( $value );
        $value = ( $length > 0 ) ? sprintf( "%0".$length."d", $value ) : "";
        return $value;
    }

    /**
     * Removes leading zeros from a number
     */
    public static function unpadZero( $value=0 )
    {
        $value = Sanitize::toNumber( ltrim( $value, "0" ) );
        return (int) $value;
    }

    /**
     * Conver a string or number value to timestamp
     */
    public static function toTimestamp( $value="" )
    {
        if( is_numeric( $value ) )
        {
            return intval( $value );
        }
        if( is_string( $value ) && $time = @strtotime( $value ) )
        {
            return $time;
        }
        return time();
    }

    /**
     * Converts a past-timestamp into a date string
     */
    public static function toDate( $time=0, $format="F jS, Y", $default="Never" )
    {
        if( $time )
        {
            $stamp  = self::toTimestamp( $time );
            $format = Utils::value( $format, "F jS, Y" );

            if( $stamp > 0 )
            {
                return date( $format, $stamp );
            }
        }
        return $default;
    }

    /**
     * Converts a past-timestamp into a readable sentence
     */
    public static function toElapsed( $time=0, $default="just now" )
    {
        if( $time )
        {
            $stamp   = self::toTimestamp( $time );
            $elapsed = time() - $stamp;

            if( $elapsed > 0 )
            {
                if( $elapsed > 60 )
                {
                    foreach( self::_tokens() as $unit => $word )
                    {
                        if( $elapsed < $unit ) continue;
                        $amount = floor( $elapsed / $unit );
                        return Numeric::toNoun( $amount, $word, $word."s" ) ." ago";
                    }
                }
                return "less than a minute ago";
            }
        }
        return $default;
    }

    /**
     * Converts past time and a wait period into a countdown sentence
     */
    public static function toCountdown( $time=0, $wait=0 )
    {
        if( $time )
        {
            $stamp   = self::toTimestamp( $time );
            $elapsed = $stamp - ( time() - $wait );

            if( $elapsed > 0 )
            {
                foreach( self::_tokens() as $unit => $word )
                {
                    if( $elapsed < $unit ) continue;
                    $amount = ceil( $elapsed / $unit );
                    return Numeric::toNoun( $amount, $word, $word."s" );
                }
            }
        }
        return "0 seconds";
    }

    /**
     * Converts a past time into an age sentence
     */
    public static function toAge( $time=0 )
    {
        if( $time )
        {
            $stamp   = self::toTimestamp( $time );
            $elapsed = time() - $stamp;

            if( $elapsed > 0 )
            {
                foreach( self::_tokens() as $unit => $word )
                {
                    if( $elapsed < $unit ) continue;
                    $amount = floor( $elapsed / $unit );
                    return Numeric::toNoun( $amount, $word, $word."s" ) ." old";
                }
            }
        }
        return "0 years old";
    }

    /**
     * Returns a list of months, days and years, for use as a date-of-birth selector
     */
    public static function getDobList( $min=10, $max=80 )
    {
        $time   = time();
        $year   = date( "Y", $time );
        $start  = intval( $year ) - intval( $min );
        $max    = intval( $max );
        $output = array(

            "months" => array(
                "01"  => "January",
                "02"  => "February",
                "03"  => "March",
                "04"  => "April",
                "05"  => "May",
                "06"  => "June",
                "07"  => "July",
                "08"  => "August",
                "09"  => "September",
                "10"  => "October",
                "11"  => "November",
                "12"  => "Decenber"
            ),
            "days"  => array(),
            "years" => array(),
        );

        for( $i = 1; $i <= 31; $i++ )
        {
            $d = Numeric::padZeros( $i );
            $output[ "days" ][ $d ] = $d;
        }
        for( $i = $start; $i >= ( $year - $max ); $i-- )
        {
            $age = ( $year - $i );
            $output[ "years" ][ $i ] = $i ." (".$age.")";
        }
        return $output;
    }

    /**
     * Map of seconds to word
     */
    private static function _tokens()
    {
        return array(
            31536000 => "year",
            2592000  => "month",
            604800   => "week",
            86400    => "day",
            3600     => "hour",
            60       => "minute",
            1        => "second"
        );
    }

}

