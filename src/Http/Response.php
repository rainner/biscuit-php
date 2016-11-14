<?php
/**
 * Sends a final response to the client.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Biscuit\Data\View;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Response {

    // props
    protected $_status  = 200;
    protected $_headers = [];
    protected $_charset = "UTF-8";

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Set the response status code
     */
    public function setStatus( $status )
    {
        $status = Sanitize::toNumber( $status );
        $this->_status = ( $status >= 200 ) ? $status : 200;
        return $this;
    }

    /**
     * Get the response status code
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Set the response charset
     */
    public function setCharset( $charset )
    {
        $charset = Sanitize::toText( $charset );
        $this->_charset = Utils::value( $charset, $this->_charset, "UTF-8" );
        return $this;
    }

    /**
     * Get the response charset
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Set single header
     */
    public function setHeader( $name, $value="" )
    {
        $name = Sanitize::toLowerCase( $name );

        if( !empty( $name ) )
        {
            $this->_headers[ $name ] = trim( $value );
        }
        return $this;
    }

    /**
     * Set headers from an array list
     */
    public function setHeaders( $list )
    {
        $list = Sanitize::toArray( $list );

        foreach( $list as $name => $value )
        {
            if( is_numeric( $name ) && strpos( $value, ":" ) !== false )
            {
                list( $name, $value ) = explode( ":", $value, 2 );
            }
            $this->setHeader( $name, $value );
        }
        return $this;
    }

    /**
     * Remove added header by name
     */
    public function removeHeader( $name )
    {
        $name = Sanitize::toLowerCase( $name );

        if( !empty( $name ) && array_key_exists( $name, $this->_headers ) )
        {
            unset( $this->_headers[ $name ] );
        }
    }

    /**
     * Get header value by key name
     */
    public function getHeader( $name, $default="" )
    {
        $name = Sanitize::toLowerCase( $name );

        if( !empty( $name ) && array_key_exists( $name, $this->_headers ) )
        {
            return $this->_headers[ $name ];
        }
        return $default;
    }

    /**
     * Get all headers
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Sets caching headers for a timestamp
     */
    public function cacheExpire( $value=null )
    {
        $time = Sanitize::toTimestamp( $value );

        if( !empty( $time ) )
        {
            $now  = time();
            $date = gmdate( "D, d M Y H:i:s", $time )." GMT";
            $secs = ( $time > $now ) ? ( $time - $now ) : 0 - ( $now - $time );

            if( $time > $now )
            {
                // future time, set cache headers
                $this->setHeader( "Expires", $date );
                $this->setHeader( "Cache-Control", "max-age=".$secs );
                $this->setHeader( "Pragma", "cache" );
            }
            else
            {
                // past time, prevent caching
                $this->setHeader( "Expires", $date );
                $this->setHeader( "Last-Modified", $date );
                $this->setHeader( "Cache-Control", "no-store, no-cache, must-revalidate, post-check=0, pre-check=0, private" );
                $this->setHeader( "Pragma", "public" );
            }
        }
        return $this;
    }

    /**
     * Sets the Strict-Transport-Security header for a timestamp
     */
    public function rememberSsl( $value=null )
    {
        $time = Sanitize::toTimestamp( $value );

        if( !empty( $time ) )
        {
            $now  = time();
            $secs = ( $time > $now ) ? ( $time - $now ) : 0 - ( $now - $time );
            $this->setHeader( "Strict-Transport-Security", "max-age=".$secs."; includeSubDomains" );
        }
        return $this;
    }

    /**
     * Sets Content-Security-Policy headers for a value
     */
    public function contentSecurityPolicy( $value="" )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( "Content-Security-Policy", $value );
            $this->setHeader( "X-Content-Security-Policy", $value );
            $this->setHeader( "X-Webkit-CSP", $value );
        }
        return $this;
    }

    /**
     * Sets the Content-Type-Options header for a value
     */
    public function contentTypeOptions( $value="" )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( "X-Content-Type-Options", $value );
        }
        return $this;
    }

    /**
     * Sets the Frame-Options header for a value
     */
    public function frameOptions( $value="" )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( "Frame-Options", $value );
            $this->setHeader( "X-Frame-Options", $value );
        }
        return $this;
    }

    /**
     * Sets the XSS-Protection header for a value
     */
    public function xssProtection( $value="" )
    {
        $value = trim( $value );

        if( !empty( $value ) )
        {
            $this->setHeader( "X-XSS-Protection", $value );
        }
        return $this;
    }

    /**
     * Send text response
     */
    public function sendText( $status, $body="" )
    {
        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "text/plain; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send HTML response
     */
    public function sendHtml( $status, $body="" )
    {
        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "text/html; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send XML response
     */
    public function sendXml( $status, $body="" )
    {
        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "application/xml; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send RSS/XML response
     */
    public function sendRss( $status, $body="" )
    {
        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "application/rss+xml; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send SOAP/XML response
     */
    public function sendSoap( $status, $body="" )
    {
        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "application/soap+xml; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send JSON response
     */
    public function sendJson( $status, $data=null )
    {
        if( is_array( $data ) ) { $body = json_encode( $data ); }
        else if( is_string( $data ) ) { $body = trim( $data ); }
        else { $body = "[]"; }

        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "application/json; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send URL-encoded response
     */
    public function sendQuery( $status, $data=null )
    {
        if( is_array( $data ) ) { $body = http_build_query( $data ); }
        else if( is_string( $data ) ) { $body = trim( $data ); }
        else { $body = ""; }

        $this->setStatus( $status );
        $this->setHeader( "Content-Type", "application/x-www-form-urlencoded; charset=".$this->_charset );
        $this->send( $body );
    }

    /**
     * Send file response
     */
    public function sendFile( $status, $mime, $file )
    {
        if( is_file( $file ) )
        {
            $this->setStatus( $status );
            $this->setHeader( "Content-Type", $mime );

            if( preg_match( "/\.php$/i", $file ) === 1 )
            {
                $body = Sanitize::toString( include_once( $file ) );
                $this->send( $body );
            }
            $this->send( "", $file );
        }
        $this->sendText( 404, "File not found." );
    }

    /**
     * Send download response
     */
    public function sendDownload( $status, $file )
    {
        if( is_file( $file ) )
        {
            $this->setStatus( $status );
            $this->setHeader( "Content-Type", "application/octet-stream" );
            $this->setHeader( "Content-Description", "File Transfer" );
            $this->setHeader( "Content-Disposition", "attachment; filename=\"".str_replace( '"', '\"', basename( $file ) )."\"" );
            $this->setHeader( "Content-Length", filesize( $file ) );
            $this->setHeader( "Content-Transfer-Encoding", "binary" );
            $this->setHeader( "Cache-Control", "must-revalidate" );
            $this->setHeader( "Pragma", "public" );
            $this->setHeader( "Expires", "0" );
            $this->send( "", $file );
        }
        $this->sendText( 404, "File not found." );
    }

    /**
     * Send rendered view object html
     */
    public function sendView( $status, $view, $replace=[] )
    {
        if( $view instanceof View )
        {
            $html = $view->render();
            $body = Utils::render( $html, Utils::merge( $replace, [
                "load_time" => Server::getLoadTime( @APP_START_TIME ),
                "mem_usage" => Server::getMemUsage(),
            ]));
            $this->sendHtml( $status, $body );
        }
        $this->sendHtml( $status, "Empty response." );
    }

    /**
     * Send rendered template view html
     */
    public function sendTemplate( $status, $file, $data=[], $replace=[] )
    {
        if( !empty( $file ) && is_string( $file ) )
        {
            $view = new View();
            $view->setTemplate( $file );
            $view->useData( $data );
            $this->sendView( $status, $view, $replace );
        }
        $this->sendHtml( $status, "Empty response." );
    }

    /**
     * Send default response depending on request method and response type
     */
    public function sendDefault( $status, $response=null, $data=[] )
    {
        if( Connection::isMethod( "GET" ) )
        {
            if( is_object( $response ) )
            {
                $this->sendView( $status, $response );
            }
            if( is_array( $response ) )
            {
                $this->sendText( $status, print_r( $response, true ) );
            }
            if( is_string( $response ) )
            {
                if( is_file( $response ) )
                {
                    $this->sendTemplate( $status, $response, $data );
                }
                $this->sendText( $status, $response );
            }
            $this->sendText( $status, "Empty response." );
        }
        $this->sendJson( $status, array_merge( Sanitize::toArray( $response ), $data ) );
    }

    /**
     * Redirect to another URL or path safely
     */
    public function redirect( $location )
    {
        // build possible versions of current route
        $cur_file    = Server::getScriptFile();
        $cur_address = Server::getUrl();
        $cur_clean   = str_replace( "/".basename( $cur_file ), "", $cur_address );

        // convert route path to full URL address
        if( preg_match( "/^\/{1}.*$/ui", $location ) )
        {
            $location = Server::getScriptUrl( $location );
        }
        // new location matches current url/route
        if( $location === $cur_address || $location === $cur_clean )
        {
            $this->sendDefault( 500, "Possible redirect loop detected for new location (".$location.")." );
        }
        // go for it
        $this->setStatus( 302 );
        $this->setHeader( "Location", $location );
        $this->setHeader( "Connection", "close" );
        $this->send();
    }

    /**
     * Build and send final response to client
     */
    public function send( $body="", $file="" )
    {
        @ob_end_clean();
        @ob_clean();

        if( headers_sent() !== true )
        {
            http_response_code( $this->_status );
            header_remove( "server" ); // hide server name/version
            header_remove( "x-powered-by" ); // hide PHP version

            foreach( $this->_headers as $name => $value )
            {
                header( $name.": ".$value, true );
            }
        }
        if( !empty( $body ) && is_string( $body ) )
        {
            echo trim( $body );
        }
        else if( !empty( $file ) && is_file( $file ) )
        {
            readfile( $file );
        }
        flush();
        exit;
    }
}