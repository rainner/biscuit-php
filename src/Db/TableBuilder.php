<?php
/**
 * MysQl/SQLite query string builder for creating tables.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Db;

use Closure;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class TableBuilder {

    // props
    protected $_name     = "";
    protected $_columns  = [];
    protected $_keys     = [];
    protected $_options  = [];
    protected $_customs  = [];
    protected $_rows     = [];
    protected $_sqlite   = false;

    /**
     * Constructor
     */
    public function __construct( $name="", $sqlite=null )
    {
        $this->setName( $name );
        $this->isSqlite( $sqlite );
    }

    /**
     * Check or toggle sqlite flag
     */
    public function isSqlite( $toggle=null )
    {
        if( is_bool( $toggle ) )
        {
            $this->_sqlite = $toggle;
            return $this;
        }
        return $this->_sqlite;
    }

    /**
     * Set foo value
     */
    public function setName( $name )
    {
        if( !empty( $name ) && is_string( $name ) )
        {
            $this->_name = trim( $name );
        }
        return $this;
    }

    /**
     * Get foo value
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set foo value
     */
    public function setEngine( $engine )
    {
        return $this->setOption( "ENGINE", $engine );
    }

    /**
     * Set foo value
     */
    public function setCharset( $charset )
    {
        return $this->setOption( "CHARSET", $charset );
    }

    /**
     * Set foo value
     */
    public function setCollate( $collate )
    {
        return $this->setOption( "COLLATE", $collate );
    }

    /**
     * Set foo value
     */
    public function setIncrement( $increment )
    {
        return $this->setOption( "AUTO_INCREMENT", $increment );
    }

    /**
     * Set foo value
     */
    public function setComment( $comment )
    {
        return $this->setOption( "COMMENT", $comment );
    }

    /**
     * Set or unset a table option (for MySQL tables)
     */
    public function setOption( $name, $value=null )
    {
        $name = strtoupper( Sanitize::toSingleSpaces( $name ) );
        $key  = strtolower( Sanitize::toKey( $name ) );

        if( !empty( $key ) )
        {
            if( !is_null( $value ) && !$this->_sqlite )
            {
                $this->_options[ $key ] = $name."='".$value."'";
            } else {
                unset( $this->_options[ $key ] );
            }
        }
        return $this;
    }

    /**
     * Get an existing option by key/name
     */
    public function getOption( $name, $default=null )
    {
        $name = strtoupper( Sanitize::toSingleSpaces( $name ) );
        $key  = strtolower( Sanitize::toKey( $name ) );

        if( array_key_exists( $key, $this->_options ) )
        {
            return $this->_options[ $key ];
        }
        return $default;
    }

    /**
     * Add primary column to list
     */
    public function addPrimary( $name, $type, $autoinc=true )
    {
        $name = Sanitize::toKey( $name );
        $type = strtoupper( Utils::value( $type, "INTEGER" ) );
        $type = ( $type === "INT" ) ? "INTEGER" : $type;
        $auto = "";

        if( $autoinc )
        {
            $auto = $this->_sqlite ? " AUTOINCREMENT" : " AUTO_INCREMENT";
        }
        $this->_columns[ $name ] = "`".$name."` ".$type." PRIMARY KEY". $auto;
        return $this;
    }

    /**
     * Add column to list
     */
    public function addColumn( $name, $type, $value=null, $notnull=true )
    {
        $name  = Sanitize::toKey( $name );
        $type  = strtoupper( Utils::value( $type, "CHAR" ) );
        $opts  = "";

        if( $notnull )
        {
            $opts .= " NOT NULL";
        }
        if( is_string( $value ) || is_numeric( $value ) )
        {
            $opts .= " DEFAULT '".$this->_escape( $value )."'";
        }
        else if( is_array( $value ) || is_object( $value ) )
        {
            $opts .= " DEFAULT '".$this->_escape( json_encode( $value ) )."'";
        }
        $this->_columns[ $name ] = "`".$name."` ".$type . $opts;
        return $this;
    }

    /**
     * Get list of added columns
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * Set table unique index on colunms (args)
     */
    public function uniqueIndex()
    {
        $key = Sanitize::toKey( $this->_name ."_unique" );
        $this->_keys[ $key ] = "CREATE UNIQUE INDEX `".$key."` ON `".$this->_name."` (`".implode( "`,`", func_get_args() )."`)";
        return $this;
    }

    /**
     * Set table text index on colunms (args)
     */
    public function keyIndex()
    {
        $key = Sanitize::toKey( $this->_name ."_index" );
        $this->_keys[ $key ] = "CREATE INDEX `".$key."` ON `".$this->_name."` (`".implode( "`,`", func_get_args() )."`)";
        return $this;
    }

    /**
     * Set table fulltext index on colunms (args)
     */
    public function searchIndex()
    {
        $ft  = $this->_sqlite ? "" : " FULLTEXT";
        $key = Sanitize::toKey( $this->_name ."_search" );
        $this->_keys[ $key ] = "CREATE".$ft." INDEX `".$key."` ON `".$this->_name."` (`".implode( "`,`", func_get_args() )."`)";
        return $this;
    }

    /**
     * Get list of added index keys
     */
    public function getKeys()
    {
        return $this->_keys;
    }

    /**
     * Add a custom query string to the final build
     */
    public function addCustom( $data=null )
    {
        if( !empty( $data ) )
        {
            if( is_string( $data ) )
            {
                $this->_customs[] = rtrim( trim( $data ), ";" );
            }
            else if( $data instanceof Closure )
            {
                $data  = $data->bindTo( $this );
                $value = call_user_func( $data );
                call_user_func( [ $this, "addCustom" ], $value );
            }
        }
        return $this;
    }

    /**
     * Add new row to the list
     */
    public function addRow( $row=[] )
    {
        if( !empty( $row ) && is_array( $row ) )
        {
            $this->_rows[] = $row;
        }
        return $this;
    }

    /**
     * Get list of added rows
     */
    public function getRows()
    {
        return $this->_rows;
    }

    /**
     * Get list of queries to be executed
     */
    public function getQueries()
    {
        $output = [];

        // add table
        $create   = "CREATE TABLE IF NOT EXISTS `".$this->_name."` ( ";
        $create  .= implode( ", ", $this->_columns );
        $create  .= " ) ". implode( " ", $this->_options );
        $output[] = $create;

        // add indexes
        foreach( $this->_keys as $index )
        {
            $output[] = $index;
        }
        // add custom queries
        foreach( $this->_customs as $custom )
        {
            $output[] = $custom;
        }
        // add rows
        foreach( $this->_rows as $row )
        {
            $columns = array_keys( $row );
            $values  = array_values( $row );

            foreach( $values as $i => $value )
            {
                $values[ $i ] = $this->_escape( $value );
            }
            $output[] = "INSERT INTO `".$this->_name."` (`".implode( "`,`", $columns )."`) VALUES ('".implode( "','", $values )."')";
        }
        return $output;
    }

    /**
     * Escape single quotes for MySQL/SQLite using two single quotes
     */
    private function _escape( $value )
    {
        $value = preg_replace( "/(\\+)?(\'+)/", "'", $value );
        $value = str_replace( "'", "''", $value );
        return $value;
    }


}
