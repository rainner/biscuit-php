<?php
/**
 * Dependency injection container.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Mvc;

use Biscuit\Util\Sanitize;
use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use Exception;
use Closure;

class Container implements ArrayAccess {

    // list of resolved object instances
    protected $_objects = array();

    /**
     * Constructor
     */
    public function __construct( $factory=false )
    {
        if( $factory === true )
        {
            $this->factory();
        }
    }

    /**
     * Set using arrow symbol $class->objectName
     */
    public function __set( $name='', $object=null )
    {
        return $this->setObject( $name, $object );
    }

    /**
     * Get using arrow symbol $class->objectName
     */
    public function __get( $name='' )
    {
        return $this->getObject( $name );
    }

    /**
     * Check using arrow symbol $class->objectName
     */
    public function __isset( $name='' )
    {
        return $this->hasObject( $name );
    }

    /**
     * Remove using arrow symbol $class->objectName
     */
    public function __unset( $name='' )
    {
        return $this->removeObject( $name );
    }

    /**
     * Set using array access $class['objectName']
     */
    public function offsetSet( $name='', $object=null )
    {
        return $this->setObject( $name, $object );
    }

    /**
     * Get using array access $class['objectName']
     */
    public function offsetGet( $name='' )
    {
        return $this->getObject( $name );
    }

    /**
     * Check using array access $class['objectName']
     */
    public function offsetExists( $name='' )
    {
        return $this->hasObject( $name );
    }

    /**
     * Remove using array access $class['objectName']
     */
    public function offsetUnset( $name='' )
    {
        return $this->removeObject( $name );
    }

    /**
     * Try to resolve an object instance to be injected
     */
    public function setObject( $name='', $object=null )
    {
        $name = Sanitize::toAlnum( $name );
        $instance = null;

        if( empty( $name ) )   throw new InvalidArgumentException( 'Must specify a valid String name for object being injected.' );
        if( empty( $object ) ) throw new InvalidArgumentException( 'Must specify a valid Object/Closure/String to be injected.' );

        if( is_string( $object ) )
        {
            if( class_exists( $object ) )
            {
                $instance = new $object();
            }
            else if( is_callable( $object ) )
            {
                $instance = call_user_func( $object );
            }
        }
        else if( is_object( $object ) )
        {
            if( $object instanceof Closure )
            {
                $object   = $object->bindTo( $this );
                $instance = $object();
            }
            else{ $instance = $object; }
        }
        if( is_object( $instance ) !== true )
        {
            throw new BadMethodCallException( 'Injection arguments ('.$name.': '.gettype( $object ).') must resolve to a valid Object instance.' );
        }
        $this->_objects[ $name ] = $instance;
    }

    /**
     * Returns an injected object instance if available
     */
    public function getObject( $name='' )
    {
        $name = Sanitize::toAlnum( $name );

        if( !empty( $name ) && array_key_exists( $name, $this->_objects ) )
        {
            return $this->_objects[ $name ];
        }
        throw new InvalidArgumentException( 'No object instance with name ('.$name.') could be found.' );
    }

    /**
     * Checks if an injected object instance exists
     */
    public function hasObject( $name='' )
    {
        $name = Sanitize::toAlnum( $name );

        if( !empty( $name ) && array_key_exists( $name, $this->_objects ) )
        {
            return true;
        }
        return false;
    }

    /**
     * Removes an injected object instance if available
     */
    public function removeObject( $name='' )
    {
        $name = Sanitize::toAlnum( $name );

        if( !empty( $name ) && array_key_exists( $name, $this->_objects ) )
        {
            unset( $this->_objects[ $name ] );
        }
    }

    /**
     * Remove all objects
     */
    public function flushObjects()
    {
        foreach( $this->_objects as $name => $instance )
        {
            $instance = null;
            $this->_objects[ $name ] = null;
        }
        $this->_objects = array();
    }

    /**
     * Load list of objects
     */
    public function loadObjects( $list=array() )
    {
        foreach( $list as $name => $object )
        {
            $this->setObject( $name, $object );
        }
    }

    /**
     * Load factory objects
     */
    public function factory()
    {
        $this->flushObjects();

        // request
        // repsonse
        // database
        // session
        // storage
        // mailing
        // view
    }


}