<?php
/**
 * Helper class for sending mail with PHPMailer.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Mail;

use Biscuit\Mvc\View;
use Biscuit\Http\Client;
use Biscuit\Http\Server;

class Mailer extends PHPMailer {

    /**
     * Setup for using remote SMTP server connection
     */
    public function setupSmtp( $server='', $port=587, $user='', $pass='', $security='', $timeout=10 )
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
    public function setSubject( $subject='' )
    {
        $this->Subject = trim( $subject );
    }

    /**
     * Send a plain text message
     */
    public function sendPlain( $body='' )
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
    public function sendHtml( $body='' )
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
    public function sendTemplate( $file='', $data=array() )
    {
        if( !empty( $file ) && is_file( $file ) )
        {
            $tpl_base = dirname( $file );
            $tpl_file = '/'.basename( $file );

            $view = new View();
            $view->setPlublicPath( $tpl_base );
            $view->addRenderPath( $tpl_base );
            $view->setTemplate( $tpl_file );
            $view->set( 'url', Server::getUrl() );
            $view->set( 'ip', Client::getIp() );
            $view->set( 'browser', Client::getAgent() );
            $view->set( 'date', date( 'l jS \of F Y h:i A T' ) );

            return $this->sendHtml( $view->render() );
        }
        return false;
    }

    /**
     * Get main send error
     */
    public function getError()
    {
        return $this->ErrorInfo;
    }

}


