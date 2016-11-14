<?php
/**
 * Handles registering authenticated users in session.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Session;

use Exception;
use Biscuit\Http\Connection;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Numeric;
use Biscuit\Utils\Utils;

class Login {

    // Session object
    protected $session = null;

    // key used to group login related data in session
    protected $key = "_login_";

    // unique client identifier
    protected $unique = "";

    // max idle time before auto logoff
    protected $idle_time = 3600;

    // max number of allowed failed login attempts
    protected $max_attempts = 20;

    // number of failed login attemps that trigger a cooldown
    protected $cd_trigger = 5;

    // how long the cooldown period will last after triggered
    protected $cd_time = 300;

    /**
     * Constructor
     */
    public function __construct( Session $session )
    {
        $this->session = $session;
        $this->unique  = Connection::getHash();

        if( $this->session->active() !== true )
        {
            throw new Exception( "Session needs to be started before using the ".__CLASS__." class." );
        }
        $this->session->setOnce( $this->key.".info", array(
            "fail_count"   => 0,  // current number of failed attempts
            "fail_total"   => 0,  // combined number of failed attempts
            "last_attempt" => 0,  // timestamp of last fail attempt
            "last_active"  => 0,  // timestamp of last acivity
            "login_time"   => 0,  // time of last succesful login
            "login_hash"   => "", // unique client hash
        ));
    }

    /**
     * Reset session login info/stats
     */
    public function reset()
    {
        $this->session->updateId();
        $this->session->set( $this->key.".info.fail_count",   0 );
        $this->session->set( $this->key.".info.fail_total",   0 );
        $this->session->set( $this->key.".info.last_attempt", 0 );
        $this->session->set( $this->key.".info.last_active",  0 );
        $this->session->set( $this->key.".info.login_time",   0 );
        $this->session->set( $this->key.".info.login_hash",   "" );
    }

    /**
     * Set the max idle time after logging in
     */
    public function setIdleTime( $value=3600 )
    {
        if( is_string( $value ) )
        {
            $value = ( intval( strtotime( trim( $value ) ) ) - time() ) * 1;
        }
        if( is_int( $value ) )
        {
            $this->idle_time = $value;
        }
    }

    /**
     * Set the max number of allowed failed login attempts
     */
    public function setMaxAttempts( $value=20 )
    {
        if( is_int( $value ) )
        {
            $this->max_attempts = $value;
        }
    }

    /**
     * Sets the number of failed login attemps that trigger a cooldown
     */
    public function setCooldownTrigger( $value=5 )
    {
        if( is_int( $value ) )
        {
            $this->cd_trigger = $value;
        }
    }

    /**
     * Sets how long the cooldown period will last after triggered
     */
    public function setCooldownTime( $value=300 )
    {
        if( is_int( $value ) )
        {
            $this->cd_time = $value;
        }
    }

    /**
     * Used to increment the failed attempt counter
     */
    public function attemptFailed( $sleep=0.3 )
    {
        if( $this->cd_trigger > 0 )
        {
            $count = $this->session->get( $this->key.".info.fail_count", 0 ) + 1;
            $total = $this->session->get( $this->key.".info.fail_total", 0 ) + 1;

            $this->session->set( $this->key.".info.fail_count", $count );
            $this->session->set( $this->key.".info.fail_total", $total );
            $this->session->set( $this->key.".info.last_attempt", time() );
        }
        if( is_numeric( $sleep )  )
        {
            // optional delay to help slow down brute-force attacks
            time_sleep_until( microtime( true ) + abs( floatval( $sleep ) ) );
        }
    }

    /**
     * Check if the total allowed failed login attempts has been reached
     */
    public function hasMaxAttempts()
    {
        $total = $this->session->get( $this->key.".info.fail_total", 0 );

        if( $this->max_attempts > 0 && $this->max_attempts <= $total )
        {
            return true; // locked period
        }
        return false;
    }

    /**
     * Check if there is an active cooldown period
     */
    public function hasCooldownTime()
    {
        $count = $this->session->get( $this->key.".info.fail_count", 0 );
        $last  = $this->session->get( $this->key.".info.last_attempt", 0 );
        $past  = time() - $last;

        if( $this->cd_trigger > 0 && $this->cd_trigger <= $count )
        {
            if( $last > 0 && $this->cd_time > $past )
            {
                return true; // cooldown period
            }
            $this->session->set( $this->key.".info.fail_count", 0 );
            $this->session->set( $this->key.".info.last_attempt", 0 );
        }
        return false;
    }

    /**
     * Get the formatted error text
     */
    public function getErrorText( $text="" )
    {
        $text    = Utils::value( $text, "Too many failed attempts." );
        $count   = $this->session->get( $this->key.".info.fail_count", 0 );
        $total   = $this->session->get( $this->key.".info.fail_total", 0 );
        $last    = $this->session->get( $this->key.".info.last_attempt", 0 );

        $keymap  = array(
            "attempts"  => $total,
            "countdown" => Numeric::toCountdown( $last, $this->cd_time ),
            "count"     => "(".$count."/".$this->cd_trigger.")",
            "total"     => "(".$total."/".$this->max_attempts.")",
        );
        return Utils::render( $text, $keymap );
    }

    /**
     * Checks if the user is logged in
     */
    public function loggedIn()
    {
        $user = $this->session->get( $this->key.".user", [] );
        $hash = $this->session->get( $this->key.".info.login_hash", "" );
        $last = $this->session->get( $this->key.".info.last_active", 0 );
        $past = time() - $last;

        // previous login detected...
        if( !empty( $hash ) )
        {
            // unique client hash has changed, why?
            if( $hash !== $this->unique )
            {
                $this->logoff();
                return false;
            }
            // user has been away for too long
            if( $last > 0 && $past > $this->idle_time )
            {
                $this->logoff();
                return false;
            }
            // resolve timezone
            if( !empty( $user["timezone"] ) )
            {
                @ini_set( "date.timezone", $user["timezone"] );
            }
            // resolve charset
            if( !empty( $user["charset"] ) )
            {
                @ini_set( "default_charset", $user["charset"] );
            }
            // update activity timestamp
            $this->session->set( $this->key.".info.last_active", time() );
            return true;
        }
        return false;
    }

    /**
     * Adds authenticated user data to the session
     */
    public function login( $data=[] )
    {
        $this->reset();
        $this->session->set( $this->key.".info.last_active", time() );
        $this->session->set( $this->key.".info.login_time", time() );
        $this->session->set( $this->key.".info.login_hash", $this->unique );

        if( !empty( $data ) && is_array( $data ) )
        {
            if( empty( $data["image"] ) && !empty( $data["email"] ) )
            {
                $data["image"] = Utils::gravatar( $data["email"] );
            }
            if( !empty( $data["options"] ) && is_string( $data["options"] ) )
            {
                $data["options"] = @json_decode( $data["options"], true );
            }
            $this->session->set( $this->key.".user", $data );
        }
        return true;
    }

    /**
     * Delete login data from session
     */
    public function logoff()
    {
        $this->reset();
        $this->session->delete( $this->key.".user" );
        return true;
    }

    /**
     * Checks if logged in user is the owner of an entry
     */
    public function isOwner( $entry=[], $column="admin_id" )
    {
        if( $this->loggedIn() && is_array( $entry ) )
        {
            $id = $this->session->get( $this->key.".user.id", 0 );

            if( !empty( $entry[ $column ] ) && $entry[ $column ] == $id )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the data for logged in user
     */
    public function getUserData( $key="" )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            return $this->session->get( $this->key.".user.".$key, null );
        }
        return $this->session->get( $this->key.".user", [] );
    }

}
