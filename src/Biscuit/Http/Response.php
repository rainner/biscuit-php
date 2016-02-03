<?php
/**
 * Handles sending back a response to the client.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Http;

use Biscuit\Http\Client;
use Biscuit\Http\Server;
use Biscuit\Storage\File;
use Biscuit\Data\Json;
use Biscuit\Data\Xml;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Validate;
use Biscuit\Util\Utils;

class Response {

    // response properties
	protected $headers  = array();   // list of headers
	protected $version  = '';        // http protocol version
	protected $status   = 200;       // response status code
	protected $ctype    = '';        // content-type
    protected $body     = '';        // response body
    protected $data     = array();   // reponse data

    /**
	 * Constructor
	 */
	public function __construct( $status=200, $ctype='text/html' )
	{
        $this->setVersion( null );
        $this->setStatusCode( $status );
        $this->setContentType( $ctype );
	}

    /**
     * Sets the HTTP protocol version
     */
    public function setVersion( $value='' )
    {
        $default = Utils::getValue( @$_SERVER['SERVER_PROTOCOL'], 'HTTP/1.1', true );
        $version = Utils::getValue( @$value, $default, true );
        $this->version = strtoupper( $version );
    }

    /**
     * Get http version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
	 * Sets the HTTP status code
	 */
	public function setStatusCode( $value=200 )
	{
        $value = Sanitize::toNumber( $value );

        if( !empty( $value ) && $value > 99 && $value < 999 )
        {
            $this->status = $value;
        }
	}

    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
	 * Sets the content type
	 */
	public function setContentType( $value='' )
	{
        $value = Sanitize::toText( $value );

        if( !empty( $value ) )
        {
            $this->ctype = strtolower( $value );
        }
	}

    /**
     * Get content type
     */
    public function getContentType()
    {
        return $this->ctype;
    }

    /**
	 * Sets a custom header string
	 */
	public function setHeader( $key='', $value='', $replace=true )
	{
        $key   = Sanitize::toSlug( $key );
        $value = Sanitize::toTitle( $value );

        if( !empty( $key ) )
        {
            $this->headers[ $key ] = array( $value, $replace );
        }
	}

    /**
	 * Sets and merges a list of headers from a given array
	 */
	public function setHeaders( $list=array(), $replace=true )
	{
        if( is_array( $list ) )
        {
            foreach( $list as $key => $value )
            {
                $this->setHeader( $key, $value, $replace );
            }
        }
	}

    /**
     * Removes a header key from the list
     */
    public function removeHeader( $key='' )
    {
        $key = Sanitize::toSlug( $key );

        if( !empty( $key ) && array_key_exists( $key, $this->headers ) )
        {
            unset( $this->headers[ $key ] );
        }
    }

    /**
     * Send response headers
     */
    public function sendHeaders()
    {
        if( headers_sent() === false )
        {
            $status  = Status::getHeader( $this->version, $this->status );
            $charset = strtolower( Utils::getValue( @mb_internal_encoding(), 'utf-8' ) );

            header( $status, true );
            header( 'Content-type: '.$this->ctype.'; charset='.$charset, true );

            foreach( $this->headers as $key => $data )
            {
                header( $key .': '. $data[0], $data[1] );
            }
            return true;
        }
        return false;
    }

    /**
     * Flushes the list of headers
     */
    public function flushHeaders()
    {
        $this->headers = array();
    }

    /**
     * Sets the response body data
     */
    public function setBody( $body='' )
    {
        $this->body = '';

        if( is_string( $body ) )
        {
            $this->body = trim( $body );
        }
    }

    /**
     * Get content body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Send the repsonse body string
     */
    public function sendBody()
    {
        echo trim( $this->body );
    }

    /**
     * Sets the response data to be formatted/encoded
     */
    public function setData( $data=array() )
    {
        $this->data = array();

        if( is_array( $data ) )
        {
            $this->data = $data;
        }
    }

    /**
     * Get content data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Delete stored body and data
     */
    public function flushContents()
    {
        $this->body = '';
        $this->data = array();
    }

    /**
     * Setup PlainText response
     */
    public function setText( $status=200, $data=null )
    {
        $this->setStatusCode( $status );
        $this->setContentType( 'text/plain' );
        $this->setBody( $data );
        $this->setData( $data );
        return true;
    }

    /**
     * Setup HTML response
     */
    public function setHtml( $status=200, $data=null )
    {
        $this->setStatusCode( $status );
        $this->setContentType( 'text/html' );
        $this->setBody( $data );
        $this->setData( $data );
        return true;
    }

    /**
     * Setup JSON response
     */
    public function setJson( $status=200, $data=null )
    {
        $this->setStatusCode( $status );
        $this->setContentType( 'application/json' );
        $this->setBody( $data );
        $this->setData( $data );
        return true;
    }

    /**
     * Setup XML response
     */
    public function setXml( $status=200, $data=null )
    {
        $this->setStatusCode( $status );
        $this->setContentType( 'application/xml' );
        $this->setBody( $data );
        $this->setData( $data );
        return $this;
    }

    /**
     * Setup RSS response
     */
    public function setRss( $status=200, $data=null )
    {
        $this->setStatusCode( $status );
        $this->setContentType( 'application/rss+xml' );
        $this->setBody( $data );
        $this->setData( $data );
        return $this;
    }

    /**
     * Sets caching headers for a timestamp
     */
    public function cacheExpire( $value='' )
    {
        $time = is_string( $value ) ? strtotime( $value ) : intval( $value );

        if( !empty( $time ) )
        {
            $now  = time();
            $date = gmdate( 'D, d M Y H:i:s', $time ).' GMT';
            $secs = ( $time > $now ) ? ( $time - $now ) : 0 - ( $now - $time );

            if( $time > $now )
            {
                // future time, set cache headers
                $this->setHeader( 'Expires', $date );
                $this->setHeader( 'Cache-Control', 'max-age='.$secs );
                $this->setHeader( 'Pragma', 'cache' );
            }
            else
            {
                // past time, prevent caching
                $this->setHeader( 'Expires', $date );
                $this->setHeader( 'Last-Modified', $date );
                $this->setHeader( 'Cache-Control', 'no-store, no-cache, must-revalidate' );
                $this->setHeader( 'Cache-Control', 'post-check=0, pre-check=0', false );
                $this->setHeader( 'Cache-Control', 'private', false );
                $this->setHeader( 'Pragma', 'public' );
            }
        }
    }

    /**
     * Sets the Strict-Transport-Security header for a timestamp
     */
    public function rememberSsl( $value='' )
    {
        $time = is_string( $value ) ? strtotime( $value ) : intval( $value );

        if( !empty( $time ) )
        {
            $now  = time();
            $secs = ( $time > $now ) ? ( $time - $now ) : 0 - ( $now - $time );

            $this->setHeader( 'Strict-Transport-Security', 'max-age='.$secs.'; includeSubDomains' );
        }
    }

    /**
     * Sets Content-Security-Policy headers for a value
     */
    public function contentSecurityPolicy( $value='' )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( 'Content-Security-Policy', $value );
            $this->setHeader( 'X-Content-Security-Policy', $value );
            $this->setHeader( 'X-Webkit-CSP', $value );
        }
    }

    /**
     * Sets the Content-Type-Options header for a value
     */
    public function contentTypeOptions( $value='' )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( 'X-Content-Type-Options', $value );
        }
    }

    /**
     * Sets the Frame-Options header for a value
     */
    public function frameOptions( $value='' )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( 'Frame-Options', $value );
            $this->setHeader( 'X-Frame-Options', $value );
        }
    }

    /**
     * Sets the XSS-Protection header for a value
     */
    public function xssProtection( $value='' )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( 'X-XSS-Protection', $value );
        }
    }

    /**
	 * Send redirect response
	 */
	public function redirect( $location='', $code=302, $delay=1 )
	{
        $current  = Server::getUrl();
        $location = Sanitize::toUrl( $location );
        $path1    = Sanitize::toPath( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
        $path2    = Sanitize::toPath( parse_url( $location, PHP_URL_PATH ) );
        $code     = is_numeric( $code ) ? intval( $code ) : 302;

        if( Validate::isExternal( $location ) || ( $path1 !== $path2 ) )
        {
            $this->flushHeaders();
            $this->flushContents();
            $this->setText( $code, '' );
            $this->setHeader( 'Location', $location, true );
            $this->setHeader( 'Connection', 'close', true );
            $this->send( $delay );
        }
        throw new Exception( 'Redirect aborted, from ('.$current.') to ('.$location.').' );
    }

    /**
     * Send file download response
     */
    public function download( $path='' )
    {
        $this->flushHeaders();
        $this->flushContents();

        if( !empty( $path ) )
        {
            if( Validate::isUrl( $path ) )
            {
                $this->redirect( $path );
            }
            if( is_file( $path ) !== true )
            {
                $this->setText( 404, '404: The requested file could not be found.' );
                $this->send();
            }
            $file = new File( $path );
            $this->cacheExpire( '-1 week' );
            $this->setStatusCode( 200 );
            $this->setContentType( $file->getMimeType() );
            $this->setHeader( 'Content-Description', 'File Transfer', true );
            $this->setHeader( 'Content-Disposition', 'attachment; filename='.$file->getSafeName().';', true );
            $this->setHeader( 'Content-Length', $file->getSize(), true );
            $this->setHeader( 'Content-Transfer-Encoding', 'binary', true );
            $this->sendHeaders();
            @readfile( $file->getPath() );
            exit;
        }
        throw new Exception( 'Tried to download a file without providing a valid file path.' );
    }

    /**
     * Send a custom response
     */
    public function send( $delay=0.0001 )
    {
        // clean buffer
        @ob_end_clean();
        @ob_clean();

        // parse local data for common content types
        if( is_array( $this->data ) )
        {
            switch( $this->ctype )
            {
                case 'application/json':    $this->_renderJson(); break;
                case 'application/rss+xml': $this->_renderRss();  break;
                case 'application/xml':     $this->_renderXml();  break;
            }
        }
        // send response
        $this->sendHeaders();
        $this->sendBody();
        $this->flushContents();

        // optional sleep time
        @time_sleep_until( microtime( true ) + floatval( $delay ) );
        @flush();
        exit;
    }

    /**
     * Renders a JSON view
     */
    private function _renderJson()
    {
        $this->body = Json::encode( $this->data );
    }

    /**
     * Renders a RSS view
     */
    private function _renderRss()
    {
        $xml = new Xml( 'rss' );
        $xml->import( $this->data );
        $this->body = $xml->getXML();
    }

    /**
     * Renders a XML view
     */
    private function _renderXml()
    {
        $xml = new Xml();
        $xml->import( $this->data );
        $this->body = $xml->getXML();
    }

}



