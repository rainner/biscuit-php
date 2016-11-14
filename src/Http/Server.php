<?php
/**
 * Provides information about the server and connection.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Http;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Validate;
use Biscuit\Utils\Numeric;
use Biscuit\Utils\Utils;

class Server {

    /**
     * Check the the server is using an HTTPS connection
     */
    public static function isSecure()
    {
        return Connection::isSecure();
    }

    /**
     * Get the calculated script load time based on a starting value
     */
    public static function getLoadTime( $start=0, $suffix="secs." )
    {
        $suffix = " ". Utils::value( $suffix, "" );

        if( is_numeric( $start ) )
        {
            return number_format( microtime( true ) - $start, 6, ".", "" ) ." ". $suffix;
        }
        return "0.00 ". $suffix;
    }

    /**
     * Get PHP allocated memory usage
     */
    public static function getMemUsage()
    {
        return Numeric::toBytes( memory_get_peak_usage() );
    }

    /**
     * Get the OS platform the server is running on
     */
    public static function getPlatform()
    {
        return ( strtoupper( substr( PHP_OS, 0, 3 ) ) === "WIN" ) ? "Windows" : "Unix";
    }

    /**
     * Get the PHP uname signature string from OS
     */
    public static function getSignature()
    {
        return Sanitize::toTitle( php_uname( "a" ) );
    }

    /**
     * Get the operating system nane
     */
    public static function getOS()
    {
        return Sanitize::toTitle( php_uname( "s" ) );
    }

    /**
     * Get name of the user serving the PHP scripts to the client
     */
    public static function getUser()
    {
        return Sanitize::toTitle( str_replace( "\\", "/", @exec( "whoami" ) ) );
    }

    /**
     * Get the installed server version (cleaned)
     */
    public static function getVersion()
    {
        $phpv    = preg_replace( "/[\r\n\t\s\ ]+/i", "", trim( PHP_VERSION ) );
        $version = preg_replace( "/^([\d\-\.]+).*$/i", "$1", $phpv );
        return !empty( $version ) ? $version : $phpv;
    }

    /**
     * Get the server user-agent name
     */
    public static function getName()
    {
        $value = Utils::value( @$_SERVER["SERVER_SOFTWARE"], "" );
        $value = Sanitize::toTitle( $value );
        return $value;
    }

    /**
     * Get the hostname
     */
    public static function getHost()
    {
        $value = Utils::value( @$_SERVER["SERVER_NAME"], "" );
        $value = Sanitize::toKey( $value );
        return $value;
    }

    /**
     * Get the port number
     */
    public static function getPort()
    {
        $value = Utils::value( @$_SERVER["SERVER_PORT"], 0 );
        $value = Sanitize::toNumber( $value );
        return $value;
    }

    /**
     * Get the HTTP protocol version
     */
    public static function getProtocol()
    {
        $value = Utils::value( @$_SERVER["SERVER_PROTOCOL"], "" );
        $value = Sanitize::toTitle( $value );
        return $value;
    }

    /**
     * Get the server document root path
     */
    public static function getDocRoot()
    {
        $value = Utils::value( @$_SERVER["DOCUMENT_ROOT"], "" );
        $value = Sanitize::toPath( $value );
        return $value;
    }

    /**
     * Get the server script handling the request
     */
    public static function getScriptFile()
    {
        $value = Utils::value( @$_SERVER["SCRIPT_FILENAME"], "" );
        $value = Sanitize::toPath( $value );
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
     * Get public web url for the script serving the request
     */
    public static function getScriptUrl( $append=null )
    {
        $script = basename( self::getScriptFile() );
        $script = preg_replace( "/index\.[\w]+/i", "", $script );
        $append = self::_append( $append );
        return self::getBaseUrl( "/". $script . $append );
    }

    /**
     * Get the public path where the script is being served from
     */
    public static function getBasePath()
    {
        if( !empty( $_SERVER["CONTEXT_PREFIX"] ) )
        {
            return Sanitize::toPath( $_SERVER["CONTEXT_PREFIX"] );
        }
        if( !empty( $_SERVER["SCRIPT_NAME"] ) )
        {
            return Sanitize::toPath( dirname( $_SERVER["SCRIPT_NAME"] ) );
        }
        return "";
    }

    /**
     * Get the base URL where the script is being served from
     */
    public static function getBaseUrl( $append=null )
    {
        $scheme   = self::isSecure() ? "https" : "http";
        $hostname = self::getHost();
        $port     = self::getPort();
        $path     = self::getBasePath();
        $append   = self::_append( $append );
        $address  = $scheme ."://". $hostname;

        if( !empty( $port ) && $port != 80 )
        {
            $address .= ":". $port;
        }
        return $address . $path . $append;
    }

    /**
     * Get current script/route url
     */
    public static function getRouteUrl( $append=null )
    {
        $path   = str_replace( self::getBasePath(), "", Connection::getPath() );
        $append = self::_append( $append );
        return self::getScriptUrl( $path . $append );
    }

    /**
     * Get public web url for a local file if it exists
     */
    public static function getFileUrl( $file="" )
    {
        $root = self::getScriptPath();
        $file = Sanitize::toPath( trim( $file, ". " ) );
        $path = str_replace( $root, "", $file );
        return self::getBaseUrl( $path );
    }

    /**
     * Get current request URL as is
     */
    public static function getUrl()
    {
        $path = str_replace( self::getBasePath(), "", trim( $_SERVER["REQUEST_URI"] ) );
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
    public static function getInfo( $key="" )
    {
        $output = array(
            "ssl"        => self::isSecure(),
            "platform"   => self::getPlatform(),
            "signature"  => self::getSignature(),
            "os"         => self::getOS(),
            "version"    => self::getVersion(),
            "user"       => self::getUser(),
            "host"       => self::getHost(),
            "domain"     => self::getDomain(),
            "port"       => self::getPort(),
            "path"       => self::getBasePath(),
            "baseurl"    => self::getBaseUrl(),
            "url"        => self::getUrl(),
            "docroot"    => self::getDocRoot(),
            "script"     => self::getScriptFile(),
            "encoding"   => mb_internal_encoding(),
            "extensions" => get_loaded_extensions(),
        );
        if( !empty( $key ) && array_key_exists( $key, $output ) )
        {
            return $output[ $key ];
        }
        return $output;
    }

    /**
     * Resolve something to be appended to a URL
     */
    private static function _append( $append=null )
    {
        if( !empty( $append ) )
        {
            if( is_array( $append ) )
            {
                $append = "/?".http_build_query( array_merge( $_GET, $append ), "param" );
            }
            else if( is_string( $append ) )
            {
                $append = trim( $append );
            }
            $append = preg_replace( "/^\/\/+/", "/", $append );
        }
        return $append;
    }

}