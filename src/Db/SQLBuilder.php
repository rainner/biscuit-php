<?php
/**
 * MySQL query string builder for CRUD operations.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Db;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class SQLBuilder {

    // props
    protected $_explain = false;    // EXPLAIN
    protected $_type    = "";       // SELECT, INSERT, etc.
    protected $_tables  = [];       // table list
    protected $_columns = [];       // table columns
    protected $_joins   = [];       // left joins
    protected $_clauses = [];       // query clauses
    protected $_group   = [];       // grouping
    protected $_order   = [];       // ordering
    protected $_limit   = [];       // result limit
    protected $_data    = [];       // custom query data
    protected $_fields  = [];       // data fields
    protected $_keys    = [];       // data keys
    protected $_pairs   = [];       // data field = key pairs

    /**
     * Initializes a new query
     */
    public function init( $type="" )
    {
        $this->_explain = false;
        $this->_type    = Sanitize::toUpperCase( $type );
        $this->_tables  = [];
        $this->_columns = [];
        $this->_joins   = [];
        $this->_clauses = [];
        $this->_group   = [];
        $this->_order   = [];
        $this->_limit   = [];
        $this->_data    = [];
        $this->_fields  = [];
        $this->_keys    = [];
        $this->_pairs   = [];
        return $this;
    }

    /**
     * Sets data to be used with INSERT/UPDATE type queries
     */
    public function data( $data=[] )
    {
        if( is_array( $data ) )
        {
            foreach( $data as $column => $value )
            {
                $column = trim( $column, ": " ); // normal column name
                $field  = Sanitize::toSqlName( $column ); // backticked column name
                $key    = ":".$column; // prepared column name

                $this->_data[ $key ]      = trim( $value );
                $this->_fields[ $column ] = $field;
                $this->_keys[ $column ]   = $key;
                $this->_pairs[ $column ]  = $field." = ".$key;
            }
        }
        return $this;
    }

    /**
     * Adds EXPLAIN before a query
     */
    public function explain()
    {
        $this->_explain = true;
        return $this;
    }

    /**
     * Starts a new SELECT query for a table
     */
    public function select( $table="" )
    {
        $this->init( "SELECT" );
        $this->tables( $table );
        return $this;
    }

    /**
     * Starts a new INSERT query for a table
     */
    public function insert( $table="" )
    {
        $this->init( "INSERT" );
        $this->tables( $table );
        return $this;
    }

    /**
     * Starts a new REPLACE query for a table
     */
    public function replace( $table="" )
    {
        $this->init( "REPLACE" );
        $this->tables( $table );
        return $this;
    }

    /**
     * Starts a new UPDATE query for a table
     */
    public function update( $table="" )
    {
        $this->init( "UPDATE" );
        $this->tables( $table );
        return $this;
    }

    /**
     * Starts a new DELETE query for a table
     */
    public function delete( $table="" )
    {
        $this->init( "DELETE" );
        $this->tables( $table );
        return $this;
    }

    /**
     * Adds a list of columns to be selected from a table
     */
    public function columns()
    {
        foreach( Utils::split( func_get_args() ) as $column )
        {
            $this->_columns[] = Sanitize::toSqlName( $column );
        }
        return $this;
    }

    /**
     * Adds a list of tables to be selected from
     */
    public function tables()
    {
        foreach( Utils::split( func_get_args() ) as $table )
        {
            $this->_tables[] = Sanitize::toSqlName( $table );
        }
        return $this;
    }

    /**
     * Adds a JOIN table clause
     */
    public function join( $table="", $condition="" )
    {
        $table     = Sanitize::toSqlName( $table );
        $condition = Sanitize::toSqlName( $condition );

        if( !empty( $table ) && !empty( $condition ) )
        {
            $this->_joins[] = "LEFT OUTER JOIN ".$table." ON ".$condition;
        }
        return $this;
    }

    /**
     * WHERE clause helper
     */
    public function equal( $column="", $value=null, $next="" )
    {
        $this->where( $column, "=", $value, $next );
        return $this;
    }

    /**
     * WHERE clause helper
     */
    public function not( $column="", $value=null, $next="" )
    {
        $this->where( $column, "!=", $value, $next );
        return $this;
    }

    /**
     * WHERE clause helper
     */
    public function bigger( $column="", $value=null, $next="" )
    {
        if( is_numeric( $value ) )
        {
            $this->where( $column, ">", intval( $value ), $next );
        }
        return $this;
    }

    /**
     * WHERE clause helper
     */
    public function smaller( $column="", $value=null, $next="" )
    {
        if( is_numeric( $value ) )
        {
            $this->where( $column, "<", intval( $value ), $next );
        }
        return $this;
    }

    /**
     * WHERE clause helper
     */
    public function like( $column="", $value="", $next="" )
    {
        if( !empty( $value ) )
        {
            $this->where( $column, "LIKE", "%".trim( $value )."%", $next );
        }
        return $this;
    }

    /**
     * Adds a new item to the WHERE clause list
     */
    public function where( $column="", $operator="", $value=null, $next="" )
    {
        $column   = Sanitize::toSqlName( $column );
        $operator = Sanitize::toUpperCase( $operator );
        $next     = Sanitize::toUpperCase( $next );
        $key      = ":val". ( count( $this->_data ) + 1 );

        if( !empty( $column ) && !empty( $operator ) && !is_null( $value ) )
        {
            $this->_clauses[] = Sanitize::toSingleSpaces( "(".$column." ".$operator." ".$key.") ".$next );
            $this->_data[ $key ] = trim( $value );
        }
        return $this;
    }

    /**
     * Adds MATCH AGAINST to the WHERE clause
     */
    public function search( $columns="", $value="", $next="" )
    {
        $columns = Sanitize::toSqlName( $columns );
        $next    = Sanitize::toUpperCase( $next );
        $key     = ":val". ( count( $this->_data ) + 1 );

        if( !empty( $columns ) && !empty( $value ) )
        {
            $this->_clauses[] = Sanitize::toSingleSpaces( "(MATCH (".$columns.") AGAINST (".$key." IN BOOLEAN MODE)) ".$next );
            $this->_data[ $key ] = trim( $value );
        }
        return $this;
    }

    /**
     * Adds BETWEEN to the WHERE clause list
     */
    public function between( $column="", $min=0, $max=0, $next="" )
    {
        $column = Sanitize::toSqlName( $column );
        $min    = trim( Sanitize::toNumber( $min ) );
        $max    = trim( Sanitize::toNumber( $max ) );
        $next   = Sanitize::toUpperCase( $next );
        $keys   = [];

        if( !empty( $column ) )
        {
            foreach( [$min, $max] as $value )
            {
                $key = ":val".( count( $this->_data ) + 1 );
                $this->_data[ $key ] = trim( $value );
                $keys[] = $key;
            }
            $this->_clauses[] = Sanitize::toSingleSpaces( "(".$column." BETWEEN ".implode( " AND ", $keys ).") ".$next );
        }
        return $this;
    }

    /**
     * Adds col IN(...) to the WHERE clause
     */
    public function has( $column="", $values=[], $next="" )
    {
        $column = Sanitize::toSqlName( $column );
        $next   = Sanitize::toUpperCase( $next );
        $keys   = [];

        if( !empty( $column ) && is_array( $values ) )
        {
            foreach( $values as $value )
            {
                $key = ":val".( count( $this->_data ) + 1 );
                $this->_data[ $key ] = trim( $value );
                $keys[] = $key;
            }
            $this->_clauses[] = Sanitize::toSingleSpaces( "(".$column." IN (".implode( ", ", $keys ).")) ".$next );
        }
        return $this;
    }

    /**
     * Adds a list of columns to group by
     */
    public function group()
    {
        foreach( Utils::split( func_get_args() ) as $column )
        {
            $this->_group[] = Sanitize::toSqlName( $column );
        }
        return $this;
    }

    /**
     * Adds a column ordering rule to the ORDER BY list
     */
    public function order( $column="", $order="" )
    {
        $column = Sanitize::toSqlName( $column );
        $order  = Sanitize::toUpperCase( $order );

        if( !empty( $column ) && !empty( $order ) )
        {
            $this->_order[] = $column." ".$order;
        }
        return $this;
    }

    /**
     * ORDER helper
     */
    public function orderDown( $column="id" )
    {
        $this->order( $column, "desc" );
        return $this;
    }

    /**
     * ORDER helper
     */
    public function orderUp( $column="id" )
    {
        $this->order( $column, "asc" );
        return $this;
    }

    /**
     * Sets the values used with the LIMIT clause (limit), (offset,limit)
     */
    public function limit()
    {
        $this->_limit = func_get_args();
        return $this;
    }

    /**
     * Builds a query for counting rows in a table
     */
    public function count( $table="", $column="*" )
    {
        $this->select( $table )->columns( "COUNT(".$column.")" );
        return $this;
    }

    /**
     * Builds a query for adding numeric rows in a table
     */
    public function sum( $table="", $column="*", $key="Sum" )
    {
        $this->select( $table )->columns( "SUM(".$column.") AS ".$key );
        return $this;
    }

    /**
     * Builds a query to find rows, ie: (table, id, 123), (table, ['col'=>'val', ...])
     */
    public function find()
    {
        $this->init();
        $args  = func_get_args();
        $table = array_shift( $args );
        $where = [];

        if( !empty( $table ) )
        {
            if( count( $args ) === 2 && is_string( $args[0] ) )
            {
                $where[ $args[0] ] = $args[1];
            }
            else if( count( $args ) === 1 && is_array( $args[0] ) )
            {
                $where = $args[0];
            }
            $this->select( $table );

            foreach( $where as $column => $value )
            {
                if( !is_numeric( $column ) )
                {
                    $this->equal( $column, $value );
                }
            }
        }
        return $this;
    }

    /**
     * Builds a query to insert, or update data for a row
     */
    public function save( $table="", $id=0, $data=[] )
    {
        if( !empty( $id ) ){ $this->update( $table )->data( $data )->equal( "id", $id ); }
        else{ $this->insert( $table )->data( $data ); }
        return $this;
    }

    /**
     * Builds final query string, returns single array with both query string and query data
     */
    public function build()
    {
        $build   = [];
        $tables  = implode( ", ", $this->_tables );
        $columns = !empty( $this->_columns ) ? implode( ", ", $this->_columns ) : "*";

        if( !empty( $this->_explain ) )
        {
            $build[] = "EXPLAIN";
        }
        if( !empty( $this->_type ) )
        {
            $build[] = $this->_type;
        }
        if( $this->_type === "SELECT" )
        {
            $build[] = $columns." FROM ".$tables;
        }
        if( $this->_type === "INSERT" || $this->_type === "REPLACE" )
        {
            $build[] = "INTO ".$tables." (".implode( ", ", $this->_fields ).") VALUES (".implode( ", ", $this->_keys ).")";
        }
        if( $this->_type === "UPDATE" )
        {
            $build[] = $tables." SET ".implode( ", ", $this->_pairs );
        }
        if( $this->_type === "DELETE" )
        {
            $build[] = "FROM ".$tables;
        }
        if( !empty( $this->_joins ) )
        {
            $build[] = implode( " ", $this->_joins );
        }
        if( !empty( $this->_clauses ) )
        {
            $build[] = "WHERE ".preg_replace( "/(AND|OR)$/u", "", implode( " ", $this->_clauses ) );
        }
        if( !empty( $this->_group ) )
        {
            $build[] = "GROUP BY ".implode( ", ", $this->_group );
        }
        if( !empty( $this->_order ) )
        {
            $build[] = "ORDER BY ".implode( ", ", $this->_order );
        }
        if( !empty( $this->_limit ) )
        {
            $build[] = "LIMIT ".implode( ", ", $this->_limit );
        }
        $query = Sanitize::toSingleSpaces( implode( " ", $build ) );
        $data  = $this->_data;
        $this->init();

        return [ $query, $data ];
    }

}