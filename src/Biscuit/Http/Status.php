<?php
/**
 * Provides a map of status codes to info string.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Http;

use Biscuit\Util\Sanitize;

class Status {

    /**
     * Get string info for a status code
     */
    public static function getString( $code=200 )
    {
        $code = Sanitize::toNumber( $code );
        $list = self::getList();
        return !empty( $list[ $code ] ) ? $list[ $code ] : $list[ 0 ];
    }

    /**
     * Get response status header string
     */
    public static function getHeader( $scheme='HTTP/1.1', $code=200 )
    {
        $scheme = strtoupper( Sanitize::toText( $scheme ) );
        $code   = Sanitize::toNumber( $code );
        $info   = self::getString( $code );
        return $scheme.' '.$code.' '.$info;
    }

    /**
     * Get list of repsonse status codes and strings
     */
    public static function getList()
    {
        return array(

            // Default
            0   => "Invalid Code",

            //Informational 1xx
            100 => "Continue",
            101 => "Switching Protocols",
            102 => "Processing",

            // Successful 2xx
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non Authoritative",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            207 => "Multi Status",

            // Redirection 3xx
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Moved Temporarily",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",

            // Client Error 4xx
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Time Out",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request Uri Too Large",
            415 => "Unsupported Media Type",
            416 => "Range Not Satisfiable",
            417 => "Expectation Failed",
            422 => "Unprocessable Entity",
            423 => "Locked",
            424 => "Failed Dependency",
            426 => "Upgrade Required",

            // Server Error 5xx
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Time Out",
            505 => "Version Not Supported",
            506 => "Variant Also Varies",
            507 => "Insufficient Storage",
            510 => "Not Extended"
        );
    }
}