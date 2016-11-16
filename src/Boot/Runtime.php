<?php
/**
 * App runtime/error handler.
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

class Runtime {

    // dependency checklist
    protected $_checklist = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
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
     * Set single runtime option using ini_set()
     */
    public function setOption( $name, $value=null )
    {
        if( !empty( $name ) && !is_numeric( $name ) )
        {
            ini_set( $name, $value );
        }
    }

    /**
     * Load list of runtime options from array list
     */
    public function loadOptions( $options=[] )
    {
        if( is_array( $options ) )
        {
            foreach( $options as $name => $value )
            {
                $this->setOption( $name, $value );
            }
        }
    }

}