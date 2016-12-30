<?php
/**
 * Import class for processing a file and extracting values to be inserted to the db
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Data;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Import {

    // props
    protected $_file   = "";
    protected $_format = "";
    protected $_keymap = [];

    /**
     * Constructor
     */
    public function __construct( $file="", $format="" )
    {
        $this->setFile( $file );
        $this->setFormat( $format );
    }

    /**
     * Set file to be read
     */
    public function setFile( $file )
    {
        if( is_string( $file ) )
        {
            $this->_file = Sanitize::toPath( $file );
        }
    }

    /**
     * Set format (method) used to serialize the file data
     */
    public function setFormat( $format )
    {
        if( is_string( $format ) )
        {
            $this->_format = Sanitize::toKey( $format );
        }
    }

    /**
     * Set the import keymap (dbColumn => fileParam)
     */
    public function setKeymap( $map=[] )
    {
        if( is_array( $map ) )
        {
            foreach( $map as $key => $value )
            {
                $this->_keymap[ $key ] = $value;
            }
        }
    }

    /**
     * Parse file data
     */
    public function parse()
    {
        $output = [];

        if( $data = @file_get_contents( $this->_file ) )
        {
            switch( $this->_format )
            {
                case "print_r"     : $output = $this->_parsePrint( $data );  break;
                case "var_dump"    : $output = $this->_parseDump( $data );   break;
                case "json_encode" : $output = $this->_parseJson( $data );   break;
                case "serialize"   : $output = $this->_parseSerial( $data ); break;
            }
        }
        return $output;
    }

    /**
     * Parse file data as PHP print_r() data dump
     */
    private function _parsePrint( $data="" )
    {
        $output = [];

        foreach( $this->_keymap as $key => $search )
        {
            @preg_match( "/(\[".$search."\])([\s\=\>]+)(.*)/u", $data, $matches );
            $output[ $key ] = trim( Utils::value( @$matches[ 3 ], "" ) );
        }
        return $output;
    }

    /**
     * Parse file data as PHP var_dump() data dump
     */
    private function _parseDump( $data="" )
    {
        $output = [];
        $data   = preg_replace( "/(array|object|string)\(\w*\)(\#\d*\s*)?(\(\d*\))?\s*{?/ui", "", $data );
        $data   = preg_replace( "/(int|bool)\((\w+)\)/ui", "$2", $data );
        $data   = preg_replace( "/\s*=>[\r\n]+/ui", " => ", $data );
        $data   = preg_replace( "/[ ]{2,}/ui", " ", $data );
        $data   = str_replace( ['"', "{", "}"], "", $data );

        foreach( $this->_keymap as $key => $search )
        {
            @preg_match( "/(\[".$search."\])([\s\=\>]+)(.*)/u", $data, $matches );
            $output[ $key ] = trim( Utils::value( @$matches[ 3 ], "" ) );
        }
        return $output;
    }

    /**
     * Parse file data as PHP json_encode() data dump
     */
    private function _parseJson( $data="" )
    {
        $output = [];

        if( $data = @json_decode( $data, true ) )
        {
            foreach( $this->_keymap as $key => $search )
            {
                if( array_key_exists( $search, $data ) )
                {
                    $output[ $key ] = trim( $data[ $search ] );
                }
            }
        }
        return $output;
    }

    /**
     * Parse file data as PHP serialize() data dump
     */
    private function _parseSerial( $data="" )
    {
        $output = [];

        if( $data = @unserialize( $data ) )
        {
            foreach( $this->_keymap as $key => $search )
            {
                if( array_key_exists( $search, $data ) )
                {
                    $output[ $key ] = trim( $data[ $search ] );
                }
            }
        }
        return $output;
    }

}