<?php
/**
 * Handles caching with Memcache.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Cache;

use Biscuit\Util\Utils;
use Biscuit\Util\Sanitize;
use Exception;

class Memcache implements CacheInterface {

    // connection handler object
    protected $obj = null;

    // connection options
    protected $options = array();

    // last error string
    protected $error = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Connect to a caching service
     */
    public function connect( $config=array() )
    {

    }

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( $config=array(), $callback=null )
    {
        if( $this->connect( $config ) !== true )
        {
            if( is_callable( $callback ) )
            {
                call_user_func( $callback, $this->error );
            }
        }
    }

    /**
     * Checks for an active connection object
     */
    public function connected()
    {

    }

    /**
     * Clear current connection object
     */
    public function disconnect()
    {

    }

    /**
     * Save/replace key data in cache
     */
    public function set( $key='', $data=null )
    {

    }

    /**
     * Fetch key data from cache
     */
    public function get( $key='', $default=null )
    {

    }

    /**
     * Set and error and return false, or get last error
     */
    public function error( $error=null )
    {
        if( is_string( $error ) )
        {
            $this->error = trim( $error );
            return false;
        }
        return $this->error;
    }

}