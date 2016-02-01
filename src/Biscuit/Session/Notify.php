<?php
/**
 * Handles message flashing and notifications.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Session;

use Biscuit\Util\Sanitize;
use Exception;

class Notify {

    // Session object
    protected $session = null;

    // session key for storing flash messahes
    protected $fkey = '_flashes_';

    // session key for storing notification messahes
    protected $nkey = '_notices_';

    /**
     * Constructor
     */
    public function __construct( Session $session )
    {
        $this->session = $session;

        if( $this->session->active() !== true )
        {
            throw new Exception( 'Session needs to be started before using the Flash class.' );
        }
        if( $this->session->has( $this->fkey ) !== true )
        {
            $this->session->set( $this->fkey, array() );
        }
        if( $this->session->has( $this->nkey ) !== true )
        {
            $this->session->set( $this->nkey, array() );
        }
    }

    /**
     * Add a new flash message to the list for $key
     */
    public function setFlash( $key='', $class='', $message='' )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $class   = Sanitize::toText( $class );
            $message = Sanitize::toText( $message );

            $key     = $this->fkey.'.'.$key;
            $list    = $this->session->get( $key, array() );
            $list[]  = array( 'class'=> $class, 'message'=> $message, 'addtime'=> time() );
            $this->session->set( $key, $list );
        }
    }

    /**
     * Get list of all added flash messages for a $key
     */
    public function getFlashList( $key='' )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $key  = $this->fkey.'.'.$key;
            $data = $this->session->get( $key, array() );
            $this->session->delete( $key );
            return $data;
        }
        return array();
    }

    /**
     * Adds a new notification to the list
     */
    public function setNotice( $key='', $notice='', $link='#' )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $this->session->set( $this->nkey.'.'.$key, array(

                'active' => true,
                'notice' => trim( $notice ),
                'link'   => trim( $link )
            ));
        }
    }

    /**
     * Get data for a notification by key
     */
    public function getNotice( $key='', $default=array() )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            return $this->session->get( $this->nkey.'.'.$key, $default );
        }
        return $default;
    }

    /**
     * Changes the active state of a notification
     */
    public function toggleNotice( $key='', $active=true )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $this->session->set( $this->nkey.'.'.$key.'.active', $active );
        }
    }

    /**
     * Delete a notification by key
     */
    public function deleteNotice( $key='' )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $this->session->delete( $this->nkey.'.'.$key );
        }
    }

    /**
     * Flush all notifications
     */
    public function flushNotices()
    {
        $this->session->set( $this->nkey, array() );
    }

    /**
     * Get all notifications
     */
    public function getNoticeList( $active=true )
    {
        $list   = $this->session->get( $this->nkey, array() );
        $output = array();

        foreach( $list as $key => $data )
        {
            if( $active === true && $data['active'] !== true )
            {
                continue;
            }
            $output[ $key ] = $data;
        }
        return $output;
    }

    /**
     * Get total number of notifications
     */
    public function getNoticeCount( $active=true )
    {
        $list   = $this->session->get( $this->nkey, array() );
        $output = 0;

        foreach( $list as $key => $data )
        {
            if( $active === true && $data['active'] !== true )
            {
                continue;
            }
            $output++;
        }
        return number_format( $output );
    }

}

