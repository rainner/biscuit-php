<?php
/**
 * Handles managing persistance of user timezone.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Boot;

use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;
use Biscuit\Util\Expose;
use DateTimeZone;
use DateTime;

class Timezone {

	// timezone string identifier
    protected $timezone = 'UTC';

    // timezone offset seconds from UTC
    protected $offset = 0;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// void
	}

	/**
	 * Set script timezone using string value
	 */
	public function setTimezoneString( $timezone='' )
	{
		$timezone = trim( $timezone );

		if( !empty( $timezone ) )
		{
			$date = new DateTime( 'now', new DateTimeZone( $timezone ) );
			$this->timezone = $timezone;
			$this->offset   = $date->getOffset();
			return @date_default_timezone_set( $timezone );
		}
		return false;
	}

	/**
	 * Set default timezone using offset seconds
	 */
	public function setTimezoneOffset( $offset=0, $dlst=false )
	{
		$offset = intval( $offset );

		if( $timezone = @timezone_name_from_abbr( "", $offset, $dlst ) )
		{
			$this->timezone = $timezone;
			$this->offset   = $offset;
			return @date_default_timezone_set( $timezone );
		}
		return false;
	}

	/**
	 * Get a list of timezone identifiers and UTC offset values for each
	 */
	public function getTimezoneList()
	{
		$output = array();

		foreach( (array) DateTimeZone::listIdentifiers() as $timezone )
		{
		    $date    = new DateTime( 'now', new DateTimeZone( $timezone ) );
		    $offset  = $date->getOffset();
		    $minutes = $offset / 60;
		    $hours   = $offset / 60 / 60;

		    $output[ $timezone ] = array(
		        'timezone' => $timezone,
		        'seconds'  => $offset,
		        'minutes'  => $minutes,
		        'hours'    => $hours,
		    );
		}
		return $output;
	}

	/**
	 * Look for timezone offset value saved in cookie by JS (in seconds)
	 */
	public function lookupJsCookie( $name='' )
	{
		$name  = Sanitize::toKey( $name );
		$value = Utils::getValue( @$_COOKIE[ $name ], null );

		if( is_numeric( $value ) )
		{
			$this->setTimezoneOffset( intval( $value ) );
		}
	}

	/**
	 * Get current timezone string being used
	 */
	public function getTimezone()
	{
		return $this->timezone;
	}

	/**
	 * Get current timezone offset being used
	 */
	public function getOffset()
	{
		return $this->offset;
	}

}

