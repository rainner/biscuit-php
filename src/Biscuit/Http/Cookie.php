<?php
/**
 * Handles data storage in HTTP cookies.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Http;

use Biscuit\Crypt\Encryption;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;

class Cookie extends Encryption {

	// cookie properties
	protected $_name    = '';
	protected $_path    = '/';
	protected $_domain  = '';
	protected $_secure  = false;
	protected $_http    = true;

	/**
	 * Constructor
	 */
	public function __construct( $name='', $cryptkey='' )
	{
		parent::__construct( $cryptkey );
		$this->setName( $name );
		$this->setPath( '/' );
		$this->setDomain( Server::getHost() );
		$this->setSecure( Server::isSecure() );
	}

	/**
	 * Grab a new customized instance of Cookie
	 */
	public static function init()
	{
		$args   = func_get_args();
		$cookie = new Cookie();

		if( count( $args ) ) $cookie->setName( array_shift( $args ) );
		if( count( $args ) ) $cookie->setPath( array_shift( $args ) );
		if( count( $args ) ) $cookie->setDomain( array_shift( $args ) );
		if( count( $args ) ) $cookie->setSecure( array_shift( $args ) );
		if( count( $args ) ) $cookie->setHttpOnly( array_shift( $args ) );
		return $cookie;
	}

	/**
	 * Sets the cookie name
	 */
	public function setName( $value='' )
	{
		$value = Sanitize::toKey( $value );

		if( !empty( $value ) )
		{
			$this->_name = $value;
		}
	}

	/**
	 * Sets the cookie path
	 */
	public function setPath( $value='' )
	{
		$value = Sanitize::toPath( $value );

		if( !empty( $value ) )
		{
			$this->_path = $value;
		}
	}

	/**
	 * Sets the cookie domain
	 */
	public function setDomain( $value='' )
	{
		$value = Sanitize::toHostname( $value );

		if( !empty( $value ) )
		{
			$this->_domain = $value;
		}
	}

	/**
	 * Toggle cookie over ssl
	 */
	public function setSecure( $value=false )
	{
		$value = Sanitize::toBool( $value );

		if( is_bool( $value ) )
		{
			$this->_secure = $value;
		}
	}

	/**
	 * Toggle cookie HTTP only (no js access)
	 */
	public function setHttpOnly( $value=false )
	{
		$value = Sanitize::toBool( $value );

		if( is_bool( $value ) )
		{
			$this->_http = $value;
		}
	}

	/**
	 * Checks a cookie exists
	 */
	public function exists()
	{
		if( !empty( $this->_name ) && array_key_exists( $this->_name, $_COOKIE ) )
		{
			return true;
		}
		return false;
	}

	/**
	 * Saves a new cookie value
	 */
	public function save( $value=null, $expire=null, $encrypt=false )
	{
		if( !empty( $this->_name ) )
		{
			$value  = Sanitize::toString( $value );
			$expire = Sanitize::toTime( $expire );

			if( $encrypt === true )
			{
				$value = $this->encrypt( $value );
			}
			return setcookie(
				$this->_name,
				$value,
				$expire,
				$this->_path,
				$this->_domain,
				$this->_secure,
				$this->_http
			);
		}
		return false;
	}

	/**
	 * Get a cookie value, or default fallback
	 */
	public function get( $default='', $decrypt=false )
	{
		if( $this->exists() )
		{
			$value = trim( $_COOKIE[ $this->_name ] );

			if( $decrypt === true )
			{
				$value = $this->decrypt( $value );
			}
			return Sanitize::toType( $value );
		}
		return $default;
	}

	/**
	 * Delete a cookie, if it exists
	 */
	public function delete()
	{
		if( $this->exists() )
		{
			return setcookie(
				$this->_name,
				null,
				false,
				$this->_path,
				$this->_domain,
				$this->_secure,
				$this->_http
			);
		}
		return false;
	}

	/**
	 * Compare a given value with a cookie value
	 */
	public function compare( $value='', $decrypt=false )
	{
		$value  = trim( $value );
		$cookie = $this->get( '', $decrypt );
		return ( $value === $cookie ) ? true : false;
	}

	/**
	 * Renew the expire time for a cookie
	 */
	public function renew( $expire='' )
	{
		return $this->save( $this->get(), $expire );
	}

}

