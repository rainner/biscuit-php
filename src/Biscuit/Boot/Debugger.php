<?php
/**
 * For capturing and displaying errors and exceptions.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Boot;

use Biscuit\Mvc\View;
use Biscuit\Http\Response;
use Biscuit\Http\Server;
use Biscuit\Http\Status;
use Biscuit\Util\Numeric;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;
use Biscuit\Storage\Folder;
use ErrorException;
use Exception;

class Debugger extends Events {

    // development mode toggle
    protected $_dev_mode = true;

    // response status code
    protected $_status_code = 500;

    // optional html view template to render
    protected $_path_template = '';

    // optional root path where logs will be saved
    protected $_path_logdir = '';

    // error type string
    protected $_error_type = 'App Error';

    // error message string
    protected $_error_message = 'There has been a problem with the application.';

    // error file path
    protected $_error_file = '';

    // error file line number
    protected $_error_line = 0;

    // error debug backtrace data
    protected $_error_backtrace = array();

    // list of PHP error constants
    protected $_error_map = array(
        E_ERROR              => 'Fatal Error',
        E_WARNING            => 'Script Warning',
        E_NOTICE             => 'Script Notice',
        E_PARSE              => 'Parse Error',
        E_CORE_ERROR         => 'Core Error',
        E_CORE_WARNING       => 'Core Warning',
        E_COMPILE_ERROR      => 'Compile Error',
        E_COMPILE_WARNING    => 'Compile Warning',
        E_USER_ERROR         => 'Custom Error',
        E_USER_WARNING       => 'Custom Warning',
        E_USER_NOTICE        => 'Custom Notice',
        E_USER_DEPRECATED    => 'Deprecated Error',
        E_STRICT             => 'Strict Error',
        E_RECOVERABLE_ERROR  => 'Uncaught Error',
        E_DEPRECATED         => 'Deprecated Error',
    );

    /**
     * Constructor
     */
    public function __construct( $capture=E_ALL )
    {
        // error capturing options
        @error_reporting( $capture );
        @ini_set( "display_errors", 0 );
        @ini_set( "display_startup_errors", 0 );
        @ini_set( "report_memleaks", 1 );
        @ini_set( "track_errors", 0 );
        @ini_set( "html_errors", 0 );

        // error logging options
        @ini_set( "log_errors", 0 );
        @ini_set( "log_errors_max_len", 10240 );
        @ini_set( "ignore_repeated_errors", 1 );
        @ini_set( "ignore_repeated_source", 1 );

        // handle errors and exceptions
        @register_shutdown_function( array( $this, '_shutdownHandler' ) );
        @set_error_handler( array( $this, '_errorHandler' ) );
        @set_exception_handler( array( $this, '_exceptionHandler' ) );
    }

    /**
     * Toggle error reporting mode
     */
    public function developmentMode( $value=true )
    {
        if( is_bool( $value ) )
        {
            $this->_dev_mode = $value;
        }
    }

    /**
     * Checks if in dev mode
     */
    public function inDevelopmentMode()
    {
        return $this->_dev_mode;
    }

    /**
     * Set the error response status code
     */
    public function setStatusCode( $value=500 )
    {
        if( is_numeric( $value ) )
        {
            $this->_status_code = intval( $value );
        }
    }

    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->_status_code;
    }

    /**
     * Sets the error type string
     */
    public function setErrorType( $value='' )
    {
        if( !empty( $value ) && is_string( $value ) )
        {
            $this->_error_type = Sanitize::toText( $value );
        }
    }

    /**
     * Get error type
     */
    public function getErrorType()
    {
        return $this->_error_type;
    }

    /**
     * Sets the error message string
     */
    public function setErrorMessage( $value='' )
    {
        if( !empty( $value ) && is_string( $value ) )
        {
            $this->_error_message = str_replace( '\\', '/', Sanitize::toText( $value ) );
        }
    }

    /**
     * Get error message
     */
    public function getErrorMessage()
    {
        return $this->_error_message;
    }

    /**
     * Sets the error file path
     */
    public function setErrorFile( $value='' )
    {
        if( is_string( $value ) )
        {
            $this->_error_file = Sanitize::toPath( $value );
        }
    }

    /**
     * Get error file
     */
    public function getErrorFile()
    {
        return $this->_error_file;
    }

    /**
     * Sets the error line number
     */
    public function setErrorLine( $value=0 )
    {
        if( is_numeric( $value ) )
        {
            $this->_error_line = intval( $value );
        }
    }

    /**
     * Get error line
     */
    public function getErrorLine()
    {
        return $this->_error_line;
    }

    /**
     * Sets the error message string
     */
    public function setErrorBacktrace( $backtrace=array() )
    {
        if( is_array( $backtrace ) )
        {
            $this->_error_backtrace = array_reverse( array_values( $backtrace ) );
        }
    }

    /**
     * Get error backtrace
     */
    public function getErrorBacktrace()
    {
        return $this->_error_backtrace;
    }

    /**
     * Set the error view template file to be rendered on error/exception
     */
    public function setTemplateFile( $value='' )
    {
        if( is_string( $value ) )
        {
            $this->_path_template = Sanitize::toPath( $value );
        }
    }

    /**
     * Get template file path
     */
    public function getTemplateFile()
    {
        return $this->_path_template;
    }

    /**
     * Set the root path where log files will be stored
     */
    public function setLogBasePath( $value='' )
    {
        if( is_string( $value ) )
        {
            $this->_path_logdir = Sanitize::toPath( $value );
        }
    }

    /**
     * Get log base path
     */
    public function getLogBasePath()
    {
        return $this->_path_logdir;
    }

    /**
     * Triggers a custom error exception to be rendered/logged
     */
    public function customError( $status=500, $type='', $message='', $file='', $line=0, $backtrace=[] )
    {
        $this->setStatusCode( $status );
        $this->setErrorType( $type );
        $this->setErrorMessage( $message );
        $this->setErrorFile( $file );
        $this->setErrorLine( $line );
        $this->setErrorBacktrace( $backtrace );
        $this->render();
    }

    /**
     * Builds final error log data and output view
     */
    public function render()
    {
        $this->triggerEvent( 'renderOutput' );
        $this->_logError();

        if( $this->_dev_mode === true )
        {
            if( strtolower( trim( @$_SERVER['REQUEST_METHOD'] ) ) === 'get' )
            {
                if( !empty( $this->_path_template ) && is_file( $this->_path_template ) )
                {
                    $this->_renderTemplate(); // exit
                }
                $this->_renderDefault(); // exit
            }
            $this->_renderJson(); // exit
        }
    }

    /**
     * Catches shutdown errors and passes it to the error handler
     */
    public function _shutdownHandler()
    {
        $e = error_get_last();

        if( array_key_exists( $e['type'], $this->_error_map ) )
        {
            $this->_errorHandler( $e['type'], $e['message'], $e['file'], $e['line'] );
        }
        return true;
    }

    /**
     * Checks error type against error_reporting and passes it down as an Exception
     */
    public function _errorHandler( $type, $message, $file, $line, $context=null )
    {
        if( error_reporting() & $type )
        {
            $this->_exceptionHandler( new ErrorException( $message, 0, $type, $file, $line ) );
        }
        return true;
    }

    /**
     * All errors will be handled here as an Exception
     */
    public function _exceptionHandler( Exception $e )
    {
        $this->setStatusCode( 500 );
        $this->setErrorType( $this->_getLastError() );
        $this->setErrorMessage( $e->getMessage() );
        $this->setErrorFile( $e->getFile() );
        $this->setErrorLine( $e->getLine() );
        $this->setErrorBacktrace( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );
        $this->render();
    }

    /**
     * Renders a custom HTML template
     */
    private function _renderTemplate()
    {
        $base = dirname( $this->_path_template );
        $file = basename( $this->_path_template );

        $data = $this->_getOutputData( true );
        $data = $this->filterEvent( 'renderTemplate', $data );

        $view = new View();
        $view->setPlublicPath( $base );
        $view->addRenderPath( $base );
        $view->setTemplate( '/'.$file );
        $view->setData( $data );

        $response = new Response();
        $response->setHtml( $this->_status_code, $view->render() );
        $response->send();
        exit;
    }

    /**
     * Renders a basic error response
     */
    private function _renderDefault()
    {
        $data = $this->_getOutputData( true );
        $data = $this->filterEvent( 'renderDefault', $data );

        $html = trim( '
        <!DOCTYPE html>
        <html>
        <head>
            <title>'.$this->_error_type.'</title>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <meta name="description" content="'.$this->_error_message.'" />
            <meta name="robots" content="none" />
        </head>
        <body style="margin: 0; padding: 0; font-size: 14px; line-height: 20px; font-family: monaco, monospace;">
            <div style="margin: 20px; padding: 20px;">
                <h1>'.$this->_status_code.': '.$this->_error_type.'</h1>
                <h3 style="color: #930">'.$this->_error_message.'</h3>
                <pre style="padding: 20px; background: #ffd;">'.print_r( $data, true ).'</pre>
            </div>
        </body>
        </html>' );

        $response = new Response();
        $response->setHtml( $this->_status_code, $html );
        $response->send();
        exit;
    }

    /**
     * Renders a JSON encoded error response
     */
    private function _renderJson()
    {
        $data = $this->_getOutputData( false );
        $data = $this->filterEvent( 'renderJson', $data );

        $response = new Response();
        $response->setJson( $this->_status_code, $data );
        $response->send();
        exit;
    }

    /**
     * Logs an error message to file
     */
    private function _logError()
    {
        if( !empty( $this->_path_logdir ) )
        {
            $date = date( 'Y.m.d h:i A' );
            $head = '['.$this->_status_code.': '.$this->_error_type.']';
            $tail = '['.$this->_error_line.': '.$this->_error_file.']';

            $logfile  = $this->_path_logdir;
            $logfile .= '/'.date( 'Y' ); // add year subfolder (/2015)
            $logfile .= '/'.date( 'F' ); // add month subfolder (/January)
            $logfile .= '/'.date( 'Y\-F\-d\-l' ).'-Errors.log'; // final (/2015-January-01-Monday-Errors.log)

            $folder = new Folder( $this->_path_logdir );
            $folder->create();
            $folder->garbageCollect( strtotime( '-5 days' ) );
            $folder->setPath( dirname( $logfile ) );
            $folder->create();

            $message = $date.' - '.$head.' - '.$this->_error_message.' - '.$tail;
            $message = $this->filterEvent( 'logMessage', $message );

            return @error_log( trim( $message )."\r\n", 3, $logfile );
        }
        return false;
    }

    /**
     * Get last error type string
     */
    private function _getLastError( $default='App Error' )
    {
        $e = error_get_last();

        if( array_key_exists( $e['type'], $this->_error_map ) )
        {
            return $this->_error_map[ $e['type'] ];
        }
        return $default;
    }

    /**
     * Parses a stack trace array and returns a new array
     */
    private function _getBacktrace()
    {
        $output = array();
        $count  = 1;

        foreach( $this->_error_backtrace as $index => $item )
        {
            $file     = Utils::getValue( @$item['file'], '' );
            $line     = Utils::getValue( @$item['line'], 0 );
            $class    = Utils::getValue( @$item['class'], '' );
            $function = Utils::getValue( @$item['function'], '' );
            $type     = Utils::getValue( @$item['type'], '' );

            if( empty( $file ) || $function === __FUNCTION__ ) continue;

            $source   = $class . $type . $function;
            $callable = basename( str_replace( '\\', '/', $source ) );
            $file     = $this->_relativePath( $file );
            $line     = (double) $line;

            $output[] = array(

                'count'     => $count,
                'file'      => $file,
                'type'      => $type,
                'class'     => $class,
                'function'  => $function,
                'line'      => number_format( $line, 0 ),
                'source'    => $source,
                'callable'  => $callable . '()',
            );
            $count++;
        }
        return $output;
    }

    /**
     * Reads a few lines from a file around where the error was triggered
     */
    private function _getSourceCode( $file='', $line=1, $pad=5 )
    {
        $line   = is_numeric( $line ) ? intval( $line ) : 1;
        $pad    = is_numeric( $pad ) ? intval( $pad ) : 5;
        $start  = ( $line > $pad ) ? ( $line - $pad ) : $line;
        $end    = ( $line + $pad );
        $count  = 0;
        $code   = "";

        if( !empty( $file ) && is_file( $file ) )
        {
            $fo = fopen( $file, 'r' );

            while( ( $fl = fgets( $fo ) ) !== false )
            {
                $count++;
                if( $count < $start ) continue;
                if( $count > $end ) break;

                $c = rtrim( $fl ) . "\n";
                $c = htmlspecialchars( $c );
                $c = str_replace( "\s", " ", $c );
                $c = str_replace( "\t", "    ", $c );
                $code .= $c;
            }
            fclose( $fo );
        }
        if( substr( trim( $code ), 0, 1 ) === '*' )
        {
            $code = "/**\n" . $code;
            $start--;
        }
        if( substr( trim( $code ), -1, 1 ) === '*' )
        {
            $code .= "\n*/";
        }
        if( !empty( $code ) )
        {
            $code = str_replace( "\n", "<br />", rtrim( $code ) );
        }
        return array(
            'start_line' => $start,
            'error_line' => $line,
            'error_code' => $code,
        );
    }

    /**
     * Calculates the total app runtime speed, if possible
     */
    private function _getRuntimeSpeed()
    {
        $speed = defined( 'RUNTIME_START' ) ? ( microtime( true ) - RUNTIME_START ) : 0.0;
        return number_format( $speed, 6, '.', '' );
    }

    /**
     * Builds and returns error output data for views
     */
    private function _getOutputData( $full=true )
    {
        $output = array(
            'status' => $this->_status_code,
            'info'   => $this->_error_type,
            'error'  => $this->_error_message,
            'file'   => $this->_relativePath( $this->_error_file ),
            'line'   => $this->_error_line,
            'date'   => date( 'r' ),
            'url'    => Server::getUrl(),
            'host'   => Server::getHost(),
            'domain' => Server::getDomain(),
            'memory' => Numeric::toSize( memory_get_peak_usage( true ) ),
            'speed'  => $this->_getRuntimeSpeed(),
        );
        if( $full === true )
        {
            $output['headers'] = getallheaders();

            if( !empty( $this->_error_backtrace ) )
            {
                $output['trace'] = $this->_getBacktrace();
            }
            if( !empty( $this->_error_file ) && !empty( $this->_error_line ) )
            {
                $output['source'] = $this->_getSourceCode( $this->_error_file, $this->_error_line );
            }
        }
        return $output;
    }

    /**
     * Cleans a path and removes the doc root from it
     */
    private function _relativePath( $path='' )
    {
        $path = Sanitize::toPath( $path );
        $root = Sanitize::toPath( $_SERVER['DOCUMENT_ROOT'] );

        foreach( explode( '/', $root ) as $dir )
        {
            $path = str_replace( $dir.'/', '', $path );
        }
        return '/'.$path;
    }

}