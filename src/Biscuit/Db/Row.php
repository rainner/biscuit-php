<?php
/**
 * Helper methods for filtering row values.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Db;

use Biscuit\Data\Json;
use Biscuit\Http\Server;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Text;
use Biscuit\Util\Numeric;
use Biscuit\Util\Utils;

abstract class Row {

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Revolves timestamps to dates from selected row
     */
    public function resolveDate( $row=array(), $column='', $format='F jS, Y', $default='Never' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $timestamp = intval( $row[ $column ] );
            $row[ $column ] = !empty( $timestamp ) ? date( $format, $timestamp ) : $default;
        }
        return $row;
    }

    /**
     * Resolves an file path for a row
     */
    public function resolveFile( $row=array(), $column='', $default='' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $value = trim( $row[ $column ] );

            if( !empty( $value ) && Validate::isUrl( $row[ $column ] ) !== true )
            {
                $row[ $column ] = Server::getBaseUrl( $row[ $column ] );
            }
        }
        return $row;
    }

    /**
     * Resolves a number column to include a noun word
     */
    public function resolveNoun( $row=array(), $column='', $single='', $plutal='' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $row[ $column ] = Numeric::toNoun( $row[ $column ], $single, $plutal );
        }
        return $row;
    }

    /**
     * Resolves a file size into human-readable format
     */
    public function resolveSize( $row=array(), $column='' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $row[ $column ] = Numeric::toSize( $row[ $column ] );
        }
        return $row;
    }

    /**
     * Decodes JSON data for a column
     */
    public function decodeJson( $row=array(), $column='', $default=array() )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $row[ $column ] = Json::decode( $row[ $column ], $default );
        }
        return $row;
    }

    /**
     * Decodes string data for a column
     */
    public function decodeType( $row=array(), $column='' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $row[ $column ] = Sanitize::toType( $row[ $column ] );
        }
        return $row;
    }

    /**
     * Reduces the size of a text entry for a specified char limit
     */
    public function shrinkText( $row=array(), $column='', $length=140 )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $row[ $column ] = (string) Text::set( $row[ $column ] )->shrink( $length );
        }
        return $row;
    }

    /**
     * Sets a placeholder text for a column, if it is empty
     */
    public function defaultText( $row=array(), $column='', $default='No value specified.' )
    {
        if( !empty( $column ) && array_key_exists( $column, $row ) )
        {
            $value = trim( $row[ $column ] );
            $row[ $column ] = !empty( $value ) ? $value : $default;
        }
        return $row;
    }

}