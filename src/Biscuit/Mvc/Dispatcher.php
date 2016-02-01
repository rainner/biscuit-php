<?php
/**
 * Handles dispatching routes to controllers.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Mvc;

use Biscuit\Boot\Events;
use Biscuit\Http\Server;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;
use Exception;
use Closure;

class Dispatcher extends Events {

    // one dispatch routine per request
    protected $_dispatched = false;

    // server docroot path
    protected $_path_root = '';

    // request handling script path
    protected $_path_script = '';

    // http request path
    protected $_path_request = '/';

    // base app areas path
    protected $_path_base = '';

    // default app area name
    protected $_default_area = 'site';

    // default controller name
    protected $_default_controller = 'home';

    // default action name
    protected $_default_action = 'index';

    // request method scheme
    protected $_method = '';

    // requested base area to serve from
    protected $_area = '';

    // requested controller name from URL path
    protected $_controller = '';

    // list of controller actions to be called
    protected $_actions = array();

    // list of action params to be passed
    protected $_params = array();

    // dependency injection container for controllers
    protected $_container = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        // register local handlers
        register_shutdown_function( array( $this, 'triggerEvent' ), 'exit' );
        spl_autoload_register( array( $this, 'mvcAutoloader' ), true );

        // resolve some local paths
        $this->_path_root   = Server::getDocRoot();
        $this->_path_script = Server::getScriptPath();
        $this->_path_base   = $this->_path_script .'/areas';

        // resolve request method verb
        $method = Utils::getValue( @$_SERVER['REQUEST_METHOD'], 'GET' );
        $method = Utils::getValue( @$_SERVER['HTTP_X_HTTP_METHOD'], $method );
        $this->_method = strtolower( trim( $method ) );

        // new empty container
        $this->_container = new Container();
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
     * Set the dependency injection container object
     */
    public function setContainer( Container $container )
    {
        $this->_container = $container;
    }

    /**
     * Sets the current request path to be parsed
     */
    public function setRequestPath( $path='/' )
    {
        $this->_path_request = Sanitize::toPath( $path );
    }

    /**
     * Returns the current request path
     */
    public function getRequestPath()
    {
        return $this->_path_request;
    }

    /**
     * Set the base path where areas will be loaded from
     */
    public function setBasePath( $path='' )
    {
        $this->_path_base = Sanitize::toPath( $path );
    }

    /**
     * Returns the current base path
     */
    public function getBasePath()
    {
        return $this->_path_base;
    }

    /**
     * Checks if an area exists by looking for a folder
     */
    public function areaExists( $area='' )
    {
        $area = Sanitize::toSlug( $area );
        $path = Sanitize::toPath( $this->_path_base .'/'. $area );
        return ( !empty( $area ) && is_dir( $path ) );
    }

    /**
     * Sets the default area name
     */
    public function setDefaultArea( $area='' )
    {
        $this->_default_area = Sanitize::toSlug( $area );
    }

    /**
     * Sets the area name
     */
    public function setArea( $area='' )
    {
        $this->_area = Sanitize::toSlug( $area );
    }

    /**
     * Returns selected area name
     */
    public function getArea()
    {
        return $this->_area;
    }

    /**
     * Returns the base path of the area being served
     */
    public function getAreaPath()
    {
        return Sanitize::toPath( $this->_path_base .'/'. $this->_area );
    }

    /**
     * Includes the setup file for the selected area with this scope
     */
    public function setupArea( $file='setup.php' )
    {
        $file = Sanitize::toPath( $this->getAreaPath().'/'.$file );
        if( is_file( $file ) ) include_once( $file );
    }

    /**
     * Sets the default controller name
     */
    public function setDefaultController( $controller='' )
    {
        $this->_default_controller = Sanitize::toSlug( $controller );
    }

    /**
     * Sets the route controller name
     */
    public function setController( $controller='' )
    {
        $this->_controller = Sanitize::toSlug( $controller );
    }

    /**
     * Returns the current controller name
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Returns the name to the controller class
     */
    public function getControllerName()
    {
        return Sanitize::toFullCamelCase( $this->_controller );
    }

    /**
     * Returns the controller class namespace
     */
    public function getControllerNamespace()
    {
        $area = Sanitize::toFullCamelCase( $this->_area );
        return '\\'.$area.'\\Controllers\\'.$this->getControllerName();
    }

    /**
     * Returns the path to the controller file
     */
    public function getControllerFile()
    {
        $path = $this->getAreaPath() .'/controllers';
        $name = $this->getControllerName() .'.php';
        return Sanitize::toPath( $path .'/'. $name );
    }

    /**
     * Returns a controller route output value.
     */
    public function getControllerOutput()
    {
        $namespace = $this->getControllerNamespace();
        $actions   = $this->getActions();
        $params    = $this->getParams();
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

    /**
     * Sets the route controller name
     */
    public function setDefaultAction( $action='' )
    {
        $this->_default_action = Sanitize::toSlug( $action );
    }

    /**
     * Add a custom controller action to the list
     */
    public function addAction( $action='' )
    {
        if( is_string( $action ) )
        {
            $action = Sanitize::toCamelCase( $action );

            if( !empty( $action ) )
            {
                $this->_actions[ $action ] = $action;
            }
        }
    }

    /**
     * Returns the list of controller actions
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     * Resets the list of actions
     */
    public function resetActions()
    {
        $this->_actions = array();
    }

    /**
     * Sets a list of parameters to be passed to controller action
     */
    public function setParams()
    {
        $this->_params = array_values( func_get_args() );
    }

    /**
     * Returns the list of actions params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Resets the list of action params
     */
    public function resetParams()
    {
        $this->_params = array();
    }

    /**
     * Convert a request path string into routing params
     */
    public function resolveRoute( $path='' )
    {
        $this->resetActions();
        $this->resetParams();

        $this->setArea( $this->_default_area );
        $this->setController( $this->_default_controller );
        $this->addAction( 'init-action' );

        $path  = Utils::getValue( $path, '/' );
        $path  = Sanitize::toPath( @parse_url( $path, PHP_URL_PATH ) );
        $path  = str_replace( Server::getBasePath(), '', $path );
        $route = explode( '/', trim( $path, '/' ) );

        if( !empty( $route[0] ) && $this->areaExists( $route[0] ) )
        {
            $this->setArea( array_shift( $route ) );
        }
        if( !empty( $route[0] ) )
        {
            $this->setController( array_shift( $route ) );
        }
        if( !empty( $route[0] ) )
        {
            $this->addAction( $this->_method.'-'.array_shift( $route ) );
        }
        if( !empty( $route ) )
        {
            $this->_params = array_values( $route );
        }
        if( count( $this->_actions ) === 1 )
        {
            $this->addAction( $this->_method.'-'.$this->_default_action );
        }
    }

    /**
     * Tries to autoload mvc controller and model classes
     */
    public function mvcAutoloader( $class='' )
    {
        $parts    = explode( '/', trim( str_replace( '\\', '/', $class ), '/ ' ) );
        $filename = array_pop( $parts ).'.php';
        $subpath  = strtolower( implode( '/', $parts ) );
        $filepath = $this->_path_base.'/'.$subpath.'/'.$filename;

        if( is_file( $filepath ) )
        {
            include_once( $filepath );
        }
    }

    /**
     * Bootup area, dispatch events and controller actions
     */
    public function dispatch()
    {
        if( $this->_dispatched !== true )
        {
            // parse route and bootup selected area
            $this->resolveRoute( $this->_path_request  );
            $this->setupArea();

            // trigger init events
            $this->triggerEvent( 'init' );
            $this->matchEvent( $this->_path_request );

            // try to dispatch the route
            $output = $this->getControllerOutput();
            $event  = !empty( $output ) ? 'output' : 'notfound';

            // trigger last events
            $this->triggerEvent( $event, $output );
            $this->triggerEvent( 'done' );
            $this->_dispatched = true;
        }
    }

}