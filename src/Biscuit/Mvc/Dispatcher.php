<?php
/**
 * Handles dispatching routes to controllers.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Mvc;

use Biscuit\Boot\Events;
use Biscuit\Exception\DependencyException;

class Dispatcher extends Events {

    // one dispatch routine per request
    protected $_dispatched = false;

    // Container object for dependency injection
    protected $_container = null;

    // Route object to be dispatched
    protected $_route = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // register some handlers
        register_shutdown_function( array( $this, 'triggerEvent' ), 'exit' );
        spl_autoload_register( array( $this, '_loadClass' ), true );
    }

    /**
     * Pass new object to container
     */
    public function __set( $name='', $object=null )
    {
        return $this->_container->setObject( $name, $object );
    }

    /**
     * Returns an object from container
     */
    public function __get( $name='' )
    {
        return $this->_container->getObject( $name );
    }

    /**
     * Set local Container object
     */
    public function setContainer( Container $container )
    {
        $this->_container = $container;
    }

    /**
     * Set local Route object
     */
    public function setRoute( Route $route )
    {
        $this->_route = $route;
    }

    /**
     * Bootup area, dispatch events and controller actions
     */
    public function dispatch()
    {
        if( $this->_dispatched !== true )
        {
            if( ( $this->_route instanceof Route ) !== true )
            {
                throw new DependencyException( "No Route object specified before calling ".__FUNCTION__."." );
            }
            if( ( $this->_container instanceof Container ) !== true )
            {
                $this->_container = new Container();
            }

            // bootstrap selected route area
            $this->_loadFile( $this->_route->getAreaSetupFile() );

            // trigger init events
            $this->triggerEvent( 'init', $this->_route );
            $this->matchEvent( $this->_route->getRequestPath() );

            // try to dispatch the route
            $output = $this->_getOutput();
            $event  = !empty( $output ) ? 'output' : 'notfound';

            // trigger last events
            $this->triggerEvent( $event, $output );
            $this->triggerEvent( 'done' );
            $this->_dispatched = true;
        }
    }

    /**
     * Tries to autoload mvc controller and model classes
     */
    public function _loadClass( $class='' )
    {
        $parts    = explode( '/', trim( str_replace( '\\', '/', $class ), '/ ' ) );
        $filename = array_pop( $parts ).'.php';
        $subpath  = strtolower( implode( '/', $parts ) );
        $filepath = $this->_route->getBasePath( '/'.$subpath.'/'.$filename );

        if( is_file( $filepath ) )
        {
            include_once( $filepath );
        }
    }

    /**
     * Loads an external file exposing this class's scope and objects
     */
    private function _loadFile( $file='' )
    {
        if( is_file( $file ) )
        {
            $dispatcher = &$this;
            $container  = &$this->_container;
            $route      = &$this->_route;
            return include( $file );
        }
        return false;
    }

    /**
     * Returns a controller route output value.
     */
    public function _getOutput()
    {
        $namespace = $this->_route->getControllerNamespace();
        $actions   = $this->_route->getActions();
        $params    = $this->_route->getParams();
        $output    = null;

        if( class_exists( $namespace ) )
        {
            $instance = new $namespace();

            if( method_exists( $instance, 'setContainer' ) )
            {
                $instance->setContainer( $this->_container );
            }
            foreach( $actions as $action )
            {
                $callable = array( $instance, $action );

                if( is_callable( $callable ) )
                {
                    $output = call_user_func_array( $callable, $params );
                }
            }
        }
        return $output;
    }

}