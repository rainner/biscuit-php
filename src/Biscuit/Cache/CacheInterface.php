<?php
/**
 * Interface for classes that handle caching.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Cache;

interface CacheInterface {

    /**
     * Connect to a caching service
     */
    public function connect( $config=array() );

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( $config=array(), $callback=null );

    /**
     * Checks for an active connection object
     */
    public function connected();

    /**
     * Clear current connection object
     */
    public function disconnect();

    /**
     * Save/replace key data in cache
     */
    public function set( $key='', $data=null );

    /**
     * Fetch key data from cache
     */
    public function get( $key='', $default=null );

    /**
     * Set an error and return false, or get last error
     */
    public function error( $error=null );

}
