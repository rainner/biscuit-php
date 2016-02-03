<?php
/**
 * Handles working with timestamps.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Util;

class Time {

    /**
     * Converts a past-timestamp into a readable sentence
     */
    public static function toElapsed( $time=0 )
    {
        $time    = Sanitize::toTime( $time );
        $elapsed = time() - $time;
        $tokens  = self::_getTokens();

        if( $elapsed > 0 )
        {
            if( $elapsed > 60 )
            {
                foreach( $tokens as $unit => $word )
                {
                    if( $elapsed < $unit ) continue;
                    $amount = floor( $elapsed / $unit );
                    return Numeric::toNoun( $amount, $word, $word.'s' ) .' ago';
                }
            }
            return 'less than a minute ago';
        }
        return 'just now';
    }

    /**
     * Converts past time and a wait period into a countdown sentence
     */
	public static function toCountdown( $time=0, $wait=300 )
	{
        $time    = Sanitize::toTime( $time );
        $elapsed = $time - ( time() - $wait );
        $tokens  = self::_getTokens();

        if( $elapsed > 0 )
        {
            foreach( $tokens as $unit => $word )
            {
                if( $elapsed < $unit ) continue;
                $amount = floor( $elapsed / $unit );
                return Numeric::toNoun( $amount + 1, $word, $word.'s' );
            }
        }
        return '0 seconds';
	}

	/**
     * Converts a past time into an age sentence
     */
	public static function toAge( $time=0 )
	{
        $time    = Sanitize::toTime( $time );
        $elapsed = time() - $time;
        $tokens  = self::_getTokens();

        if( $elapsed > 0 )
        {
            foreach( $tokens as $unit => $word )
            {
                if( $elapsed < $unit ) continue;
                $amount = floor( $elapsed / $unit );
                return Numeric::toNoun( $amount, $word, $word.'s' ) .' old';
            }
        }
        return '0 years old';
	}

    /**
     * Returns a list of months, days and years, for use as a date-of-birth selector
     */
    public static function getDobList( $min=10, $max=50 )
    {
        $time   = time();
        $year   = date( 'Y', $time );
        $start  = intval( $year ) - intval( $min );
        $max    = intval( $max );
        $output = array(

            'months' => array(
                '01'  => 'January',
                '02'  => 'February',
                '03'  => 'March',
                '04'  => 'April',
                '05'  => 'May',
                '06'  => 'June',
                '07'  => 'July',
                '08'  => 'August',
                '09'  => 'September',
                '10'  => 'October',
                '11'  => 'November',
                '12'  => 'Decenber'
            ),
            'days'  => array(),
            'years' => array(),
        );

        for( $i = 1; $i <= 31; $i++ )
        {
            $d = self::_padZero( $i );
            $output[ 'days' ][ $d ] = $d;
        }
        for( $i = $start; $i >= ( $year - $max ); $i-- )
        {
            $age = ( $year - $i );
            $output[ 'years' ][ $i ] = $i .' ('.$age.')';
        }
        return $output;
    }

    /**
	 * Prepend a zero before numbers < 10
	 */
	private static function _padZero( $num=0 )
	{
		return ( intval( $num ) < 10 ) ? '0'.$num : ''.$num;
	}

    /**
     * Map of seconds to word
     */
    private static function _getTokens()
    {
        return array(
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second'
        );
    }

}
