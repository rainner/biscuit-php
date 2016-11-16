<?php
/**
 * Error handling class.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Boot;

use Closure;
use Exception;
use ErrorException;
use Biscuit\Http\Server;
use Biscuit\Http\Connection;
use Biscuit\Http\Response;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Error {

    // props
    protected $_status   = 500;  // error status code
    protected $_template = "";   // error template file
    protected $_logdir   = "";   // error logs base dir

    /**
     * Constructor
     */
    public function __construct( $capture=E_ALL )
    {
        $this->setCapture( $capture );

        register_shutdown_function( [ $this, "_onShutdown" ] );
        set_error_handler( [ $this, "_onError" ] );
        set_exception_handler( [ $this, "_onException" ] );

        ini_set( "display_errors", 0 );
        ini_set( "display_startup_errors", 0 );
        ini_set( "report_memleaks", 1 );
        ini_set( "track_errors", 0 );
        ini_set( "html_errors", 0 );
        ini_set( "log_errors", 0 );
        ini_set( "log_errors_max_len", 10240 );
        ini_set( "ignore_repeated_errors", 1 );
        ini_set( "ignore_repeated_source", 1 );
    }

    /**
     * Set error reporting option
     */
    public function setCapture( $capture )
    {
        error_reporting( $capture );
    }

    /**
     * Set default resposne status code
     */
    public function setStatus( $status=500 )
    {
        if( is_numeric( $status ) )
        {
            $this->_status = intval( $status );
        }
    }

    /**
     * Set file to save error_log messages to
     */
    public function setLogPath( $path )
    {
        $path = Sanitize::toPath( $path );

        if( is_dir( $path ) || mkdir( $path, 0775, true ) )
        {
            $this->_logdir = $path;
        }
    }

    /**
     * Set error template file to render
     */
    public function setTemplate( $file )
    {
        if( is_string( $file ) )
        {
            $this->_template = trim( $file );
        }
    }

    /**
     * Logs an error message to file
     */
    public function log( $message )
    {
        if( !empty( $this->_logdir ) && is_string( $message ) )
        {
            if( is_dir( $this->_logdir ) || mkdir( $this->_logdir, 0775, true ) )
            {
                $message = "[".date( "Y-m-d h:i:s A T" )."] ".trim( $message )."\r\n";
                $logfile = $this->_logdir ."/". date( "Y_m_d" )."_errors.log";
                $oldfile = strtotime( "-3 days" ); // keep 3 days of log files

                foreach( glob( $this->_logdir."/*.log" ) as $f ) // gc
                {
                    if( filemtime( $f ) <= $oldfile ) unlink( $f );
                }
                return error_log( $message, 3, $logfile );
            }
        }
        return false;
    }

    /**
     * Catches shutdown errors and passes it to the error handler
     */
    public function _onShutdown( $error=null )
    {
        $e = is_array( $error ) ? $error : error_get_last();

        if( in_array( $e["type"], [ E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR ] ) )
        {
            $this->_onError( $e["type"], $e["message"], $e["file"], $e["line"] );
        }
    }

    /**
     * Checks error type against error_reporting and passes it down as an Exception
     */
    public function _onError( $type, $message, $file, $line, $context=null )
    {
        if( $type & error_reporting() )
        {
            $this->_onException( new ErrorException( $message, $type, $type, $file, $line ) );
        }
    }

    /**
     * Renders an error template for PHP errors and Exceptions
     */
    public function _onException( $e )
    {
        $response = new Response();
        $error    = $this->_filterError( $e );
        $data     = [
            "status"      => $this->_status,
            "title"       => $this->_status.": ".$error["type"],
            "description" => "There has been a problem of type (".$error["type"].").",
            "address"     => Server::getUrl(),
            "method"      => Connection::getMethod(),
            "date"        => date( "F jS Y h:i:s A T" ),
            "headers"     => getallheaders(),
            "error"       => $error,
        ];
        // send error data to log file
        $this->log( $error["type"].": ".$error["message"]." in ".$error["file"]." on line ".$error["line"]."." );

        // send http reposnse
        if( Connection::isMethod( "GET" ) )
        {
            // template has been set...
            if( !empty( $this->_template ) && is_file( $this->_template ) )
            {
                // send all data to template to be rendered as needed
                $response->sendTemplate( $this->_status, $this->_template, $data );
            }
            // no template, send just error description for security reasons
            $response->sendText( $this->_status, $data["description"] );
        }
        // non-GET, send error description data as JSON
        $response->sendJson( $this->_status, [
            "status" => $this->_status,
            "error"  => $data["description"],
        ]);
    }

    /**
     * Filter data from given exception object into an array
     */
    protected function _filterError( $error )
    {
        $message  = $error->getMessage();
        $file     = $error->getFile();
        $code     = $error->getCode();
        $line     = $error->getLine();
        $type     = $this->_getType( $code, $message );

        return [
            "type"    => $type,
            "message" => Utils::relativePath( $message ),
            "file"    => Utils::relativePath( $file ),
            "line"    => $line,
            "code"    => $code,
        ];
    }

    /**
     * Resolve the error type string
     */
    protected function _getType( $code, $message="" )
    {
        $map = array(
            E_ERROR              => "Fatal Error",
            E_WARNING            => "Script Warning",
            E_NOTICE             => "Script Notice",
            E_PARSE              => "Parse Error",
            E_CORE_ERROR         => "Core Error",
            E_CORE_WARNING       => "Core Warning",
            E_COMPILE_ERROR      => "Compile Error",
            E_COMPILE_WARNING    => "Compile Warning",
            E_USER_ERROR         => "Custom Error",
            E_USER_WARNING       => "Custom Warning",
            E_USER_NOTICE        => "Custom Notice",
            E_USER_DEPRECATED    => "Deprecated Error",
            E_STRICT             => "Strict Error",
            E_RECOVERABLE_ERROR  => "Uncaught Error",
            E_DEPRECATED         => "Deprecated Error",
        );
        if( !empty( $message ) && preg_match( "/^.*(SQL|PDO|Database).*$/ui", $message ) )
        {
            return "Database Error";
        }
        if( array_key_exists( $code, $map ) )
        {
            return $map[ $code ];
        }
        return "App Error";
    }

}