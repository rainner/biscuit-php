<?php
/**
 * Handles resolving requests to controller routes.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Mvc;

use Biscuit\Http\Server;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;

class Route {

    // preppend request method verbs to action names
    protected $_use_verbs = true;

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

    /**
     * Constructor
     */
    public function __construct()
    {
        // resolve some local paths
        $this->_path_root   = Server::getDocRoot();
        $this->_path_script = Server::getScriptPath();
        $this->_path_base   = $this->_path_script .'/areas';

        // resolve request method verb
        $method = Utils::getValue( @$_SERVER['REQUEST_METHOD'], 'GET' );
        $method = Utils::getValue( @$_SERVER['HTTP_X_HTTP_METHOD'], $method );
        $this->_method = strtolower( trim( $method ) );
    }

    /**
     * Toggle use of request verbs in action names
     */
    public function useVebs( $toggle=true )
    {
        if( is_bool( $toggle ) )
        {
            $this->_use_verbs = $toggle;
        }
    }

    /**
     * Returns web host root path
     */
    public function getRootPath( $append='' )
    {
        return $this->_path_root . $append;
    }

    /**
     * Returns handling script path
     */
    public function getScriptPath( $append='' )
    {
        return $this->_path_script . $append;
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
    public function getRequestPath( $append='' )
    {
        return $this->_path_request . $append;
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
    public function getBasePath( $append='' )
    {
        return $this->_path_base . $append;
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
     * Returns the path to the area setup file
     */
    public function getAreaSetupFile( $filename='setup.php' )
    {
        return Sanitize::toPath( $this->getAreaPath().'/'.$filename );
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
     * Sets the route controller name
     */
    public function setDefaultAction( $action='' )
    {
        $this->_default_action = Sanitize::toSlug( $action );
    }

    /**
     * Add a custom controller action to the list
     */
    public function addAction( $action='', $useverb=true )
    {
        if( !empty( $action ) && is_string( $action ) )
        {
            if( $useverb === true && $this->_use_verbs === true )
            {
                $action = $this->_method.'-'.$action;
            }
            $action = Sanitize::toCamelCase( $action );
            $this->_actions[ $action ] = $action;
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
     * Returns info about current route
     */
    public function getRouteInfo()
    {
        return array(
            'rootPath' => $this->getRootPath(),
            'scriptPath' => $this->getScriptPath(),
            'requestPath' => $this->getRequestPath(),
            'basePath' => $this->getBasePath(),
            'area' => $this->getArea(),
            'areaPath' => $this->getAreaPath(),
            'areaSetupFile' => $this->getAreaSetupFile(),
            'controller' => $this->getController(),
            'controllerName' => $this->getControllerName(),
            'controllerNamespace' => $this->getControllerNamespace(),
            'controllerFile' => $this->getControllerFile(),
            'actions' => $this->getActions(),
            'params' => $this->getParams(),
        );
    }

    /**
     * Convert a request path string into routing params
     */
    public function parse()
    {
        $this->resetActions();
        $this->resetParams();

        $this->setArea( $this->_default_area );
        $this->setController( $this->_default_controller );
        $this->addAction( 'init-action', false );

        $path  = Utils::getValue( $this->_path_request, '/' );
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
            $this->addAction( array_shift( $route ) );
        }
        if( !empty( $route ) )
        {
            $this->_params = array_values( $route );
        }
        if( count( $this->_actions ) === 1 )
        {
            $this->addAction( $this->_default_action );
        }
    }

}