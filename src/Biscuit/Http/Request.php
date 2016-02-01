<?php
/**
 * Handles and parses request data sent by the client.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Http;

use Biscuit\Data\Registry;
use Biscuit\Data\Json;
use Biscuit\Data\Xml;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;

class Request extends Registry {

    // local properties
    protected $_encoding = '';       // internal charset
    protected $_method   = '';       // request method
    protected $_ctype    = '';       // request content-type
    protected $_charset  = '';       // request charset
    protected $_boundary = '';       // request form-data boundary
    protected $_input    = '';       // request input body
    protected $_secure   = false;    // is https request
    protected $_ajax     = false;    // is ajax request
    protected $_soap     = false;    // is soap request

    /**
     * Constructor
     */
    public function __construct( $parseInput=true )
    {
        $this->resolveIsSecure();
        $this->resolveIsAjax();
        $this->resolveIsSoap();
        $this->resolveEncoding();
        $this->resolveMethod();
        $this->resolveContentType();

        if( $parseInput === true )
        {
            $this->parseInput();
        }
    }

    /**
     * Resolve check for SSL request
     */
    public function resolveIsSecure( $default=false )
    {
        $this->_secure = Server::isSecure();
    }

    /**
     * Resolve check for AJAX request
     */
    public function resolveIsAjax( $default=false )
    {
        $value = Utils::getValue( @$_SERVER['REQUEST_WITH'], '' );
        $value = Utils::getValue( @$_SERVER['HTTP_REQUEST_WITH'], $value );
        $value = Utils::getValue( @$_SERVER['HTTP_X_REQUEST_WITH'], $value );
        $value = strtolower( trim( $value ) );
        $this->_ajax = ( $value === 'xmlhttprequest' ) ? true : $default;
    }

    /**
     * Resolve check for SOAP request
     */
    public function resolveIsSoap( $default=false )
    {
        $value = Utils::getValue( @$_SERVER['SOAPACTION'], '' );
        $value = Utils::getValue( @$_SERVER['HTTP_SOAPACTION'], $value );
        $this->_soap = ( !empty( $value ) || strpos( $this->_ctype, 'soap' ) !== false ) ? true : $default;
    }

    /**
     * Resolve the encoding used to filter input data
     */
    public function resolveEncoding( $default='UTF-8' )
    {
        $value = Utils::getValue( @mb_internal_encoding(), $default );
        $this->_encoding = strtolower( trim( $value ) );
    }

    /**
     * Resolve request method
     */
    public function resolveMethod( $default='GET' )
    {
        $value = Utils::getValue( @$_SERVER['REQUEST_METHOD'], $default );
        $value = Utils::getValue( @$_SERVER['HTTP_X_HTTP_METHOD'], $value );
        $this->_method = strtolower( trim( $value ) );
    }

    /**
     * Resolve input content type string
     */
    public function resolveContentType( $default='text/plain' )
    {
        $value = Utils::getValue( @$_SERVER['CONTENT_TYPE'], $default );
        $value = Utils::getValue( @$_SERVER['HTTP_CONTENT_TYPE'], $value );
        $value = Utils::getValue( @$_SERVER['HTTP_X_CONTENT_TYPE'], $value );
        $parts = array();

        if( !empty( $value ) )
        {
            $value = 'ctype='. $value;
            $value = preg_replace( '/\;\s+/', '&', $value );
            parse_str( $value, $parts );
        }
        $this->_ctype    = Utils::getValue( @$parts['ctype'], '' );
        $this->_charset  = Utils::getValue( @$parts['charset'], '' );
        $this->_boundary = Utils::getValue( @$parts['boundary'], '' );
    }

    /**
     * Resolve request data
     */
    public function parseInput()
    {
        // grab raw input, if any
        $this->_input = trim( file_get_contents( 'php://input' ) );

        // parse input request data
        $parser = new Parser();
        $parser->setMethod( $this->_method );
        $parser->setContentType( $this->_ctype );
        $parser->setBoundary( $this->_boundary );
        $parser->setRawInput( $this->_input );
        $parser->skipFiles( 'php', 'phtml', 'htm', 'html', 'js', 'css', 'sh', 'py', 'cgi', 'exe', 'java', 'swf' );
        $parser->parse();

        // fix the files array
        $files = new Files();
        $files->parse();

        // filter some values
        $_GET     = $this->_filter( $_GET );
        $_POST    = $this->_filter( $_POST );
        $_REQUEST = $this->_filter( $_REQUEST );
        $_COOKIE  = $this->_filter( $_COOKIE );
    }

    /**
     * Checks what request method is being used
     */
    public function isMethod( $method='' )
    {
        return ( strtolower( trim( $method ) ) === $this->_method );
    }

    /**
     * Checks what request content-type is being used
     */
    public function isType( $type='' )
    {
        return ( strtolower( trim( $type ) ) === $this->_ctype );
    }

    /**
     * Checks if the connection is under HTTPS
     */
    public function isSecure()
    {
        return $this->_secure;
    }

    /**
     * Checks if an incoming request is AJAX
     */
    public function isAjax()
    {
        return $this->_ajax;
    }

    /**
     * Checks if an incoming request is SOAP
     */
    public function isSoap()
    {
        return $this->_soap;
    }

    /**
     * Get list of request headers, or single value if $name is provided
     */
    public function getHeaders( $name='', $default='' )
    {
        $headers = getallheaders();

        if( !empty( $name ) )
        {
            return Utils::getValue( @$headers[ $name ], $default );
        }
        return $headers;
    }

    /**
     * Get request method
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Get content-type
     */
    public function getContentType()
    {
        return $this->_ctype;
    }

    /**
     * Get charset
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Get boundary
     */
    public function getBoundary()
    {
        return $this->_boundary;
    }

    /**
     * Get raw input body data
     */
    public function getInput()
    {
        return $this->_input;
    }

    /**
     * Get an object from parsed XML string input data
     */
    public function getXml()
    {
        return Xml::parse( $this->_input );
    }

    /**
     * Get an array from parsed JSON string input data
     */
    public function getJson()
    {
        return Json::decode( $this->_input );
    }

    /**
     * Get GET data array, or sinle value for $name
     */
    public function query( $name='', $default='', $filters=null )
    {
        $this->useData( $_GET );
        $value = $this->get( $name, $default );
        return $this->_sanitize( $value, $filters );
    }

    /**
     * Get POST data array, or sinle value for $name
     */
    public function post( $name='', $default='', $filters=null )
    {
        $this->useData( $_POST );
        $value = $this->get( $name, $default );
        return $this->_sanitize( $value, $filters );
    }

    /**
     * Get REQUEST data array, or sinle value for $name
     */
    public function request( $name='', $default='', $filters=null )
    {
        $this->useData( $_REQUEST );
        $value = $this->get( $name, $default );
        return $this->_sanitize( $value, $filters );
    }

    /**
     * Get COOKIE data array, or sinle value for $name
     */
    public function cookie( $name='', $default='', $filters=null )
    {
        $this->useData( $_COOKIE );
        $value = $this->get( $name, $default );
        return $this->_sanitize( $value, $filters );
    }

    /**
     * Get FILES data array, or sinle value for $name
     */
    public function files( $name='', $default=array() )
    {
        $this->useData( $_FILES );
        return $this->get( $name, $default );
    }

    /**
     * Sanitizes a value for passed filters
     */
    private function _sanitize( $value=null, $filters=null )
    {
        if( !empty( $filters ) && ( is_array( $filters ) || is_string( $filters ) ) )
        {
            $filters  = is_array( $filters ) ? $filters : array( $filters );
            $instance = new Sanitize;

            foreach( $filters as $method )
            {
                $callable = array( $instance, $method );

                if( is_callable( $callable ) )
                {
                    $value = call_user_func( $callable, $value );
                }
            }
        }
        return $value;
    }

    /**
     * Recursive filter for input data arrays
     */
    private function _filter( $value=null )
    {
        if( is_numeric( $value ) )
        {
            return $value + 0;
        }
        if( is_string( $value ) )
        {
            $value = trim( $value );

            if( !empty( $this->_encoding ) )
            {
                $value = mb_convert_encoding( $value, $this->_encoding, $this->_encoding );
            }
            if( get_magic_quotes_gpc() )
            {
                $value = stripslashes( $value );
            }
            return Sanitize::toType( $value );
        }
        if( is_array( $value ) )
        {
            foreach( $value as $k => $v )
            {
                $value[ $k ] = $this->_filter( $v );
            }
        }
        return $value;
    }

}


