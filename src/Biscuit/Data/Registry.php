<?php
/**
 * Container for loading and working with array data.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Data;

use Biscuit\Util\Sanitize;
use Closure;

class Registry {

	// current stored data
	protected $_data = array();

    // cached data for iteration
    protected $_each = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
        // void
	}

    /**
	 * Sets and merges a given data array with the current local data array
	 */
	public function setData( $data=array() )
	{
        if( is_array( $data ) )
        {
		    $this->_data = array_merge( $this->_data, $data );
        }
	}

    /**
     * Uses existing data reference
     */
    public function useData( &$data=null )
    {
        if( is_array( $data ) )
        {
            $this->_data = $data;
        }
    }

    /**
     * Checks if there us data set
     */
    public function hasData()
    {
        if( !empty( $this->_data ) && is_array( $data ) )
        {
            return true;
        }
        return false;
    }

    /**
     * Gets the data array as is
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
	 * Loads data from files in a folder, or single file into the local data array
	 */
	public function loadData()
	{
        $args = func_get_args();
        $key  = ( count( $args ) > 1 ) ? array_shift( $args ) : '';
        $path = ( count( $args ) > 0 ) ? Sanitize::toPath( array_shift( $args ) ) : '';
        $data = array();

        if( is_dir( $path ) )
        {
            foreach( glob( $path.'/*.php', GLOB_NOSORT ) as $file )
            {
                $d = include( $file );

                if( !empty( $d ) )
                {
                    $n = basename( $file, '.php' );
                    $data[ $n ] = $d;
                }
            }
        }
        else if( is_file( $path ) && strpos( $path, '.php' ) !== false )
        {
            $d = include( $path );

            if( !empty( $d ) )
            {
                $n = basename( $path, '.php' );
                $data[ $n ] = $d;
            }
        }
        if( !empty( $key ) )
        {
            $this->set( $key, $data );
            return;
        }
        $this->setData( $data );
	}

    /**
     * Set a value for a dot-notated key string
	 */
	public function set( $key='', $value=null )
	{
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $data = &$this->_data;

            foreach( explode( '.', $key ) as $step )
            {
                if( !isset( $data[ $step ] ) || !is_array( $data[ $step ] ) )
                {
                    $data[ $step ] = array();
                }
                $data = &$data[ $step ];
            }
            $data = $value;
        }
	}

    /**
	 * Get a value for a dot-notated key string
	 */
	public function get( $key='', $default=null )
	{
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $data = $this->_data;

            foreach( explode( '.', $key ) as $step )
            {
                if( isset( $data[ $step ] ) && !is_null( $data[ $step ] ) )
                {
                    $data = $data[ $step ];
                    continue;
                }
                return $default;
            }
            return $data;
        }
        return $default;
	}

    /**
     * Gets an existing key, or return a default value.
     */
    public function delete( $key='' )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $list = explode( '.', $key );
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
     * Runs a function for each element of an array and returns the combined output string
     */
    public function loop( $key='', $callback=null )
    {
        $list   = $this->get( $key, array() );
        $output = '';

        if( !empty( $list ) && is_array( $list ) && $callback instanceof Closure )
        {
            $callback = $callback->bindTo( $this );

            foreach( $list as $key => $value )
            {
                $result = call_user_func( $callback, $key, $value );

                if( is_string( $result ) || is_numeric( $result ) )
                {
                    $output .= $result;
                }
            }
        }
        return $output;
    }

    /**
     * Used inside while() clause for looping array data for $key
     */
    public function each( $key='' )
    {
        if( !empty( $key ) )
        {
            if( isset( $this->_each[ $key ] ) !== true )
            {
                $list = $this->get( $key, array() );
                $this->_each[ $key ] = is_array( $list ) ? array_values( $list ) : array();
            }
            if( $item = array_shift( $this->_each[ $key ] ) )
            {
                if( is_array( $item ) )
                {
                    $registry = new Registry();
                    $registry->setData( $item );
                    return $registry;
                }
                return $item;
            }
            unset( $this->_each[ $key ] );
        }
        return false;
    }

    /**
     * Merges data for an existing key, or creates the key if not found
     */
    public function merge( $key='', $data=null )
    {
        if( !empty( $key ) && is_array( $data ) )
        {
            $current = $this->get( $key, null );

            if( is_null( $current ) )
            {
                $this->set( $key, $data );
            }
            else if( is_array( $current ) )
            {
                $this->set( $key, array_merge( $current, $data ) );
            }
        }
    }

    /**
     * Flushes any stored data
     */
    public function flush()
    {
        $this->_data = array();
    }

    /**
     * Checks if a session key is set
     */
    public function hasKey( $key='' )
    {
        return ( $this->get( $key ) !== null );
    }

    /**
     * Checks if a key matches a value
     */
    public function hasValue( $key='', $value=null )
    {
        return ( $this->get( $key, null ) === $value );
    }

    /**
     * Checks if a key is undefined, or empty
     */
    public function isEmpty( $key='' )
    {
        $value = $this->get( $key, null );
        return empty( $value );
    }

    /**
     * Checks if a key is numeric
     */
    public function isNumber( $key='' )
    {
        $value = $this->get( $key, null );
        return is_numeric( $value );
    }

    /**
     * Checks if a key is an integer
     */
    public function isInt( $key='' )
    {
        $value = $this->get( $key, null );
        return is_int( $value );
    }

    /**
     * Checks if a key is a float number
     */
    public function isFloat( $key='' )
    {
        $value = $this->get( $key, null );
        return is_float( $value );
    }

    /**
     * Checks if a key is a string
     */
    public function isString( $key='' )
    {
        $value = $this->get( $key, null );
        return is_string( $value );
    }

    /**
     * Checks if a key is an array
     */
    public function isArray( $key='' )
    {
        $value = $this->get( $key, null );
        return is_array( $value );
    }

    /**
     * Checks if a key is an object
     */
    public function isObject( $key='' )
    {
        $value = $this->get( $key, null );
        return is_object( $value );
    }

    /**
     * Checks if a key is closure
     */
    public function isClosure( $key='' )
    {
        $value = $this->get( $key, null );
        return ( $value instanceof Closure );
    }

}

