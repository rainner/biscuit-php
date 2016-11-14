<?php
/**
 * Handles management of server session data.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Session;

use Exception;
use Biscuit\Http\Server;
use Biscuit\Http\Connection;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Random;
use Biscuit\Utils\Utils;

class Session {

    // container name for session data
    protected $container = "_appdata_";

    // lifetime of session id before it is regenerated
    protected $idexpire = 300;

    // http://php.net/manual/en/session.configuration.php
    protected $options = array();

    // if session has been started
    protected $started = false;

    /**
     * Constructor
     */
    public function __construct( $name="", $expire=null )
    {
        // factory options
        $this->setOption( "name", "session" );
        $this->setOption( "cookie_lifetime", 0 );
        $this->setOption( "cookie_path", "/" );
        $this->setOption( "cookie_domain", Server::getHost() );
        $this->setOption( "cookie_secure", Connection::isSecure() );
        $this->setOption( "cookie_httponly", true );
        $this->setOption( "use_only_cookies", true );
        $this->setOption( "use_trans_sid", false );
        $this->setOption( "entropy_file", "/dev/urandom" );
        $this->setOption( "entropy_length", 256 );
        $this->setOption( "hash_function", "sha256" );
        $this->setOption( "hash_bits_per_character", 6 );
        $this->setOption( "gc_maxlifetime", 86400 ); // 24h

        // set custom values
        $this->setName( $name );
        $this->setExpire( $expire );
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Set the session ID regeneration time
     */
    public function setRenewIdTime( $seconds=300 )
    {
        if( !empty( $seconds ) && is_int( $seconds ) )
        {
            $this->idexpire = $seconds;
        }
    }

    /**
     * Get the session cookie id name
     */
    public function getId()
    {
        if( $this->started === true )
        {
            return session_id();
        }
        return "";
    }

    /**
     * Update the session cookie id name
     */
    public function updateId()
    {
        if( $this->started === true && headers_sent() === false )
        {
            return session_regenerate_id( true );
        }
        return false;
    }

    /**
     * Set the container name for session data
     */
    public function setContainerName( $name="" )
    {
        $name = Sanitize::toKey( $name );

        if( !empty( $name ) )
        {
            $this->container = $name;
        }
    }

    /**
     * Set the name for the session cookie
     */
    public function setName( $name="" )
    {
        $name = Sanitize::toKey( $name );

        if( !empty( $name ) )
        {
            $this->setOption( "name", $name );
        }
    }

    /**
     * Get the session name
     */
    public function getName()
    {
        if( $this->started === true )
        {
            return session_name();
        }
        return $this->getOption( "name", "" );
    }

    /**
     * Set the time the session data will last
     */
    public function setExpire( $expire=3600 )
    {
        if( is_string( $expire ) )
        {
            $expire = ( intval( strtotime( trim( $expire ) ) ) - time() ) * 1;
        }
        if( is_int( $expire ) )
        {
            $this->setOption( "cookie_lifetime", $expire );
        }
    }

    /**
     * Set the base path where session files will be saved
     */
    public function setSavePath( $path="" )
    {
        $path = Sanitize::toPath( $path );

        if( !empty( $path ) )
        {
            if( is_dir( $path ) || mkdir( $path , 0777, true ) )
            {
                $this->setOption( "save_path", $path );
            }
        }
    }

    /**
     * Merge the local options with a new set
     */
    public function setOptions( $options=array() )
    {
        if( !empty( $options ) && is_array( $options ) )
        {
            $this->options = array_merge( $this->options, $options );
        }
    }

    /**
     * Get the list of options as is
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set a new option key and value
     */
    public function setOption( $key="", $value=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) )
        {
            $this->options[ $key ] = $value;
        }
    }

    /**
     * Returns a valus for an option key, or default value
     */
    public function getOption( $key="", $default=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && array_key_exists( $key, $this->options ) )
        {
            return $this->options[ $key ];
        }
        return $default;
    }

    /**
     * Checks if an option key exists
     */
    public function hasOption( $key="" )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && array_key_exists( $key, $this->options ) )
        {
            return true;
        }
        return false;
    }

    /**
     * Get information about the session cookie
     */
    public function getCookieInfo()
    {
        return array_merge( array(
            "id"   => $this->getId(),
            "name" => $this->getName(),
        ), session_get_cookie_params() );
    }

    /**
     * Generates a new CSRF-Protection token and returns it
     */
    public function getXPtoken()
    {
        $token = Random::encoded();
        $this->set( "_xsrfp_", $token );
        return $token;
    }

    /**
     * Compare current CSRF-Protection token with existing token
     */
    public function checkXPtoken( $token="" )
    {
        $value = $this->get( "_xsrfp_", "" );
        return ( $token === $value );
    }

    /**
     * Setup and start a new session
     */
    public function start()
    {
        if( $this->started !== true )
        {
            if( headers_sent() )
            {
                throw new Exception( "Tried to start a new session but output headers have already been sent." );
            }
            if( empty( $this->getOption( "name" ) ) )
            {
                throw new Exception( "Tried to start a new session without setting a session name to use." );
            }
            foreach( $this->options as $key => $value )
            {
                @ini_set( "session.".$key, $value );
            }
            if( $this->started = session_start() )
            {
                // grab/prep some data
                $current_time = time();
                $status_key   = "_status_";
                $renew_ttl    = $this->idexpire;
                $data_ttl     = $this->getOption( "gc_maxlifetime", 86400 );
                $cookie_ttl   = $this->getOption( "cookie_lifetime", 0 );
                $last_active  = $this->get( $status_key.".last_active", 0 );
                $past_time    = $current_time - $last_active;

                // run session maintenance
                if( $last_active > 0 )
                {
                    if( $data_ttl > 0 && $past_time > $data_ttl )
                    {
                        $this->restart(); // garbage collect
                    }
                    if( $renew_ttl > 0 && $past_time > $renew_ttl )
                    {
                        $this->updateId(); // renew session ID
                    }
                    if( $cookie_ttl > 0 )
                    {
                        $this->extendCookie( $cookie_ttl ); // renew cookie
                    }
                }
                // update timestamps
                $this->setOnce( $status_key.".start_time", $current_time );
                $this->set( $status_key.".last_active", $current_time );
            }
        }
        return $this->started;
    }

    /**
     * Extends the session cookie lifetime period
     */
    public function extendCookie( $expire=null )
    {
        $time    = time();
        $default = $time + $this->getOption( "cookie_lifetime", 0 );
        $expire  = Utils::value( Sanitize::toTimestamp( $expire ), $default );

        if( $expire > $time )
        {
            return setcookie(
                session_name(),
                session_id(),
                $expire,
                $this->getOption( "cookie_path" ),
                $this->getOption( "cookie_domain" ),
                $this->getOption( "cookie_secure" ),
                $this->getOption( "cookie_httponly" )
            );
        }
        return false;
    }

    /**
     * Closes a session file-write lock
     */
    public function close()
    {
        if( $this->started === true )
        {
            return session_write_close();
        }
        return true; // nothing to close
    }

    /**
     * Destroy the session
     */
    public function destroy()
    {
        if( $this->started === true )
        {
            $e = time() - 3600;
            $p = session_get_cookie_params();
            $n = session_name();
            $d = session_destroy();

            $_SESSION = array();
            $this->started = false;
            @setcookie( $n, "", $e, $p["path"], $p["domain"], $p["secure"], $p["httponly"] );
            return $d;
        }
        return true;
    }

    /**
     * Flush session data
     */
    public function flush()
    {
        if( $this->started === true )
        {
            $_SESSION = array();
        }
    }

    /**
     * Destroy and restart the session
     */
    public function restart()
    {
        $this->destroy();
        return $this->start();
    }

    /**
     * Checks if a session is active
     */
    public function active()
    {
        if( function_exists( "session_status" ) )
        {
            return ( session_status() === PHP_SESSION_ACTIVE );
        }
        return $this->started;
    }

    /**
     * Set a value for a dot-notated key string
     */
    public function set( $key="", $value=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && $this->started === true )
        {
            $path = trim( $this->container.".".$key, "." );
            $data = &$_SESSION;

            foreach( explode( ".", $path ) as $step )
            {
                if( !isset( $data[ $step ] ) || !is_array( $data[ $step ] ) )
                {
                    $data[ $step ] = array();
                }
                $data = &$data[ $step ];
            }
            $data = $value;
        }
    }

    /**
     * Get a value for a dot-notated key string
     */
    public function get( $key="", $default=null )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && $this->started === true )
        {
            $path = trim( $this->container.".".$key, "." );
            $data = $_SESSION;

            foreach( explode( ".", $path ) as $step )
            {
                if( isset( $data[ $step ] ) && !is_null( $data[ $step ] ) )
                {
                    $data = $data[ $step ];
                    continue;
                }
                return $default;
            }
            return $data;
        }
        return $default;
    }

    /**
     * Delete an entry for a dot-notated key string
     */
    public function delete( $key="" )
    {
        $key = Sanitize::toKey( $key );

        if( !empty( $key ) && $this->started === true )
        {
            $path = trim( $this->container.".".$key, "." );
            $list = explode( ".", $path );
            $last = array_pop( $list );
            $data = &$_SESSION;

            foreach( $list as $step )
            {
                if( !isset( $data[ $step ] ) )
                {
                    return; // gone
                }
                $data = &$data[ $step ];
            }
            if( isset( $data[ $last ] ) )
            {
                // need to reference the last key for unset() to work
                $data[ $last ] = null;
                unset( $data[ $last ] );
            }
        }
    }

    /**
     * Sets a session key if it does not exist
     */
    public function setOnce( $key="", $value=null )
    {
        if( $this->get( $key, null ) === null )
        {
            $this->set( $key, $value );
        }
    }

    /**
     * Checks if a session key is set
     */
    public function has( $key="" )
    {
        return ( $this->get( $key ) !== null );
    }


}