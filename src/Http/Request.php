<?php
/**
 * Parses and sanitizes incoming request data from the client.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Biscuit\Data\Registry;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Request {

    // props
    protected $_registry = null;
    protected $_tmpdir   = "";
    protected $_maxsize  = "";
    protected $_maxpost  = "";
    protected $_ctype    = "";
    protected $_body     = "";

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_registry = new Registry();
        $this->_tmpdir   = ini_get( "upload_tmp_dir" );
        $this->_maxsize  = ini_get( "upload_max_filesize" );
        $this->_maxpost  = ini_get( "post_max_size" );

        $this->_resolveContentType();
        $this->_readRequestBody();
        $this->_parseRequestBody();
        $this->_cleanGlobals();
        $this->_cleanFiles();
    }

    /**
     * Get request value by key (not-notation)
     */
    public function request( $key="", $default=null )
    {
        $this->_registry->useData( $_REQUEST );
        return $this->_registry->getKey( $key, $default );
    }

    /**
     * Get request arg value by key (not-notation)
     */
    public function args( $key="", $default=null )
    {
        $this->_registry->useData( $_GET );
        return $this->_registry->getKey( $key, $default );
    }

    /**
     * Get request param value by key (not-notation)
     */
    public function params( $key="", $default=null )
    {
        $this->_registry->useData( $_POST );
        return $this->_registry->getKey( $key, $default );
    }

    /**
     * Get request cookie value by key (not-notation)
     */
    public function cookies( $key="", $default=null )
    {
        $this->_registry->useData( $_COOKIE );
        return $this->_registry->getKey( $key, $default );
    }

    /**
     * Get request file value by key (not-notation)
     */
    public function files( $key="", $default=null )
    {
        $this->_registry->useData( $_FILES );
        return $this->_registry->getKey( $key, $default );
    }

    /**
     * Get request header value by name
     */
    public function headers( $name="", $default="" )
    {
        $name = strtolower( Sanitize::toSlug( $name ) );

        if( !empty( $name ) && function_exists( "getallheaders" ) )
        {
            foreach( getallheaders() as $k => $value )
            {
                if( strtolower( Sanitize::toSlug( $k ) ) === $name )
                {
                    return $value;
                }
            }
        }
        return $default;
    }

    /**
     * Parse content type string from headers
     */
    private function _resolveContentType()
    {
        $value = $this->headers( "content-type" );
        parse_str( "type=".preg_replace( "/;\s*/", "&", $value ), $data );
        $this->_ctype = Utils::value( @$data["type"], "" );
    }

    /**
     * Read and return the contents of the request body as a string
     */
    private function _readRequestBody()
    {
        $this->_body = "";
        $input = fopen( "php://input", "r" );
        while( $data = fread( $input, 1024 ) ) $this->_body .= $data;
        fclose( $input );
    }

    /**
     * Parses the content body string if needed based on content-type
     */
    private function _parseRequestBody()
    {
        if( !empty( $this->_body ) && !empty( $this->_ctype ) )
        {
            if( preg_match( "/url\-?encoded/i", $this->_ctype ) )
            {
                $this->_parseString();
            }
            else if( preg_match( "/jsonp?/i", $this->_ctype ) )
            {
                $this->_parseJson();
            }
            else if( preg_match( "/soap|xml/i", $this->_ctype ) )
            {
               $this->_parseXml();
            }
            else if( preg_match( "/form\-?data/i", $this->_ctype ) )
            {
                $this->_parseForm();
            }
        }
    }

    /**
     * Parse the headers of a multipart chunk into an array
     */
    private function _parseBodyHeaders( $headers="" )
    {
        $output = [];

        if( is_string( $headers ) )
        {
            $headers = trim( $headers );
            $headers = preg_replace( "/([\r\n]+)|(\;\s*)/", "&", $headers );
            $headers = preg_replace( "/\:\s*/", "=", $headers );
            parse_str( $headers, $pairs );

            if( is_array( $pairs ) )
            {
                foreach( $pairs as $name => $value )
                {
                    $output[ strtolower( $name ) ] = stripslashes( trim( $value, '" ' ) );
                }
            }
        }
        return $output;
    }

    /**
     * Parse request body as url-encoded string
     */
    private function _parseString()
    {
        parse_str( $this->_body, $_POST );
    }

    /**
     * Parse request body as JSON
     */
    private function _parseJson()
    {
        $_POST = @json_decode( $this->_body, true );
    }

    /**
     * Parse request body as XML
     */
    private function _parseXml()
    {
        libxml_use_internal_errors( true );
        $opts  = LIBXML_NOCDATA | LIBXML_NOEMPTYTAG | LIBXML_NOBLANKS | LIBXML_NSCLEAN | LIBXML_NOENT;
        $xml   = simplexml_load_string( $this->_body, "SimpleXMLElement", $opts );
        $_POST = @json_decode( @json_encode( $xml ), true );
    }

    /**
     * Parse request body as multipart FormData
     */
    private function _parseForm()
    {
        $boundary = trim( strtok( $this->_body, "\n" ) );
        $chunks   = preg_split( "/".$boundary."(\-\-)?/", $this->_body, -1, PREG_SPLIT_NO_EMPTY );
        $params   = [];
        $files    = [];
        $counter  = [];

        if( is_array( $chunks ) )
        {
            foreach( $chunks as $index => $chunk )
            {
                // skip empty chunks
                $chunk = ltrim( $chunk, "-\r\n\t\s " );
                if( empty( $chunk ) ) continue;

                // split chunk into headers and value
                @list( $head, $value ) = explode( "\r\n\r\n", $chunk, 2 );
                $headers = $this->_parseBodyHeaders( $head );
                $name    = Utils::value( @$headers["name"], "undefined_".( $index + 1 ) );
                $type    = Utils::value( @$headers["content-type"], "application/octet-stream" );
                $key     = Sanitize::toKey( $name );
                $value   = trim( $value );

                // counter to increment array-like param names
                if( isset( $counter[ $key ] ) !== true )
                {
                    $counter[ $key ] = 0;
                }
                // process uploaded file
                if( isset( $headers["filename"] ) )
                {
                    $file = $headers["filename"];
                    $name = str_replace( "[]", "", $name );
                    $path = "";
                    $copy = false;

                    if( !empty( $headers["filename"] ) && !empty( $value ) )
                    {
                        $path = Sanitize::toPath( tempnam( $this->_tmpdir, "upload" ) );
                        $copy = file_put_contents( $path, $value );
                    }
                    if( preg_match( "/\[\d+\]$/", $name ) !== 1 )
                    {
                        $name .= "[".$counter[ $key ]."]";
                    }
                    $files[ $name."[name]" ]      = $file;
                    $files[ $name."[type]" ]      = $type;
                    $files[ $name."[tmp_name]" ]  = $path;
                    $files[ $name."[error]" ]     = !empty( $copy ) ? 0 : UPLOAD_ERR_NO_FILE;
                    $files[ $name."[size]" ]      = !empty( $copy ) ? filesize( $path ) : 0;
                }
                else // param data
                {
                    if( preg_match( "/\[\]$/", $name ) === 1 )
                    {
                        $name = str_replace( "[]", "[".$counter[ $key ]."]", $name );
                    }
                    $params[ $name ] = $value;
                }
                $counter[ $key ] += 1;
            }
            // finalize arrays
            parse_str( urldecode( http_build_query( $params, "", "&" ) ), $_POST );
            parse_str( urldecode( http_build_query( $files, "", "&" ) ), $_FILES );
        }
    }

    /**
     * Make sure all the globals are set
     */
    private function _cleanGlobals()
    {
        $_GET     = Sanitize::toArray( @$_GET );
        $_POST    = Sanitize::toArray( @$_POST );
        $_FILES   = Sanitize::toArray( @$_FILES );
        $_COOKIE  = Sanitize::toArray( @$_COOKIE );
        $_REQUEST = array_merge( $_GET, $_POST );
    }

    /**
     * Clean up the FILES array
     */
    private function _cleanFiles()
    {
        if( !empty( $_FILES ) && is_array( $_FILES ) )
        {
            // 1. convert files array into an URL encoded string
            $query  = urldecode( http_build_query( $_FILES, "", "&" ) );
            $lines  = explode( "&", $query );
            $param  = "";
            $build  = "";

            // 2. loop each key=value as individual line
            foreach( $lines as $line )
            {
                // 3. split line into separate key and value
                $parts = explode( "=", $line );
                $key   = isset( $parts[0] ) ? $parts[0] : "";
                $value = isset( $parts[1] ) ? $parts[1] : "";

                // 4. extract the fileinfo property from the key
                if( preg_match( "/(\[(name|type|tmp_name|error|size)\])/", $key, $prop ) )
                {
                    // 5. remove fileinfo prop from key
                    $key = str_replace( $prop[1], "", $key );

                    // 6. add a [0] to the end of key if dealing with a single file
                    if( !preg_match( "/\[[0-9]+\]$/", $key ) ) $key .= "[0]";

                    // 7. add the fileinfo prop to the end of key
                    $key .= $prop[1];

                    // 8. clean the value and add to final string
                    $param  = preg_replace( "/[^\w]+/", "", $prop[1] );
                    $value  = $this->_paramValue( $param, $value );
                    $build .= $key ."=". $value ."&";
                }
            }
            // 9. convert the new encoded values back into an array
            if( !empty( $build ) ) parse_str( $build, $_FILES );
        }
    }

    /**
     * Sanitizes a value for different param types
     */
    private function _paramValue( $param="", $value="" )
    {
        if( $param === "name" )
        {
            $value = trim( preg_replace( "/[^\w\-\.]+/i", "_", $value ) );
        }
        else if( $param === "type" || $param === "tmp_name" )
        {
            $value = trim( str_replace( "\\", "/", $value ) );
        }
        else if( $param === "size" && is_numeric( $value ) )
        {
            $value = intval( $value );
        }
        else if( $param === "error" && is_numeric( $value ) )
        {
            switch( intval( $value ) )
            {
                case UPLOAD_ERR_INI_SIZE   : $value = "The file size exceeds the (upload_max_filesize: ".$this->_maxsize.") server limit."; break;
                case UPLOAD_ERR_FORM_SIZE  : $value = "The file size exceeds the (max_file_size: ".$this->_maxpost.") http/post limit."; break;
                case UPLOAD_ERR_PARTIAL    : $value = "The file was only partially uploaded and could not be saved."; break;
                case UPLOAD_ERR_NO_FILE    : $value = "The server did not receive any file contents to be saved."; break;
                case UPLOAD_ERR_NO_TMP_DIR : $value = "The server had no temporary folder to store the file."; break;
                case UPLOAD_ERR_CANT_WRITE : $value = "The server does not have permission to copy the file contents."; break;
                case UPLOAD_ERR_EXTENSION  : $value = "A server script extension stopped the file upload."; break;
                default:                     $value = "";
            }
        }
        return $value;
    }

    // ...
}