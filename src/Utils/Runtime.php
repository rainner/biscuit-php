<?php
/**
 * App runtime/error handler.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use Closure;
use Exception;
use ErrorException;
use Biscuit\Http\Server;
use Biscuit\Http\Connection;
use Biscuit\Http\Response;

class Runtime {

    // props
    protected $_status    = 500;  // error status code
    protected $_template  = "";   // error template file
    protected $_logdir    = "";   // error logs base dir
    protected $_checklist = [];   // dependency checklist

    /**
     * Constructor
     */
    public function __construct( $options=[] )
    {
        error_reporting( -1 );
        register_shutdown_function( [ $this, "_onShutdown" ] );
        set_error_handler( [ $this, "_onError" ] );
        set_exception_handler( [ $this, "_onException" ] );

        $this->setOptions( array_merge( [
            "display_errors" => 0,
            "display_startup_errors" => 0,
            "report_memleaks" => 1,
            "track_errors" => 0,
            "html_errors" => 0,
            "log_errors" => 0,
            "log_errors_max_len" => 10240,
            "ignore_repeated_errors" => 1,
            "ignore_repeated_source" => 1,
        ], $options ) );
    }

    /**
     * Loads data from a text/plain file as INI data and put it into the _ENV array.
     */
    public function loadEnv( $file )
    {
        if( is_file( $file ) )
        {
            $data = @parse_ini_file( $file, false );

            if( is_array( $data ) )
            {
                foreach( $data as $key => $value )
                {
                    $key   = Sanitize::toKey( $key );
                    $value = Utils::unserialize( $value );

                    if( empty( $key ) || is_numeric( $key ) || is_string( $key ) !== true )
                    {
                        continue;
                    }
                    if( array_key_exists( $key, $_ENV ) )
                    {
                        continue;
                    }
                    putenv( $key."=".$value );
                    $_ENV[ $key ] = $value;
                }
            }
        }
    }

    /**
     * Load list of checklit tests from a file
     */
    public function loadChecklist( $checklist )
    {
        if( is_file( $checklist ) )
        {
            $checklist = include_once( $checklist );
        }
        if( is_array( $checklist ) )
        {
            foreach( $checklist as $c )
            {
                $this->addCheck( @$c["info"], @$c["test"], @$c["pass"], @$c["fail"] );
            }
        }
    }

    /**
     * Get list of added checklist tests
     */
    public function getChecklist()
    {
        return $this->_checklist;
    }

    /**
     * Add single check test to local checklist
     */
    public function addCheck( $info, $test, $pass="", $fail="" )
    {
        $info = Sanitize::toString( $info );
        $test = Sanitize::toBool( $test );
        $pass = Utils::value( $pass, "Ready" );
        $fail = Utils::value( $fail, "Missing" );

        $this->_checklist[] = array(
            "info" => $info,
            "test" => $test,
            "pass" => $pass,
            "fail" => $fail,
        );
    }

    /**
     * Run loaded checklist and handle failed tests
     */
    public function runChecks( $callback=null )
    {
        if( $callback instanceof Closure )
        {
            $callback->bindTo( $this );
            $output = [];

            foreach( $this->_checklist as $check )
            {
                $output[] = call_user_func_array( $callback, $check );
            }
            return $output;
        }

        foreach( $this->_checklist as $check )
        {
            if( isset( $check["test"] ) && $check["test"] !== true )
            {
                throw new Exception( "Runtime Test Failed: ". $check["info"] ." (".$check["fail"].")." );
            }
        }
    }

    /**
     * Set runtime error related options using ini_set()
     */
    public function setOptions( $options=[] )
    {
        if( is_array( $options ) )
        {
            foreach( $options as $key => $value )
            {
                if( !empty( $key ) && !is_numeric( $key ) )
                {
                    ini_set( $key, $value );
                }
            }
        }
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
            // no template, send serialized error data for debugging
            $response->sendText( $this->_status, "
                <h1>".$data["title"]."</h1>
                <pre>".print_r( $error, true )."</pre>
            ");
        }
        // non-GET, send error data as JSON
        $response->sendJson( $this->_status, [
            "status" => $this->_status,
            "error"  => $error,
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