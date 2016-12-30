<?php
/**
 * For rendering output view html markup.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Data;

use Biscuit\Http\Server;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class View extends Registry {

    // props
    protected $_template = "";  // main view template file
    protected $_paths    = [];  // paths to try rendering from

    /**
     * Constructor
     */
    public function __construct()
    {
        $root   = Server::getScriptPath();
        $parent = dirname( $root );

        $this->addPath( $root );
        $this->addPath( $parent );
        $this->addPath( $parent."/source" );
    }

    /**
     * Set the main view template file to render
     */
    public function setTemplate( $file )
    {
        $this->_template = "";

        if( !empty( $file ) && is_string( $file ) )
        {
            $file = Sanitize::toPath( $file );

            if( is_file( $file ) )
            {
                $this->addPath( dirname( $file ) );
                $this->_template = $file;
            }
        }
    }

    /**
     * Get the main view template file path
     */
    public function getTemplate( $file )
    {
        return $this->_template;
    }

    /**
     * Add a path to try rendering from
     */
    public function addPath( $path )
    {
        if( !empty( $path ) && is_string( $path ) )
        {
            $path = Sanitize::toPath( $path );

            if( is_dir( $path ) && !in_array( $path, $this->_paths ) )
            {
                $this->_paths[] = $path;
            }
        }
    }

    /**
     * Get list of added paths starting with last added
     */
    public function getPaths()
    {
        return array_reverse( $this->_paths );
    }

    /**
     * Helper: Get base url
     */
    public function baseUrl( $append=null )
    {
        return Server::getBaseUrl( $append );
    }

    /**
     * Helper: Get current route url without params
     */
    public function routeUrl( $append=null )
    {
        return Server::getRouteUrl( $append );
    }

    /**
     * Helper: Get current php script url
     */
    public function scriptUrl( $append=null )
    {
        return Server::getScriptUrl( $append );
    }

    /**
     * Helper: Get public web url for a local file if it exists
     */
    public function fileUrl( $file="" )
    {
        return Server::getFileUrl( $file );
    }

    /**
     * Helper: Resolve a full url
     */
    public function resolveUrl( $value="" )
    {
        return Server::resolveUrl( $value );
    }

    /**
     * Helper: Get full page url with param
     */
    public function currentUrl()
    {
        return Server::getUrl();
    }

    /**
     * Helper: Extract value from something or use fallback value
     */
    public function value()
    {
        $value = call_user_func_array( "Biscuit\\Utils\\Utils::value", func_get_args() );
        return is_string( $value ) ? trim( $value ) : $value;
    }

    /**
     * Try to import a file making use of relative paths added locally
     */
    public function import( $file="", $indent=0, $data=[] )
    {
        $output = "";

        if( !empty( $file ) )
        {
            // look for local key
            $file = $this->getKey( $file, $file );

            // try to load file as is
            $output = $this->_load( $file );

            // try adding available paths to file
            if( empty( $output ) )
            {
                foreach( $this->getPaths() as $path )
                {
                    if( $output = $this->_load( $path."/".$file, $data ) ) break;
                }
            }
            // add indentation to output if needed
            if( !empty( $output ) && !empty( $indent ) )
            {
                $spaces = "\n" . str_repeat( "\t", $indent );
                $output = implode( $spaces, explode( "\n", $output ) );
            }
        }
        return $output;
    }

    /**
     * Render main template view file once
     */
    public function render( $indent=0 )
    {
        $output = "";

        if( !empty( $this->_template ) )
        {
            $output = $this->import( $this->_template, $indent );
            $this->_template = "";
        }
        return $output;
    }

    /**
     * Try to load a file and return it"s rendered output
     */
    private function _load( $file="", $data=[] )
    {
        $file   = Sanitize::toPath( $file );
        $output = null;

        if( !empty( $file ) && is_file( $file ) )
        {
            ob_start();
            include( $file );
            $output = ob_get_contents();
            ob_end_clean();
        }
        return $output;
    }


}