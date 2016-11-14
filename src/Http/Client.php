<?php
/**
 * Basic HTTP client class with CURL.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Closure;

class Client {

    // request props
    protected $boundary = "";
    protected $method   = "";
    protected $baseurl  = "";
    protected $path     = "";
    protected $timeout  = 10;

    // request data
    protected $headers  = [];
    protected $query    = [];
    protected $params   = [];
    protected $body     = "";

    // response data
    protected $code     = 0;
    protected $codemap  = [];
    protected $response = "";
    protected $error    = "";
    protected $debug    = "";

    /**
     * Constructor
     */
    public function __construct( $baseurl="" )
    {
        $this->setBoundary();
        $this->setBaseUrl( $baseurl );
        $this->resetCodes();
    }

    /**
     * Sets the boundary string to use for form-encoded POST data
     */
    public function setBoundary( $boundary="" )
    {
        $default = "--------------------------".microtime( true );
        $this->boundary = !empty( $boundary ) ? $boundary : $default;
    }

    /**
     * Set request scheme type
     */
    public function setMethod( $method="" )
    {
        if( !empty( $method ) )
        {
            $this->method = strtoupper( trim( $method ) );
        }
    }

    /**
     * Get request scheme type
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set optional base request URL
     */
    public function setBaseUrl( $baseurl="" )
    {
        if( !empty( $baseurl ) )
        {
            $this->baseurl = trim( $baseurl );
        }
    }

    /**
     * Get optional base request URL
     */
    public function getBaseUrl()
    {
        return $this->baseurl;
    }

    /**
     * Set the request timeout
     */
    public function setTimeout( $timeout=10 )
    {
        $this->timeout = is_numeric( trim( $timeout ) ) ? intval( $timeout ) : 10;
    }

    /**
     * Get the request timeout
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Add a new header line
     */
    public function setHeader( $name, $value="" )
    {
        if( !empty( $name ) && is_string( $name ) )
        {
            $key   = strtolower( trim( $name ) );
            $name  = trim( $name );
            $value = trim( $value );
            $this->headers[ $key ] = $name .": ".$value;
        }
    }

    /**
     * Set headers array
     */
    public function setHeaders( $headers=[] )
    {
        $this->headers = [];

        if( is_array( $headers ) )
        {
            foreach( $headers as $name => $value )
            {
                $this->setHeader( $name, $value );
            }
        }
    }

    /**
     * Get headers array, or value for single key $name
     */
    public function getHeaders( $name )
    {
        $key = strtolower( trim( $name ) );

        if( !empty( $key ) && array_key_exists( $key, $this->headers ) )
        {
            return $this->headers[ $key ];
        }
        return $this->headers;
    }

    /**
     * Set custom request URL path
     */
    public function setPath( $path="" )
    {
        if( !empty( $path ) )
        {
            $this->path = trim( $path );
        }
    }

    /**
     * Get custom request URL path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set URL query string params
     */
    public function setQuery( $query=[] )
    {
        if( is_array( $query ) )
        {
            $this->query = $query;
        }
    }

    /**
     * Get URL query string params array
     */
    public function getQueryArray()
    {
        return $this->query;
    }

    /**
     * Get URL query string params as encoded string
     */
    public function getQueryString( $prefix="" )
    {
        return count( $this->query ) ? $prefix . http_build_query( $this->query ) : "";
    }

    /**
     * Set custom params array
     */
    public function setParams( $params=[] )
    {
        if( is_array( $params ) )
        {
            $this->params = $params;
        }
    }

    /**
     * Get custom params array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set custom raw body string
     */
    public function setBody( $body="" )
    {
        if( is_string( $body ) )
        {
            $this->body = trim( $body );
        }
    }

    /**
     * Get custom raw body string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get full request URL with quey args added
     */
    public function getRequestUrl()
    {
        $query = $this->getQueryString( "?" );

        if( preg_match( "/^([\w\-]+\:).*$/i", $this->path ) === 1 )
        {
            return $this->path . $query;
        }
        return $this->baseurl . $this->path . $query;
    }

    /**
     * Reset response code an local codemap
     */
    public function resetCodes()
    {
        $this->code = 200;
        $this->codemap = [ 200 => "Request successful" ];
    }

    /**
     * Maps a response code to a message
     */
    public function mapCode( $code, $message="" )
    {
        if( is_int( $code ) )
        {
            $this->codemap[ $code ] = trim( $message );
        }
    }

    /**
     * Return last response status code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Return message for last response status code
     */
    public function getMessage()
    {
        if( $this->code !== 200 )
        {
            if( array_key_exists( $this->code, $this->codemap ) )
            {
                return $this->codemap[ $this->code ];
            }
            if( !empty( $this->error ) )
            {
                return $this->error;
            }
            return "No response message for code (".$this->code.").";
        }
        return "";
    }

    /**
     * Return last response body
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Checks if there has been an error
     */
    public function hasError()
    {
        return !empty( $this->error ) ? true : false;
    }

    /**
     * Return last error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Return debug string data from last request
     */
    public function getDebugData()
    {
        return $this->debug;
    }

    /**
     * Setup new request action
     */
    public function action( $method="GET", $path="" )
    {
        $this->setMethod( $method );
        $this->setPath( $path );
        $this->setQuery( [] );
        $this->setParams( [] );
        $this->setBody( "" );
        return $this;
    }

    /**
     * Send URL-encoded query vars
     */
    public function sendQuery( $data=[] )
    {
        $this->setQuery( $data );
        $this->setHeader( "Content-Type", "text/plain" );
        return $this;
    }

    /**
     * Send URL-encoded data
     */
    public function sendEncoded( $data=[] )
    {
        $this->setParams( $data );
        $this->setBody( http_build_query( $data ) );
        $this->setHeader( "Content-Type", "application/x-www-form-urlencoded" );
        return $this;
    }

    /**
     * Send JSON encoded data
     */
    public function sendJson( $data=[] )
    {
        $this->setParams( $data );
        $this->setBody( json_encode( $data ) );
        $this->setHeader( "Content-Type", "application/json" );
        return $this;
    }

    /**
     * Send form encoded data
     */
    public function sendForm( $data=[] )
    {
        $this->setParams( $data );
        $this->setBody( $this->_formdata( $data ) );
        $this->setHeader( "Content-type", "multipart/form-data; boundary=".$this->boundary );
        return $this;
    }

    /**
     * Fetch a response and handle it with a custom callback
     */
    public function fetch( $handler )
    {
        if( $handler instanceof Closure )
        {
            $response = $this->_request();
            return call_user_func( $handler, $this->code, $this->response, $this->error );
        }
        return false;
    }

    /**
     * Fetch a response and handle it as JSON data
     */
    public function fetchJson( $default=[] )
    {
        if( $response = $this->_request() )
        {
            if( $data = json_decode( $response, true ) )
            {
                return $data;
            }
        }
        return $default;
    }

    /**
     * Send a requets and handle a response
     */
    private function _request()
    {
        $this->_error();

        $endpoint      = $this->getRequestUrl();
        $hostname      = parse_url( $endpoint, PHP_URL_HOST );
        $safe_mode     = ini_get( "safe_mode" );
        $open_basedir  = ini_get( "open_basedir" );
        $exec_time     = ini_get( "max_execution_time" );

        if( !empty( $hostname ) )
        {
            set_time_limit( $this->timeout );

            $c = curl_init();
            curl_setopt( $c, CURLOPT_URL, $endpoint );
            curl_setopt( $c, CURLOPT_REFERER, $endpoint );
            curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, $this->timeout );
            curl_setopt( $c, CURLOPT_USERAGENT, "PHP-CURL-Client" );
            curl_setopt( $c, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $c, CURLOPT_FAILONERROR, 1 );
            curl_setopt( $c, CURLOPT_FORBID_REUSE, 1 );
            curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $c, CURLOPT_MAXREDIRS, 5 );

            if( count( $this->headers ) )
            {
                curl_setopt( $c, CURLOPT_HTTPHEADER, $this->headers );
            }
            if( empty( $safe_mode ) && empty( $open_basedir ) )
            {
                curl_setopt( $c, CURLOPT_FOLLOWLOCATION, 1 );
            }
            if( !empty( $this->method ) )
            {
                curl_setopt( $c, CURLOPT_CUSTOMREQUEST, $this->method );

                if( $this->method !== "GET" )
                {
                    curl_setopt( $c, CURLOPT_POST, 1 );
                    curl_setopt( $c, CURLOPT_POSTFIELDS, $this->body );
                }
            }
            $this->response = trim( curl_exec( $c ) );
            $this->code     = intval( trim( curl_getinfo( $c, CURLINFO_HTTP_CODE ) ) );
            $this->error    = trim( curl_error( $c ) );
            $this->debug    = trim(
                "REQUEST_URL:     \n\n" . $endpoint,
                "REQUEST_HEADERS: \n\n" . print_r( $this->headers, true ),
                "REQUEST_QUERY:   \n\n" . print_r( $this->query, true ),
                "REQUEST_PARAMS:  \n\n" . print_r( $this->params, true ),
                "RESPONSE_CODE:   \n\n" . print_r( $this->code, true ),
                "RESPONSE_BODY:   \n\n" . print_r( $this->response, true ),
                "RESPONSE_ERROR:  \n\n" . $this->getMessage()
            );
            curl_close( $c );
            set_time_limit( $exec_time );

            return ( $this->hasError() !== true ) ? $this->response : false;
        }
        return $this->_error( "Tried to send an HTTP request without first specifying a valid remote URL." );
    }

    /**
     * Get form-data encoded string data
     */
    public function _formdata( $data=[] )
    {
        $output = "";
        foreach( $data as $key => $value )
        {
            $output .= "" .
            "--" . $this->boundary . "\r\n" .
            'Content-Disposition: form-data; name="'. $key .'"' . "\r\n\r\n" .
            trim( $value ) . "\r\n";
        }
        return $output . "--" . $this->boundary . "--\r\n";
    }

    /**
     * Sets an error string and returns false
     */
    private function _error( $error="" )
    {
        $this->error = trim( $error );
        return false;
    }

}