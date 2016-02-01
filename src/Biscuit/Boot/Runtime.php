<?php
/**
 * Handles runtime related procedures.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Boot;

use Biscuit\Http\Client;
use Biscuit\Util\Sanitize;
use Exception;

class Runtime {

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Sets the default charset to be used for everything
     */
    public function setCharset( $charset='' )
    {
        if( !empty( $charset ) && is_string( $charset ) )
        {
            $charset = trim( $charset );
            @mb_internal_encoding( $charset );
            @mb_http_output( $charset );
            @mb_http_input( $charset );
            @mb_regex_encoding( $charset );
        }
    }

    /**
     * Loads an array of conditional checks from a file and throws exceptions if any fails.
     */
    public function checkErrors( $file='' )
    {
        if( is_file( $file ) )
        {
            $checklist = include( $file );

            if( is_array( $checklist ) )
            {
                foreach( $checklist as $key => $data )
                {
                    if( isset( $data['check'] ) && !$data['check'] )
                    {
                        $message = !empty( $data['message'] )
                        ? trim( $data['message'] )
                        : 'One of the runtime checks did not pass ('. $key .').';

                        throw new Exception( 'Runtime error: '.$message );
                    }
                }
            }
        }
    }

    /**
     * Look for a list of ips in a file and deny requests from any of them
     */
    public function denyFrom( $file='' )
    {
        if( !empty( $file ) && is_file( $file ) )
        {
            $ip    = Client::getIp();
            $list  = include( $file );
            $error = '';

            if( is_array( $list ) )
            {
                foreach( $list as $entry )
                {
                    if( !empty( $entry ) && !empty( $ip ) && $entry == $ip )
                    {
                        $error = 'This application does not accept requests from your network.';
                        break;
                    }
                }
            }
            if( !empty( $error ) )
            {
                throw new Exception( 'Runtime error: '.$error, 401 );
            }
        }
    }

    /**
     * Loads data from a text/plain file as INI data and put it into the _ENV array.
     */
    public function loadEnv( $file='' )
    {
        if( is_file( $file ) )
        {
            $data = @parse_ini_file( $file, false );

            if( is_array( $data ) )
            {
                foreach( $data as $key => $value )
                {
                    $key   = Sanitize::toKey( $key );
                    $value = Sanitize::toType( $value );

                    if( empty( $key ) || is_numeric( $key ) || is_string( $key ) !== true )
                    {
                        continue;
                    }
                    if( array_key_exists( $key, $_ENV ) )
                    {
                        continue;
                    }
                    putenv( $key.'='.$value );
                    $_ENV[ $key ] = $value;
                }
            }
        }
    }

    /**
     * Get a value for a key in _ENV, if set, or default
     */
    public function getEnv( $key='', $default=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && array_key_exists( $key, $_ENV ) )
        {
            return $_ENV[ $key ];
        }
        return $default;
    }

    /**
     * Loads an array from a file and uses it to define constants.
     */
    public function defineGlobals( $file='' )
    {
        if( is_file( $file ) )
        {
            $deflist = include( $file );
            $this->_define( $deflist );
        }
    }

    /**
     * Recursive array-to-constant, uses previous key as prefix.
     */
    private function _define( $pairs=[], $prefix='' )
    {
        if( is_array( $pairs ) )
        {
            foreach( $pairs as $key => $value )
            {
                if( is_string( $key ) && !empty( $key ) )
                {
                    if( is_array( $value ) )
                    {
                        $this->_define( $value, $prefix .'_'. $key );
                        continue;
                    }
                    if( is_string( $value ) || is_numeric( $value ) || is_bool( $value ) || is_null( $value ) )
                    {
                        // unserialize string values
                        $tval = strtolower( trim( $value ) );
                        if( $tval === 'true' )     $value = true;
                        if( $tval === 'false' )    $value = false;
                        if( $tval === 'null' )     $value = null;
                        if( is_numeric( $tval ) )  $value = $tval + 0;

                        // define key/val pair
                        $key = $this->_key( $prefix .'_'. $key );
                        if( !defined( $key ) ) define( $key, $value );
                        //echo "Define: ( ".$key.", ".$value." ) <br /> \n";
                    }
                }
            }
        }
    }

    /**
     * Prepare final key name for constant
     */
    private function _key( $key='' )
    {
        $key = preg_replace( '/[^\w]+/', '_', trim( $key, "_-.\s\r\t\n " ) );
        $key = preg_replace( '/\_\_+/', '_', $key );
        return strtoupper( trim( $key, '_' ) );
    }
}