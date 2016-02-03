<?php
/**
 * Helper for building CREATE table quries.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Db;

use Biscuit\Util\Sanitize;

class Table {

    // table properties
    protected $name        = '';
    protected $description = '';
    protected $columns     = array();
    protected $indexes     = array();
    protected $options     = array();
    protected $rows        = array();

    /**
     * Constructor
     */
    public function __construct( $name='', $engine='MyISAM', $charset='utf8', $collation='', $increment=0 )
    {
        $this->setName( $name );
        $this->setEngine( $engine );
        $this->setCharset( $charset );
        $this->setCollation( $collation );
        $this->setAutoIncrement( $increment );
    }

    /**
     * Set the table name
     */
    public function setName( $value='' )
    {
        $this->name = Sanitize::toKey( $value );
        return $this;
    }

    /**
     * Get the table name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the table description
     */
    public function setDescription( $value='' )
    {
        $this->description = Sanitize::toText( $value );
        return $this;
    }

    /**
     * Get the table description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the number of columns for this table
     */
    public function getColumnCount()
    {
        return count( $this->columns );
    }

    /**
     * Get concat of table options (values) as a string
     */
    public function getOptionsText()
    {
        $values = array_values( $this->options );
        return implode( ', ', $values );
    }

    /**
     * Set a table option
     */
    public function setOption( $key='', $value='', $wrap=false )
    {
        $local = Sanitize::toKey( $key );
        $key   = Sanitize::toText( $key );
        $value = Sanitize::toText( $value );

        if( !empty( $key ) && !empty( $value ) )
        {
            if( !empty( $wrap ) )
            {
                $value = "'".addslashes( stripslashes( $value ) )."'";
            }
            $this->options[ $local ] = $key ."=". $value;
        }
        return $this;
    }

    /**
     * Set the table storage engine type
     */
    public function setEngine( $value='' )
    {
        return $this->setOption( 'ENGINE', $value );
    }

    /**
     * Set the table charset name
     */
    public function setCharset( $value='' )
    {
        return $this->setOption( 'DEFAULT CHARACTER SET', $value );
    }

    /**
     * Set the table collation name
     */
    public function setCollation( $value='' )
    {
        return $this->setOption( 'DEFAULT COLLATE', $value );
    }

    /**
     * Set the table storage engine type
     */
    public function setAutoIncrement( $value='' )
    {
        return $this->setOption( 'AUTO_INCREMENT', $value );
    }

    /**
     * Set the table max rows
     */
    public function setMaxRows( $value='' )
    {
        return $this->setOption( 'MAX_ROWS', $value );
    }

    /**
     * Set the table min rows
     */
    public function setMinRows( $value='' )
    {
        return $this->setOption( 'MIN_ROWS', $value );
    }

    /**
     * Set the table comment
     */
    public function setComment( $value='' )
    {
        return $this->setOption( 'COMMENT', $value, true );
    }

    /**
     * Add a column
     */
    public function addColumn( $name='', $type='', $default=null, $auto=false, $null=false )
    {
        $name    = Sanitize::toKey( $name );
        $type    = !empty( $type ) ? Sanitize::toTitle( $type ) : '';
        $default = !is_null( $default ) ? " DEFAULT '".addslashes( stripslashes( trim( $default ) ) )."'" : " DEFAULT ''";
        $auto    = !empty( $auto ) ? " AUTO_INCREMENT" : '';
        $null    = !empty( $null ) ? " NULL": " NOT NULL";

        if( !empty( $name ) && !empty( $type ) )
        {
            if( !empty( $auto ) )
            {
                $default = '';
            }
            $this->columns[ $name ] = "`". $name ."` " . $type . $null . $auto . $default;
        }
        return $this;
    }

    /**
     * Set primary column/s index
     */
    public function addPrimaryKey( $name='', $columns=array() )
    {
        $name    = Sanitize::toKey( $name );
        $columns = array_values( $columns );

        if( !empty( $name ) && !empty( $columns ) )
        {
            $this->indexes[ $name ] = "PRIMARY KEY ".$name." (`". implode( "`,`", $columns ) ."`)";
        }
        return $this;
    }

    /**
     * Set unique column/s index
     */
    public function addUniqueKey( $name='', $columns=array() )
    {
        $name    = Sanitize::toKey( $name );
        $columns = array_values( $columns );

        if( !empty( $name ) && !empty( $columns ) )
        {
            $this->indexes[ $name ] = "UNIQUE KEY ".$name." (`". implode( "`,`", $columns ) ."`)";
        }
        return $this;
    }

    /**
     * Set key column/s index
     */
    public function addIndexKey( $name='', $columns=array() )
    {
        $name    = Sanitize::toKey( $name );
        $columns = array_values( $columns );

        if( !empty( $name ) && !empty( $columns ) )
        {
            $this->indexes[ $name ] = "KEY ".$name." (`". implode( "`,`", $columns ) ."`)";
        }
        return $this;
    }

    /**
     * Set fulltext column/s index
     */
    public function addFulltextKey( $name='', $columns=array() )
    {
        $name    = Sanitize::toKey( $name );
        $columns = array_values( $columns );

        if( !empty( $name ) && !empty( $columns ) )
        {
            $this->indexes[ $name ] = "FULLTEXT ".$name." (`". implode( "`,`", $columns ) ."`)";
        }
        return $this;
    }

    /**
     * Add rows to seed the table after creation
     */
    public function addRow( $row=array() )
    {
        if( !empty( $row ) && is_array( $row ) )
        {
            $this->rows[] = $row;
        }
        return $this;
    }

    /**
     * Get list of rows
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Get CREATED TABLE query
     */
    public function getQuery()
    {
        $query = "";

        if( !empty( $this->name ) )
        {
            $query .= "CREATE TABLE IF NOT EXISTS `". $this->name ."` (";

            if( !empty( $this->columns ) )
            {
                $query .= "\n\n";
                $query .= implode( ", \n", array_values( $this->columns ) );
            }
            if( !empty( $this->indexes ) )
            {
                $query .= ",\n\n";
                $query .= implode( ", \n", array_values( $this->indexes ) );
            }

            $query .= "\n\n";
            $query .= ") ". implode( " ", array_values( $this->options ) ).";";
        }
        return trim( $query );
    }


}