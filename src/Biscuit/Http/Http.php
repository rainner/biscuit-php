<?php
/**
 * HTTP client class for sending requests.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Http;

class Http {

    // request
    protected $type     = 'GET';
    protected $url      = '';
    protected $headers  = array();
    protected $params   = array();
    protected $boundary = '';
    protected $uagent   = '';
    protected $timeout  = 10;

    // response
    protected $info     = array();
    protected $response = null;
    protected $error    = '';

    /**
     * Constructor
     */
    public function __construct( $type='GET', $url='', $params=array() )
    {
        $this->setBoundary();
        $this->setType( $type );
        $this->setUrl( $url );
        $this->setUserAgent();

        foreach( $params as $key => $value )
        {
            $this->addParam( $key, $value );
        }
    }

    /**
     * Returns a new class instance setup to send a GET request
     */
    public static function get( $url='', $params=array() )
    {
        return new Http( 'GET', $url, $params );
    }

    /**
     * Returns a new class instance setup to send a POST request
     */
    public static function post( $url='', $params=array() )
    {
        return new Http( 'POST', $url, $params );
    }

    /**
     * Returns a new class instance setup to send a PUT request
     */
    public static function put( $url='', $params=array() )
    {
        return new Http( 'PUT', $url, $params );
    }

    /**
     * Returns a new class instance setup to send a PATCH request
     */
    public static function patch( $url='', $params=array() )
    {
        return new Http( 'PATCH', $url, $params );
    }

    /**
     * Returns a new class instance setup to send a DELETE request
     */
    public static function delete( $url='', $params=array() )
    {
        return new Http( 'DELETE', $url, $params );
    }

    /**
     * Flush previous response data
     */
    public function flushResponse()
    {
        $this->info     = array();
        $this->response = null;
        $this->error    = '';
        return $this;
    }

    /**
     * Sets the boundary string to use for form-encoded POST data
     */
    public function setBoundary( $boundary='' )
    {
        $default = '--------------------------'.microtime( true );
        $this->boundary = !empty( $boundary ) ? $boundary : $default;
        return $this;
    }

    /**
     * Set request scheme type
     */
    public function setType( $value='' )
    {
        if( !empty( $value ) )
        {
            $this->type = strtoupper( trim( $value ) );
        }
        return $this;
    }

    /**
     * Set the request endpoint URL
     */
    public function setUrl( $value='' )
    {
        if( !empty( $value ) )
        {
            $this->url = trim( $value );
        }
        return $this;
    }

    /**
     * Set the request timeout
     */
    public function setTimeout( $value=10 )
    {
        if( is_numeric( $value ) )
        {
            $this->timeout = intval( $value );
        }
        return $this;
    }

    /**
     * Set a user agant string, or use default
     */
    public function setUserAgent( $value='' )
    {
        $this->uagent = !empty( $value ) ? trim( $value ) : 'PHP/'.phpversion();
        return $this;
    }

    /**
     * Add a custom header line to be included with the request
     */
    public function addHeader( $value='' )
    {
        if( !empty( $value ) )
        {
            $this->headers[] = trim( $value );
        }
        return $this;
    }

    /**
     * Add a custom request argument/param to be sent with the request
     */
    public function addParam( $key='', $value='' )
    {
        if( !empty( $key ) )
        {
            $this->params[ $key ] = $value;
        }
        return $this;
    }

    /**
     * Get url encoded request params
     */
    public function getUrlParams( $prefix='' )
    {
        $output = '';

        if( !empty( $this->params ) )
        {
            $output = $prefix . @http_build_query( $this->params );
        }
        return $output;
    }

    /**
     * Get form-data encoded request params
     */
    public function getFormParams()
    {
        $output = '';

        foreach( $this->params as $key => $value )
        {
            $output .= "" .
            "--" . $this->boundary . "\r\n" .
            'Content-Disposition: form-data; name="'. $key .'"' . "\r\n\r\n" .
            trim( $value ) . "\r\n";
        }
        $output .= "--" . $this->boundary . "--\r\n";
        return $output;
    }

    /**
     * Get all data about the request
     */
    public function getRequestData()
    {
        return array(
            'type'    => $this->type,
            'url'     => $this->url,
            'uagent'  => $this->uagent,
            'timeout' => $this->timeout,
            'headers' => $this->headers,
            'params'  => $this->params,
        );
    }

    /**
     * Get the CURL response info from the last request
     */
    public function getResponseInfo()
    {
        return $this->info;
    }

    /**
     * Get the response data from the last request
     */
    public function getResponse()
    {
        if( $response = $this->_send() )
        {
            return trim( $response );
        }
        return false;
    }

    /**
     * Send request and parse XML response into an object
     */
    public function getXmlObject()
    {
        if( $response = $this->_send() )
        {
            return @simplexml_load_string( trim( $response ), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS );
        }
        return false;
    }

    /**
     * Send request and parse JSON response into an object
     */
    public function getJsonObject()
    {
        if( $response = $this->_send() )
        {
            return @json_decode( trim( $response ) );
        }
        return false;
    }

    /**
     * Send request and parse JSON response into an array
     */
    public function getJsonArray()
    {
        if( $response = $this->_send() )
        {
            return @json_decode( trim( $response ), true );
        }
        return false;
    }

    /**
     * Get the response info array from CURL request
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get the response error from the last request
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Sends an HTTP request using CURL
     */
    private function _http_curl()
    {
        $hostname     = parse_url( $this->url, PHP_URL_HOST );
        $safe_mode    = ini_get( 'safe_mode' );
        $open_basedir = ini_get( 'open_basedir' );
        $exec_time    = ini_get( 'max_execution_time' );

        // need a valid url
        if( empty( $hostname ) )
        {
            return $this->_error( 'Tried to send an HTTP request without first specifying a valid remote URL ('.$this->url.')' );
        }

        // add content type header
        $ctype   = ( $this->type === 'GET' ) ? 'application/x-www-form-urlencoded' : 'multipart/form-data; boundary='.$this->boundary;
        $content = ( $this->type === 'GET' ) ? $this->getUrlParams() : $this->getFormParams();
        $args    = ( $this->type === 'GET' ) ? $this->getUrlParams( '?' ) : '';
        $this->addHeader( 'Content-type: '.$ctype );

        // ini custom timeout
        set_time_limit( $this->timeout );

        // begin advanced request with curl
        $c = curl_init();
        curl_setopt( $c, CURLOPT_URL, $this->url . $args );
        curl_setopt( $c, CURLOPT_REFERER, $this->url );
        curl_setopt( $c, CURLOPT_USERAGENT, $this->uagent );
        curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt( $c, CURLOPT_CUSTOMREQUEST, $this->type );
        curl_setopt( $c, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $c, CURLOPT_FAILONERROR, 1 );
        curl_setopt( $c, CURLOPT_FORBID_REUSE, 1 );
        curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $c, CURLOPT_MAXREDIRS, 5 );

        // set allow redirect, if possible
        if( empty( $safe_mode ) && empty( $open_basedir ) )
        {
            curl_setopt( $c, CURLOPT_FOLLOWLOCATION, 1 );
        }

        // add custom headers
        if( !empty( $this->headers ) )
        {
            curl_setopt( $c, CURLOPT_HTTPHEADER, $this->headers );
        }

        // send the POST data unsing form-data
        if( !empty( $this->params ) && $this->type !== 'GET' )
        {
            curl_setopt( $c, CURLOPT_POST, 1 );
            curl_setopt( $c, CURLOPT_POSTFIELDS, $content );
        }

        // get response/errors and close
        $this->response = curl_exec( $c );
        $this->info     = curl_getinfo( $c );
        $error_number   = curl_errno( $c );
        $error_string   = curl_error( $c );
        curl_close( $c );

        // restore timeout
        set_time_limit( $exec_time );

        // check for error and return
        if( !empty( $error_number ) )
        {
            return $this->_error( 'HTTP request to ('.$this->url.') failed: ' . $error_string );
        }
        return $this->response;
    }

    /**
     * Sends an HTTP request using stream context
     */
    private function _http_stream()
    {
        $hostname  = parse_url( $this->url, PHP_URL_HOST );
        $exec_time = ini_get( 'max_execution_time' );

        // need a valid url
        if( empty( $hostname ) )
        {
            return $this->_error( 'Tried to send an HTTP request without first specifying a valid remote URL ('.$this->url.')' );
        }

        // add content type header
        $ctype   = ( $this->type === 'GET' ) ? 'application/x-www-form-urlencoded' : 'multipart/form-data; boundary='.$this->boundary;
        $content = ( $this->type === 'GET' ) ? $this->getUrlParams() : $this->getFormParams();
        $this->addHeader( 'Content-type: '.$ctype );

        // default context structure for making GET requests
        $http = array(
            'http' => array(
                'method'          => $this->type,
                'max_redirects'   => 5,
                'follow_location' => 1,
                'user_agent'      => $this->uagent,
                'timeout'         => $this->timeout,
                'header'          => $this->headers,
                'content'         => $content
            )
        );

        // ini custom timeout
        set_time_limit( $this->timeout );

        // new context and repsonse
        $context        = @stream_context_create( $http );
        $this->response = @file_get_contents( $this->url, false, $context );

        // restore timeout
        set_time_limit( $exec_time );

        // check for error and return
        if( $this->response === false )
        {
            return $this->_error( 'HTTP request to ('.$this->url.') failed using file_get_contents stream context.' );
        }
        return $this->response;
    }

    /**
     * Send the request and get a response, or false on error
     */
    private function _send()
    {
        $this->flushResponse();
        $output = null;

        if( function_exists( 'curl_init' ) )
        {
            $output = $this->_http_curl();
        }else{
            $output = $this->_http_stream();
        }
        $this->_log();
        return $output;
    }

    /**
     * Sets an error string and returns false
     */
    private function _error( $error='' )
    {
        $this->error = trim( $error );
        return false;
    }

}