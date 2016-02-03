<?php
/**
 * Handles registering and firing of events.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Boot;

use Closure;

abstract class Events {

    // event listeners
    protected $listeners = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Check if there are listeners for an event name
     */
    public function hasEvent( $event='' )
    {
        if( !empty( $event ) && !empty( $this->listeners[ $event ] ) )
        {
            return true;
        }
        return false;
    }

    /**
     * Register an event listener callback
     */
    public function onEvent( $event='', $callback=null )
    {
        if( !empty( $event ) && $callback instanceof Closure )
        {
            $this->listeners[ $event ][] = $callback->bindTo( $this );
        }
    }

    /**
     * Trigger all listeners of an event name to filter a given content value
     */
    public function filterEvent( $event='', $content=null )
    {
        if( $this->hasEvent( $event ) )
        {
            foreach( $this->listeners[ $event ] as $callback )
            {
                $content = call_user_func( $callback, $content );
            }
        }
        return $content;
    }

    /**
     * Trigger all listeners of an event name and pass arguments
     */
    public function triggerEvent()
    {
        $args   = func_get_args();
        $output = null;

        if( count( $args ) )
        {
            $event = array_shift( $args );

            if( $this->hasEvent( $event ) )
            {
                foreach( $this->listeners[ $event ] as $callback )
                {
                    $output = call_user_func_array( $callback, $args );
                }
            }
        }
        return $output;
    }

    /**
     * Trigger all listeners of an event name by matching a given value
     */
    public function matchEvent()
    {
        $args   = func_get_args();
        $output = null;

        if( count( $args ) )
        {
            $value = trim( array_shift( $args ) );

            foreach( $this->listeners as $pattern => $callbacks )
            {
                @preg_match_all( "!^".$pattern."$!", $value, $matches, PREG_SET_ORDER );

                if( !empty( $matches[0] ) && is_array( $matches[0] ) )
                {
                    array_shift( $matches[0] );
                    $matches = array_values( $matches[0] );
                    $args    = array_values( array_merge( $args, $matches ) );

                    foreach( $callbacks as $cb )
                    {
                        $output = call_user_func_array( $cb, $args );
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Get a list of all registered event listeners
     */
    public function availableEvents()
    {
        $events = array();

        foreach( $this->listeners as $event => $list )
        {
            $events[ $event ] = array(

                'event' => $event,
                'count' => count( $list )
            );
        }
        return $events;
    }

    /**
     * Flush the list of event listeners
     */
    public function flushEvents()
    {
        $this->listeners = array();
    }

}

