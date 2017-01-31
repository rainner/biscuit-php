<?php
/**
 * App router for routing a request to a controller.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Closure;
use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Router {

    // props
    protected $_request    = null;
    protected $_response   = null;
    protected $_method     = "GET";
    protected $_route      = "";
    protected $_pubpath    = "";
    protected $_csspath    = "";
    protected $_jspath     = "";
    protected $_basepath   = "";
    protected $_cnfpath    = "";
    protected $_ctrpath    = "";
    protected $_tplpath    = "";
    protected $_area       = "site";
    protected $_controller = "home";
    protected $_action     = "index";
    protected $_params     = [];
    protected $_data       = [];
    protected $_objects    = [];
    protected $_callbacks  = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // assumed initial paths
        $public = Server::getScriptPath();
        $base   = dirname( $public )."/areas";

        $this->_request  = new Request();
        $this->_response = new Response();
        $this->_method   = Connection::getMethod();
        $this->_route    = Connection::getPath();

        $this->setBasePath( $base );
        $this->setPublicPath( $public );
    }

    /**
     * Returns an injected object instance if available
     */
    public function __get( $key )
    {
        $key = Sanitize::toKey( $key );

        if( array_key_exists( $key, $this->_objects )  )
        {
            return $this->_objects[ $key ];
        }
        throw new InvalidArgumentException( trim( "
            Object with key of (".$key.") not found, in ".__CLASS__.".
        " ) );
    }

    /**
     * Resolve an object instance to be injected into route controllers
     */
    public function inject( $key, $object, $scope=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && $object )
        {
            if( is_string( $object ) )
            {
                if( class_exists( $object ) )
                {
                    $object = new $object();
                }
                else if( is_callable( $object ) )
                {
                    $object = call_user_func( $object );
                }
            }
            else if( $object instanceof Closure )
            {
                $scope  = is_object( $scope ) ? $scope : $this;
                $object = call_user_func( $object->bindTo( $scope ) );
            }
            if( is_object( $object ) )
            {
                $this->_objects[ $key ] = $object;
            }
        }
    }

    /**
     * Add persistent data to be passed to all HTML template responses for all routes
     */
    public function persist( $key, $value=null )
    {
        if( !empty( $key ) )
        {
            if( is_string( $key ) )
            {
                $this->_data[ Sanitize::toKey( $key ) ] = $value;
            }
            else if( is_array( $key ) )
            {
                $this->_data = array_merge( $this->_data, $key );
            }
        }
    }

    /**
     * Used by loaded controller file to register request actions
     */
    public function action( $method, $action, Closure $closure )
    {
        $method = ( $method === "*" ) ? "ANY" : Sanitize::toUpperCase( $method );
        $action = ( $action === "*" ) ? "any" : Sanitize::toParam( $action );

        if( !empty( $method ) && !empty( $action ) && $closure )
        {
            $this->_callbacks[ $method ][ $action ] = $closure;
        }
    }

    /**
     * Used to trigger registered controller actions
     */
    public function trigger( $method, $action, $params=null )
    {
        $method = ( $method === "*" ) ? "ANY" : Sanitize::toUpperCase( $method );
        $action = ( $action === "*" ) ? "any" : Sanitize::toParam( $action );

        if( !empty( $this->_callbacks[ $method ][ $action ] ) )
        {
            $closure = $this->_callbacks[ $method ][ $action ];
            $closure = $closure->bindTo( $this );

            if( is_array( $params ) )
            {
                return call_user_func_array( $closure, $params );
            }
            return call_user_func( $closure, $params );
        }
        return false;
    }

    /**
     * Dispatch current route to a controller
     */
    public function resolve( $fallback=null )
    {
        $this->_parseRoute();
        $this->_includeFile( $this->_cnfpath."/setup.php" );
        $this->_includeFile( $this->_ctrpath."/".$this->_controller.".php" );

        if( $output = $this->trigger( "*", "*", $this->_params ) )
        {
            return $output;
        }
        if( $output = $this->trigger( "*", $this->_action, $this->_params ) )
        {
            return $output;
        }
        if( $output = $this->trigger( $this->_method, "*", $this->_params ) )
        {
            return $output;
        }
        if( $output = $this->trigger( $this->_method, $this->_action, $this->_params ) )
        {
            return $output;
        }
        if( $fallback instanceof Closure )
        {
            $fallback = $fallback->bindTo( $this );
            return call_user_func( $fallback );
        }
        $this->_response->sendText( 404,
            "The requested route could not be resolved: (".$this->_route.")."
        );
    }

    /**
     * Set public base path
     */
    public function setPublicPath( $path )
    {
        $this->_pubpath = Sanitize::toPath( $path );
    }

    /**
     * Set areas base path
     */
    public function setBasePath( $path )
    {
        $this->_basepath = Sanitize::toPath( $path );
    }

    /**
     * Set the current path to be routed
     */
    public function setRoute( $route="/" )
    {
        if( !empty( $route ) && is_string( $route ) && $route !== $this->_route )
        {
            $this->_route = ( $route === "/" ) ? $route : Sanitize::toPath( $route );
            $this->_parseRoute();
        }
    }

    /**
     * Get route
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * Check route
     */
    public function isRoute( $route )
    {
        return ( $this->_route === $route ) ? true : false;
    }

    /**
     * Get request method
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Check request method
     */
    public function isMethod( $method )
    {
        return ( $this->_method === strtoupper( trim( $method ) ) ) ? true : false;
    }

    /**
     * Get area name
     */
    public function getArea()
    {
        return $this->_area;
    }

    /**
     * Check area
     */
    public function isArea( $area )
    {
        return ( $this->_area === $area ) ? true : false;
    }

    /**
     * Check area folder exists
     */
    public function areaExists( $area )
    {
        $folder = $this->_basepath."/".$area;
        return ( !empty( $area ) && is_dir( $folder ) ) ? true : false;
    }

    /**
     * Get controller name
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Check controller
     */
    public function isController( $controller )
    {
        return ( $this->_controller === $controller ) ? true : false;
    }

    /**
     * Check controller file exists
     */
    public function controllerExists( $controller )
    {
        $file = $this->_basepath."/".$this->_area."/controllers/".$controller.".php";
        return ( !empty( $controller ) && is_file( $file ) ) ? true : false;
    }

    /**
     * Get action name
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Check action
     */
    public function isAction( $action )
    {
        return ( $this->_action === $action ) ? true : false;
    }

    /**
     * Helper: get request data by key
     */
    public function request( $key="", $default=null )
    {
        return $this->_request->request( $key, $default );
    }

    /**
     * Helper: get request arg value by key
     */
    public function args( $key="", $default=null )
    {
        return $this->_request->args( $key, $default );
    }

    /**
     * Helper: get request param value by key
     */
    public function params( $key="", $default=null )
    {
        return $this->_request->params( $key, $default );
    }

    /**
     * Helper: get request cookie value by key
     */
    public function cookies( $key="", $default=null )
    {
        return $this->_request->cookies( $key, $default );
    }

    /**
     * Helper: get request file value by key
     */
    public function files( $key="", $default=null )
    {
        return $this->_request->files( $key, $default );
    }

    /**
     * Helper: get request header value by name
     */
    public function headers( $name="", $default="" )
    {
        return $this->_request->headers( $name, $default );
    }

    /**
     * Helper: run custom callback to configure local response object
     */
    public function withResponse( $callback )
    {
        if( $callback instanceof Closure )
        {
            $callback = $callback->bindTo( $this );
            call_user_func( $callback, $this->_response );
        }
    }

    /**
     * Helper: set a custom response header
     */
    public function sendHeader( $name, $value="" )
    {
        $this->_response->setHeader( $name, $value );
    }

    /**
     * Helper: send PlainText response
     */
    public function sendText( $status, $body=null )
    {
        if( is_array( $body ) || is_object( $body ) )
        {
            $body = print_r( $body, true );
        }
        $this->_response->sendText( $status, $body );
    }

    /**
     * Helper: send HTML response
     */
    public function sendHtml( $status, $body=null )
    {
        if( is_array( $body ) || is_object( $body ) )
        {
            $body = print_r( $body, true );
        }
        $this->_response->sendHtml( $status, $body );
    }

    /**
     * Helper: send rendered template HTML response
     */
    public function sendTemplate( $status, $data=[], $file="" )
    {
        $file = basename( $file, ".php" );
        $file = !empty( $file ) ? $file : "main";
        $file = Sanitize::toPath( $this->_tplpath."/".$file.".php" );

        if( is_file( $file ) )
        {
            if( is_array( $data ) )
            {
                $this->_data = array_merge( $this->_data, $data );
            }
            $this->_data["controller"] = $this->_getControllerInfo();
            $this->_data["menulist"]   = $this->_loadMenuData( $this->_cnfpath."/menu.php" );
            $this->_response->sendTemplate( $status, $file, $this->_data );
        }
        $this->_response->sendHtml( $status, "Template file not found (".$file.")" );
    }

    /**
     * Helper: send rendered HTML view for current route
     */
    public function sendView( $status, $data=[], $file="" )
    {
        $file = basename( $file, ".php" );
        $file = !empty( $file ) ? $file : $this->_action;
        $file = Sanitize::toPath( $this->_tplpath."/".$this->_controller."/".$file.".php" );

        if( is_file( $file ) )
        {
            if( is_array( $data ) )
            {
                $this->_data = array_merge( $this->_data, $data );
            }
            $this->_response->sendTemplate( $status, $file, $this->_data );
        }
        $this->_response->sendHtml( $status, "View file not found (".$file.")" );
    }

    /**
     * Helper: send JSON response
     */
    public function sendJson( $status, $data=[] )
    {
        $this->_response->sendJson( $status, $data );
    }

    /**
     * Helper: send File preview response
     */
    public function sendFile( $status, $mime, $file )
    {
        $this->_response->sendFile( $status, $mime, $file );
    }

    /**
     * Helper: send File download response
     */
    public function sendDownload( $status, $file )
    {
        $this->_response->sendDownload( $status, $file );
    }

    /**
     * Helper: send default response based on request method
     */
    public function sendDefault( $status, $response=null )
    {
        $this->_response->sendDefault( $status, $response );
    }

    /**
     * Helper: send redirect response
     */
    public function sendRedirect( $location )
    {
        $this->_response->redirect( $location );
    }

    /**
     * Include a file in $this scope
     */
    private function _includeFile( $file )
    {
        if( $file = realpath( $file ) )
        {
            include_once( $file );
            return true;
        }
        return false;
    }

    /**
     * Resolve current route details and paths for route
     */
    private function _parseRoute()
    {
        $route = trim( $this->_route, "/" );
        $route = preg_replace( "/[^\w\-\:\/]+/", "_", $route );
        $route = preg_replace( "/[\/]+/", "/", $route );
        $route = preg_replace( "/[\-]+/", "-", $route );
        $route = preg_replace( "/[\_]+/", "_", $route );
        $route = trim( $route, "-_ " );

        $this->_area       = "site";
        $this->_controller = "home";
        $this->_action     = "index";
        $this->_params     = explode( "/", $route );

        if( !empty( $this->_params[0] ) && $this->areaExists( $this->_params[0] ) )
        {
            $this->_area = array_shift( $this->_params );
        }
        if( !empty( $this->_params[0] ) && $this->controllerExists( $this->_params[0] ) )
        {
            $this->_controller = array_shift( $this->_params );
        }
        if( !empty( $this->_params[0] ) )
        {
            $this->_action = array_shift( $this->_params );
        }
        $this->_cnfpath = $this->_basepath."/".$this->_area."/configs";
        $this->_ctrpath = $this->_basepath."/".$this->_area."/controllers";
        $this->_tplpath = $this->_basepath."/".$this->_area."/templates";
        $this->_csspath = $this->_pubpath."/css";
        $this->_jspath  = $this->_pubpath."/js";
    }

    /**
     * Get requested controller details if it exists
     */
    private function _getControllerInfo()
    {
        $file    = $this->_ctrpath."/".$this->_controller.".php";
        $view    = $this->_tplpath."/".$this->_controller."/".$this->_action.".php";
        $styles  = $this->_csspath."/".$this->_area.".".$this->_controller.".css";
        $scripts = $this->_jspath."/".$this->_area.".".$this->_controller.".js";
        $output  = [];

        if( is_file( $file ) )
        {
            $output["base"]    = $this->_basepath;
            $output["area"]    = $this->_area;
            $output["name"]    = $this->_controller;
            $output["action"]  = $this->_action;
            $output["file"]    = $file;
            $output["view"]    = $view;
            $output["styles"]  = is_file( $styles )  ? Server::getFileUrl( $styles )  : "";
            $output["scripts"] = is_file( $scripts ) ? Server::getFileUrl( $scripts ) : "";
            $output["views"]   = [];

            foreach( glob( $this->_tplpath."/".$this->_controller."/*.php" ) as $v )
            {
                $output["views"][ basename( $v, ".php" ) ] = $v;
            }
        }
        return $output;
    }

    /**
     * Load and filter list of menu items data from a file
     */
    private function _loadMenuData( $file )
    {
        $file   = Sanitize::toPath( $file );
        $menu   = is_file( $file ) ? include_once( $file ) : [];
        $output = [];
        $count  = 1;

        if( is_array( $menu ) )
        {
            foreach( $menu as $idx => $item )
            {
                $active = "";
                $url    = Utils::value( @$item["url"], Server::getBaseUrl() );

                if( empty( $item["url"] ) ) // no url given, resolve one...
                {
                    if( !empty( $item["route"] ) ) // full route given, check it...
                    {
                        if( preg_match( "/^(\/".$this->_area.")?(\/".$this->_controller.")/", $item["route"] ) === 1 )
                        {
                            $active = "active"; // route matched current location
                        }
                        $url = Server::getBaseUrl( $item["route"] );
                    }
                    else if( !empty( $item["controller"] ) ) // controller name given, check it...
                    {
                        if( $this->_controller === $item["controller"] )
                        {
                            $active = "active"; // controller matched current controller
                        }
                        $area  = ( $this->_area !== "site" ) ? $this->_area : "";
                        $route = Utils::buildPath( $area, $item["controller"], @$item["action"] );
                        $url   = Server::getBaseUrl( $route );
                    }
                }
                $item["active"] = $active;
                $item["url"] = $url;
                $output[] = $item;
                $count++;
            }
        }
        return $output;
    }

}
