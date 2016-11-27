<?php
/**
 * Provides a list of timezone keys and info.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use DateTimeZone;
use DateTime;

class Timezone {

	public static function getList()
	{
	    $list       = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
	    $utc        = new DateTime( "now", new DateTimeZone( "UTC" ) );
	    $tzone_list = [ "UTC" => "(UTC) Universal Time Code " ];
	    $tmp_list   = [];

	    foreach( $list as $tzid )
	    {
	    	if( $tzid === "UTC" ) continue;

	        $dtz    = new DateTimeZone( $tzid );
	        $offset = $dtz->getOffset( $utc );
	        $sign   = ( $offset > 0 ) ? "+" : "-";
	        $time   = $sign . gmdate( "H:i", abs( $offset ) );
	        $info   = str_replace( "/", ", ", $tzid );
	        $info   = str_replace( "_", " ", $info );

	        $tmp_list[ $tzid ] = [
	        	"tzid" => $tzid,
	        	"time" => $time,
	        	"info" => $info,
	        ];
	    }
	    ksort( $tmp_list );

	    foreach( $tmp_list as $tz )
	    {
	    	$tzone_list[ $tz["tzid"] ] = "(UTC ".$tz["time"].") ". $tz["info"];
	    }
		return $tzone_list;
	}
}
