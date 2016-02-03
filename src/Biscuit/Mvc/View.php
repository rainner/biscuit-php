<?php
/**
 * Used to build and render HTML views.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Mvc;

use Biscuit\Http\Server;
use Biscuit\Data\Registry;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Validate;
use Exception;

class View extends Registry {

    // props
    protected $_paths    = array();
    protected $_base     = '';
    protected $_template = '';

	/**
     * Constructor
     */
	public function __construct()
	{
        // void
	}

    /**
     * Reset paths and stored data
     */
	public function reset()
    {
        $this->flush();
        $this->_paths    = array();
        $this->_base     = '';
        $this->_template = '';
    }

    /**
     * Get a unique ID
     */
    public function unique()
    {
        return uniqid();
    }

    /**
     * Checks if a file path points to an external file
     */
    public function isExternal( $file='' )
    {
        if( Validate::isExternal( $file ) )
        {
            return $file;
        }
        return false;
    }

    /**
     * Checks if a relative file path can be found on the server
     */
    public function isFile( $file='' )
    {
        $file = Sanitize::toPath( $file );

        if( is_file( $this->_base . $file ) )
        {
            return $file;
        }
        return false;
    }

    /**
     * Try to find a file by looking at the $_paths list
     */
    public function resolveFile( $file='' )
    {
        $file = Sanitize::toPath( $file );

        if( !empty( $file ) )
        {
            foreach( $this->_paths as $path )
            {
                $target = $path . $file;

                if( is_file( $target ) )
                {
                    return $target;
                }
            }
        }
        return false;
    }

    /**
     * Add a private path to look for template files from
     */
    public function addRenderPath( $path='' )
    {
        $path = Sanitize::toPath( $path );

        if( empty( $path ) || !is_dir( $path ) )
        {
            throw new Exception( 'The render path must be a valid path.' );
        }
        if( in_array( $path, $this->_paths, true ) !== true )
        {
            $this->_paths[] = $path;
        }
    }

    /**
     * Get list of private render paths
     */
    public function getRenderPaths()
    {
        return $this->_paths;
    }

    /**
     * Sets the base path where public resources are loaded from
     */
    public function setPlublicPath( $path='' )
    {
        $path = Sanitize::toPath( $path );

        if( empty( $path ) || is_dir( $path ) !== true )
        {
            throw new Exception( 'The public path must be a valid path.' );
        }
        $this->_base = $path;
    }

    /**
     * Get teh public the base path
     */
    public function getPlublicPath()
    {
        return $this->_base;
    }

    /**
     * Get the url where public resources are loaded from
     */
    public function getPublicUrl( $append='' )
    {
        $base = str_replace( Server::getScriptPath(), '', $this->_base );
        return Server::getBaseUrl( $base . $append );
    }

    /**
     * Get script base URL
     */
    public function getBaseUrl( $append='' )
    {
        return Server::getBaseUrl( $append );
    }

    /**
     * Get current request URL
     */
    public function getUrl()
    {
        return Server::getUrl();
    }

    /**
     * Resolve the public url for a relative file
     */
    public function resolveUrl( $file='' )
    {
        if( $external = $this->isExternal( $file ) )
        {
            return $external;
        }
        if( $internal = $this->isFile( $file ) )
        {
            return $this->getPublicUrl( $internal );
        }
        return false;
    }

    /**
     * Sets the main template file that will be rendered
     */
    public function setTemplate( $file='' )
    {
        $file = Sanitize::toPath( $file );

        if( !empty( $file ) )
        {
            $this->_template = $file;
        }
    }

    /**
     * Get the list of render paths
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * Check if the current template file can be found
     */
    public function templateExists()
    {
        return $this->resolveFile( $this->_template );
    }

    /**
     * Adds a view file path to the view data that can be used later in the main template
     */
    public function setView( $key='', $file='' )
    {
        $key  = Sanitize::toKey( $key );
        $file = Sanitize::toPath( $file );

        if( !empty( $key ) && !empty( $file ) )
        {
            $this->set( 'viewfiles.'.$key, $file );
        }
    }

    /**
     * Get a view file path by key and import it
     */
    public function getView( $key='', $indent=1 )
    {
        $key  = Sanitize::toKey( $key );
        $file = $this->get( 'viewfiles.'.$key, '' );
        return  $this->import( $file, null, $indent );
    }

    /**
     * Adds an entry to the list of breadcrumb links data
     */
    public function addCrumb( $name='', $link='', $title='', $params=array() )
    {
        $key   = Sanitize::toKey( $name );
        $name  = Sanitize::toName( $name );
        $link  = Sanitize::toPath( $link );
        $title = Sanitize::toTitle( $title );

        if( !empty( $key ) && !empty( $link ) )
        {
            $crumbs = $this->get( 'crumbs', array() );

            $crumbs[ $key ] = array_merge( array(
                'name'  => $name,
                'link'  => $link,
                'title' => $title,
            ), $params );

            $this->set( 'crumbs', $crumbs );
        }
    }

    /**
     * Get the list of breadcrumbs
     */
    public function getCrumbs()
    {
        return $this->get( 'crumbs', array() );
    }

    /**
     * Imports a template file and returns the parsed output string
     */
	public function import( $file='', $data=null, $indent=0 )
    {
        if( $f = $this->resolveFile( $file ) )
        {
            $output = $this->_importFile( $f, $data );

            if( !empty( $output ) )
            {
                if( !empty( $indent ) )
                {
                    $spaces = "\n" . str_repeat( "\t", $indent );
                    $output = implode( $spaces, explode( "\n", $output ) );
                }
                return $output . "\n";
            }
            return 'View file empty ('.$file.').';
        }
        return 'View file not found ('.$file.').';
    }

    /**
     * Render body output for selected view type
     */
	public function render()
    {
        @ob_end_clean();
        @ob_clean();
        $output = $this->import( $this->_template );
        $this->reset();
        return trim( $output );
    }

    /**
     * Closed environment for importing view files and passing stuff to it
     */
    private function _importFile( $f='', $data=null )
    {
        $view = &$this;
        ob_start();
        include( $f );
        $output = trim( ob_get_contents() );
        ob_end_clean();
        return $output;
    }

}

















