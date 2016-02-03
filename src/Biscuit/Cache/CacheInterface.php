<?php
/**
 * Interface for classes that handle caching.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
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
