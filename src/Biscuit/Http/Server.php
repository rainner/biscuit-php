<?php
/**
 * Provides information about the PHP server and connection.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Http;

use Biscuit\Util\Sanitize;
use Biscuit\Util\Validate;
use Biscuit\Util\Utils;

class Server {

    /**
     * Check the the server is using an HTTPS connection
     */
    public static function isSecure()
    {
        $https = Utils::getValue( @$_SERVER['HTTPS'], '', true );
        $https = strtolower( $https );

        $forward = Utils::getValue( @$_SERVER['HTTP_X_FORWARDED_SSL'], '', true );
        $forward = Utils::getValue( @$_SERVER['HTTP_X_FORWARDED_PROTO'], $forward, true );
        $forward = strtolower( $forward );

        $port = Utils::getValue( @$_SERVER['SERVER_PORT'], '', true );
        $port = strtolower( $port );

        if( !empty( $https ) && $https !== 'off' ) return true;
        if( !empty( $forward ) && $forward === 'https' ) return true;
        if( !empty( $port ) && $port === '443' ) return true;
        return false;
    }

    /**
     * Get the OS platform the server is running on
     */
    public static function getPlatform()
    {
        return ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? 'Windows' : 'Unix';
    }

    /**
     * Get the PHP uname signature string from OS
     */
    public static function getSignature()
    {
        return Sanitize::toTitle( php_uname( 'a' ) );
    }

    /**
     * Get the operating system nane
     */
    public static function getOS()
    {
        return Sanitize::toTitle( php_uname( 's' ) );
    }

    /**
     * Get name of the user serving the PHP scripts to the client
     */
    public static function getUser()
    {
        return Sanitize::toTitle( str_replace( '\\', '/', @exec( 'whoami' ) ) );
    }

    /**
     * Get the installed server version (cleaned)
     */
    public static function getVersion()
    {
        $phpv    = preg_replace( '/[\r\n\t\s\ ]+/i', '', trim( PHP_VERSION ) );
        $version = preg_replace( '/^([\d\-\.]+).*$/i', '$1', $phpv );
        return !empty( $version ) ? $version : $phpv;
    }

    /**
     * Get the server user-agent name
     */
    public static function getName()
    {
        $value = Utils::getValue( @$_SERVER['SERVER_SOFTWARE'], '', true );
        $value = Sanitize::toTitle( $value );
        return $value;
    }

    /**
     * Get the hostname
     */
    public static function getHost()
    {
        $value = Utils::getValue( @$_SERVER['SERVER_NAME'], '', true );
        $value = Sanitize::toKey( $value );
        return $value;
    }

    /**
     * Get the port number
     */
    public static function getPort()
    {
        $value = Utils::getValue( @$_SERVER['SERVER_PORT'], 0 );
        $value = Sanitize::toNumber( $value );
        return $value;
    }

    /**
     * Get the HTTP protocol version
     */
    public static function getProtocol()
    {
        $value = Utils::getValue( @$_SERVER['SERVER_PROTOCOL'], '', true );
        $value = Sanitize::toTitle( $value );
        return $value;
    }

    /**
     * Get the server script handling the request
     */
    public static function getScriptFile()
    {
        $value = Utils::getValue( @$_SERVER['SCRIPT_FILENAME'], '', true );
        $value = str_replace( '\\', '/', $value );
        return $value;
    }

    /**
     * Get path of the server script handling the request
     */
    public static function getScriptPath()
    {
        return dirname( self::getScriptFile() );
    }

    /**
     * Get the server document root path
     */
    public static function getDocRoot()
    {
        $value = Utils::getValue( @$_SERVER['DOCUMENT_ROOT'], '', true );
        $value = rtrim( str_replace( '\\', '/', $value ), '/' );
        return $value;
    }

    /**
     * Get the path where the script is being served from
     */
    public static function getBasePath()
    {
        $root   = self::getDocRoot();
        $script = self::getScriptPath();
        return rtrim( str_replace( $root, '', $script ), '/' );
    }

    /**
     * Get the base URL where the script is being served from
     */
    public static function getBaseUrl( $append='/' )
    {
        $scheme   = self::isSecure() ? 'https' : 'http';
        $hostname = self::getHost();
        $port     = self::getPort();
        $path     = self::getBasePath();
        $address  = $scheme .'://'. $hostname;

        if( !empty( $port ) && $port != 80 )
        {
            $address .= ':'. $port;
        }
        return $address . $path . $append;
    }

    /**
     * Get current request URL as is
     */
    public static function getUrl()
    {
        $path = str_replace( self::getBasePath(), '', trim( $_SERVER['REQUEST_URI'] ) );
        return self::getBaseUrl( $path );
    }

    /**
     * Get the domain name from current hostname
     */
    public static function getDomain( $tld=true )
    {
        $hostname = self::getHost();
        $domain   = preg_replace( "/^(.*\.)?([^.]*\..*)$/i", "$2", $hostname );

        if( Validate::isIp( $hostname ) || Validate::isIpv6( $hostname ) )
        {
            return $hostname;
        }
        if( $tld !== true )
        {
            return preg_replace( "/\.[a-z]+$/i", "", $domain );
        }
        return $domain;
    }

    /**
     * Get combined values from this class, or single value for $key
     */
    public static function getInfo( $key='' )
    {
        $output = array(
            'ssl'        => self::isSecure(),
            'platform'   => self::getPlatform(),
            'signature'  => self::getSignature(),
            'os'         => self::getOS(),
            'version'    => self::getVersion(),
            'user'       => self::getUser(),
            'host'       => self::getHost(),
            'domain'     => self::getDomain(),
            'port'       => self::getPort(),
            'path'       => self::getBasePath(),
            'baseurl'    => self::getBaseUrl(),
            'url'        => self::getUrl(),
            'docroot'    => self::getDocRoot(),
            'script'     => self::getScriptFile(),
            'encoding'   => mb_internal_encoding(),
            'extensions' => get_loaded_extensions(),
        );
        if( !empty( $key ) && array_key_exists( $key, $output ) )
        {
            return $output[ $key ];
        }
        return $output;
    }

}