<?php
/**
 * Used to expose and output data for debugging.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Util;

class Expose {

    /**
     * Exposes passed arguments for debugging and exits.
     */
    public static function show()
    {
        $method = Utils::getValue( @$_SERVER['REQUEST_METHOD'], 'GET' );
        $method = Utils::getValue( @$_SERVER['HTTP_X_HTTP_METHOD'], $method );

        if( strtolower( trim( $method ) ) === 'get' )
        {
            self::_headers( 'text/html' );
            self::_html( func_get_args() );
        }
        else
        {
            self::_headers( 'application/json' );
            self::_json( func_get_args() );
        }
        exit;
    }

    /**
     * Send some headers
     */
    protected static function _headers( $type='' )
    {
        if( headers_sent() !== true && !empty( $type ) )
        {
            header( 'Content-Type: '. $type .'; charset='. @mb_internal_encoding(), true, 200 );
        }
    }

    /**
     * Wrap some code into formatted HTML pre tag
     */
    protected static function _code( $value=null )
    {
        if( is_string( $value ) === true )
        {
            $value = htmlspecialchars( trim( $value ) );
        }
        return '' .
        '<pre style="display:block; margin:10px; padding:10px; background-color:#fec;">' . "\r\n" .
            trim( @print_r( $value, true ) ) . "\r\n" .
        '</pre>' . "\r\n" . "\r\n";
    }

    /**
     * Builds HTML data output
     */
    protected static function _html( $data=array() )
    {
        $output = '';

        foreach( $data as $value )
        {
            $output .= self::_code( $value );
        }
        echo '' .
        '<!DOCTYPE HTML>' . "\r\n" .
        '<html lang="en">' . "\r\n" .
            '<head>' . "\r\n" .
                '<title>Debug Output</title>' . "\r\n" .
                '<meta charset="utf-8" />' . "\r\n" .
            '</head>' . "\r\n" .
            '<body>' . "\r\n" .
                $output . "\r\n" .
            '</body>' . "\r\n" .
        '</html>';
    }

    /**
     * Builds JSON data output
     */
    protected static function _json( $data=array() )
    {
        echo json_encode( $data );
    }

}