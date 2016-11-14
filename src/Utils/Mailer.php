<?php
/**
 * Helper class for sending mail with PHPMailer.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

use Biscuit\Data\View;
use Biscuit\Http\Connection;
use Biscuit\Http\Server;
use PHPMailer\PHPMailer;

class Mailer extends PHPMailer {

    /**
     * Setup for using remote SMTP server connection
     */
    public function setupSmtp( $server="", $port=587, $user="", $pass="", $security="", $timeout=10 )
    {
        $this->isSMTP();
        $this->SMTPAuth = false;
        $this->SMTPKeepAlive = true;
        $this->SMTPDebug = 0;
        $this->Host = trim( $server );
        $this->Port = intval( $port );

        if( !empty( $user ) )
        {
            $this->SMTPAuth = true;
            $this->Username = trim( $user );
            $this->Password = trim( $pass );
        }
        if( !empty( $security ) )
        {
            $this->SMTPSecure = trim( $security );
        }
        $this->setTimeout( $timeout );
        return $this;
    }

    /**
     * Sets the SMTP and script timeout
     */
    public function setTimeout( $timeout=10 )
    {
        if( !empty( $timeout ) && is_numeric( $timeout ) )
        {
            $timeout = intval( $timeout );
            set_time_limit( $timeout + 5 );
            $this->Timeout = $timeout;
        }
        return $this;
    }

    /**
     * Sets the message subject
     */
    public function setSubject( $subject )
    {
        $this->Subject = trim( $subject );
    }

    /**
     * Send a plain text message
     */
    public function sendPlain( $body )
    {
        $this->isHTML( false );
        $this->CharSet = @mb_internal_encoding();
        $this->Body = trim( $body );

        $sent = $this->send();
        $this->SmtpClose();
        return $sent;
    }

    /**
     * Send a HTML message
     */
    public function sendHtml( $body )
    {
        $this->isHTML( true );
        $this->CharSet = @mb_internal_encoding();
        $this->MsgHTML( trim( $body ) );

        $sent = $this->send();
        $this->SmtpClose();
        return $sent;
    }

    /**
     * Send a rendered template file HTML message
     */
    public function sendTemplate( $file, $data=[] )
    {
        $view = new View();
        $view->setTemplate( $file );
        $view->setKey( "url", Server::getUrl() );
        $view->setKey( "baseurl", Server::getBaseUrl() );
        $view->setKey( "browser", Connection::getAgent() );
        $view->setKey( "ip", Connection::getIp() );
        $view->setKey( "date", date( "r T" ) );
        $view->mergeData( $data );

        return $this->sendHtml( $view->render() );
    }

    /**
     * Get main send error
     */
    public function getError()
    {
        return $this->ErrorInfo;
    }

}


