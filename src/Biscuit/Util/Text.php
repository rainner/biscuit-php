<?php
/**
 * Provides chained methods for working with a string value.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Util;

class Text {

	// the working string
	protected $str = '';

	/**
	 * Constructor
	 */
	public function __construct( $string='' )
	{
        $this->setString( $string );
	}

    /**
     * Returns the final string
     */
    public function __toString()
    {
        return trim( $this->str );
    }

	/**
	 * Static way of initializing this class
	 */
	public static function set( $string='' )
	{
		return new Text( $string );
	}

    /**
    * Sets a new working string
    */
    public function setString( $string='' )
    {
        if( is_string( $string ) || is_numeric( $string ) )
        {
            $internal  = mb_internal_encoding();
            $external  = mb_detect_encoding( $string, mb_detect_order(), true );

            $this->str = iconv( $external, $internal, $string );
            $this->str = preg_replace( "/\x{20}/u", " ", $this->str );
            $this->str = preg_replace( "/\x{2026}/u", "...", $this->str );
            $this->str = preg_replace( "/[\x{201C}\x{201D}]/u", '"', $this->str );
            $this->str = preg_replace( "/[\x{2018}\x{2019}]/u", "'", $this->str );
            $this->str = preg_replace( "/[\x{2013}\x{2014}]/u", "-", $this->str );
            $this->str = str_replace( "& ", "&amp; ", $this->str );
            $this->str = trim( $this->str );
        }
        return $this;
    }

    /**
     * Remove all special characters
     */
    public function noSpecials()
    {
        $this->str = preg_replace( "/[^a-zA-Z0-9\ ]+/i", " ", $this->str );
        $this->singleSpaces();
        return $this;
    }

	/**
	 * Removes all whitespaces from a string
	 */
	public function noSpaces()
	{
		$this->str = preg_replace( "/[\t\r\n\s\ ]+/i", "", $this->str );
		return $this;
	}

    /**
     * Removes all whitespace and only allows single spaces
     */
    public function singleSpaces()
    {
        $this->str = preg_replace( "/[\t\r\n\s\ ]+/i", " ", $this->str );
        $this->str = trim( preg_replace( "/\s\s+/i", " ", $this->str ) );
        return $this;
    }

    /**
	 * Encodes HTML characters
	 */
	public function htmlEncode()
	{
		$this->str = htmlentities( $this->str, ENT_NOQUOTES, $this->encoding );
		return $this;
	}

	/**
	 * Decodes encoded characters
	 */
	public function htmlDecode()
	{
		$this->str = str_replace( "&nbsp;", " ", $this->str );
		$this->str = html_entity_decode( $this->str, ENT_QUOTES, $this->encoding );
		return $this;
	}

	/**
	 * Convert a string to have special characters for use in forms and such
	 */
	public function htmlSpecial()
	{
		$this->str = htmlspecialchars( $this->str );
		return $this;
	}

    /**
	 * Strips out HTML markup from a string
	 */
	public function htmlStrip( $allow='' )
	{
		$this->str = strip_tags( $this->str, $allow );
		return $this;
	}

	/**
	 * Remove all back slashes from the working string
	 */
	public function unescape()
	{
        $this->str = stripslashes( implode( "", explode( "\\", $this->str ) ) );
		return $this;
	}

    /**
     * escapes a string for safe usage
     */
    public function escape()
    {
        $this->unescape();
        $this->str = str_replace( "'", "\'", $this->str );
        return $this;
    }

    /**
     * Cleans a string and cuts a section out the end to make it shorter
     */
    public function shrink( $length=30, $suffix='...' )
    {
        $this->htmlStrip();
        $length = ( $length - strlen( trim( $suffix ) ) );

        if( strlen( $this->str ) > $length )
        {
            $this->str = substr( $this->str, 0, $length ) . $suffix;
        }
        return $this;
    }

    /**
     * Picks a chunk out of a string between $(b)egin and $(e)nd
     */
    public function crop( $b='', $e='' )
    {
        $d = $this->str;
        $this->str = substr( $d, strpos( $d, $b ) + strlen( $b ), strpos( $d, $e, strpos( $d, $b ) ) - strpos( $d, $b ) - strlen( $b ) );
        return $this;
    }

    /**
     * Extract a piece of a string from the beginning (left)
     */
    public function cutLeft( $length=0 )
    {
        if( is_int( $length ) && $length > 0 )
        {
            $this->str = trim( substr( $this->str, 0, $length ) );
        }
        return $this;
    }

    /**
     * Extract a piece of a string from the end (right)
     */
    public function cutRight( $length=0 )
    {
        if( is_int( $length ) && $length > 0 )
        {
            $this->str = trim( substr( $this->str, ( 0 - $length ), $length ) );
        }
        return $this;
    }

    /**
     * Looks for array keys in a string and replaces them with array values
     */
    public function mapReplace( $map=array() )
    {
        $list = array(
            'day'       => date( 'l' ),
            'month'     => date( 'F' ),
            'year'      => date( 'Y' ),
            'date'      => date( 'r' ),
            'datelong'  => date( 'l jS \of F Y' ),
            'dateshort' => date( 'm/d/Y' ),
            'time'      => date( 'h:i A' ),
            'timestamp' => time(),
            'ip'        => trim( @$_SERVER['REMOTE_ADDR'] ),
            'browser'   => trim( @$_SERVER['HTTP_USER_AGENT'] ),
            'charset'   => trim( @mb_internal_encoding() ),
            'timezone'  => trim( @date_default_timezone_get() ),
        );
        if( !empty( $map ) && is_array( $map ) )
        {
            $list = array_merge( $list, $map );
        }
        foreach( $list as $k => $v )
        {
            $this->str = str_replace( '%'.trim( $k ).'%', trim( $v ), $this->str );
        }
        return $this;
    }

    /**
     * Fills a space to the right of a string with a number of characters
     */
    public function fillSpace( $max=30, $char='.', $last=':' )
    {
        $this->htmlStrip();
        $fill   = '';
        $strlen = strlen( $this->str ) + strlen( $last );
        $max    = ( $strlen > $max ) ? ( $strlen - $max ) : ( $max - $strlen );
        while( $max-- ) $fill .= $char;
        $this->str = trim( $this->str ) . $fill . $last;
        return $this;
    }

	/**
	 * Call methods from the Sanitize class on the current string
	 */
	public function sanitize()
	{
        $instance = new Sanitize;

        foreach( func_get_args() as $method )
        {
            $callable = array( $instance, $method );

            if( is_callable( $callable ) )
            {
                $this->str = call_user_func( $callable, $this->str );
            }
        }
		return $this;
	}

    /**
     * Assig na fallback value to the current string, if empty
     */
    public function fallback( $value=null )
    {
        if( empty( $this->str ) )
        {
            $this->str = trim( $fallback );
        }
        return $this;
    }

}

