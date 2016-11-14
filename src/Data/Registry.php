<?php
/**
 * Registry object for storing data.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Data;

use Closure;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Registry {

    // props
    protected $_data = [];  // current stored data
    protected $_each = [];  // cached data for iteration

    /**
     * Override local data array
     */
    public function useData( $data )
    {
        if( !empty( $data ) && is_array( $data ) )
        {
            $this->_data = $data;
        }
    }

    /**
     * Merge data with local data array
     */
    public function mergeData( $data )
    {
        if( !empty( $data ) && is_array( $data ) )
        {
            $this->_data = array_merge( $this->_data, $data );
        }
    }

    /**
     * Load array data from files in a path, or a single file
     */
    public function loadData( $path )
    {
        if( !empty( $path ) && is_string( $path ) )
        {
            $path = Sanitize::toPath( $path );

            if( is_dir( $path ) )
            {
                foreach( glob( $path."/*.php", GLOB_NOSORT ) as $file )
                {
                    $data = $this->_fileData( $file );
                    $this->mergeData( $data );
                }
            }
            else if( is_file( $path ) )
            {
                $data = $this->_fileData( $path );
                $this->mergeData( $data );
            }
        }
    }

    /**
     * Gets the data array as is
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Delete all local data
     */
    public function flushData()
    {
        $this->_data = [];
    }

    /**
     * Checks if a key has been set
     */
    public function hasKey( $key )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $data  = &$this->_data;
            $keys  = explode( ".", $key );
            $total = count( $keys );
            $count = 0;

            foreach( $keys as $step )
            {
                if( !empty( $step ) && isset( $data[ $step ] ) )
                {
                    $data = &$data[ $step ];
                    $count++;
                }
            }
            return ( $count === $total ) ? true : false;
        }
        return false;
    }

    /**
     * Checks if a key matches a value
     */
    public function hasKeyValue( $key, $value )
    {
        return ( $this->getKey( $key ) === $value );
    }

    /**
     * Checks the type of data for a key
     */
    public function isKeyType( $key, $type )
    {
        $value = $this->getKey( $key );
        return Validate::isType( $value, $type );
    }

    /**
     * Checks if a key is undefined, or empty
     */
    public function isKeyEmpty( $key )
    {
        $value = $this->getKey( $key );
        return empty( $value );
    }

    /**
     * Set a value for a dot-notated key string
     */
    public function setKey( $key, $value=null )
    {
       $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $data = &$this->_data;

            foreach( explode( ".", $key ) as $step )
            {
                if( !isset( $data[ $step ] ) || !is_array( $data[ $step ] ) )
                {
                    $data[ $step ] = [];
                }
                $data = &$data[ $step ];
            }
            $data = $value;
        }
    }

    /**
     * Get a value for a dot-notated key string
     */
    public function getKey( $key, $default=null, $special=false )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $data = $this->_data;

            foreach( explode( ".", $key ) as $step )
            {
                if( isset( $data[ $step ] ) && !is_null( $data[ $step ] ) )
                {
                    $data = $data[ $step ];
                    continue;
                }
                return $default;
            }
            return ( $special === true ) ? htmlspecialchars( $data ) : $data;
        }
        return $default;
    }

    /**
     * Add value to a key, if it is an array
     */
    public function addKey( $key, $value=null, $index=null )
    {
        $data = $this->getKey( $key );

        if( is_array( $data ) )
        {
            if( is_string( $index ) || is_numeric( $index ) )
            {
                if( isset( $data[ $index ] ) !== true )
                {
                    $data[ $index ] = $value;
                }
            }
            else{ $data[] = $value; }

            $this->setKey( $key, $data );
        }
    }

    /**
     * Delete an existing key
     */
    public function deleteKey( $key )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $list = explode( ".", $key );
            $last = array_pop( $list );
            $data = &$this->_data;

            foreach( $list as $step )
            {
                if( !isset( $data[ $step ] ) ) return;

                $data = &$data[ $step ];
            }
            if( isset( $data[ $last ] ) )
            {
                $data[ $last ] = null;
                unset( $data[ $last ] );
            }
        }
    }

    /**
     * Filter the value for a given key, store the new value and return it
     */
    public function filterKey( $key, $callback )
    {
        $value = $this->getKey( $key, null );

        if( $value !== null && $callback instanceof Closure )
        {
            $callback = $callback->bindTo( $this );
            $value    = call_user_func( $callback, $value );
            $this->setKey( $key, $value );
        }
        return $value;
    }

    /**
     * Format value for a local key by passing it through a custom callable (key, callable, arg1, ...)
     */
    public function formatKey()
    {
        $args   = func_get_args();
        $key    = count( $args ) ? array_shift( $args ) : ""; // extract first arg as local key
        $filter = count( $args ) ? array_shift( $args ) : ""; // extract second arg as filter callable
        $value  = $this->getKey( $key, "" );
        $base   = "\\Biscuit\\Utils\\";
        $output = "";

        if( !empty( $filter ) )
        {
            $params = [ $value ]; // use value as first param for filter callable
            foreach( $args as $arg ) $params[] = $arg;

            if( is_callable( $filter ) )
            {
                $output = call_user_func_array( $filter, $params );
            }
            else if( is_callable( $base.$filter ) )
            {
                $output = call_user_func_array( $base.$filter, $params );
            }
        }
        return $output;
    }

    /**
     * Used inside while() clause for looping array data for $key
     */
    public function eachKey( $key )
    {
        if( !empty( $key ) )
        {
            if( isset( $this->_each[ $key ] ) !== true )
            {
                $list = $this->getKey( $key, [] );
                $this->_each[ $key ] = is_array( $list ) ? array_values( $list ) : [];
            }
            if( $item = array_shift( $this->_each[ $key ] ) )
            {
                if( is_array( $item ) )
                {
                    $registry = new Registry();
                    $registry->useData( $item );
                    return $registry;
                }
                return $item;
            }
            unset( $this->_each[ $key ] );
        }
        return false;
    }

    /**
     * Try to load array data from a file
     */
    private function _fileData( $_file )
    {
        if( is_file( $_file ) && strpos( $_file, ".php" ) !== false )
        {
            $data = include_once( $_file );

            if( is_array( $data ) )
            {
                return $data;
            }
        }
        return [];
    }

}
