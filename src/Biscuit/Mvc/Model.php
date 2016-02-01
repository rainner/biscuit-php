<?php
/**
 * Provides methods for working with a DB table and building SQL queries.
 *
 * valauthor     Rainner Lins | http://rainnerlins.com
 * vallicense    See: /docs/license.txt
 * valcopyright  All Rights Reserved
 */
namespace Biscuit\Mvc;

use Biscuit\Db\DbInterface;
use Biscuit\Db\Row;
use Biscuit\Data\Json;
use Biscuit\Util\Sanitize;
use Biscuit\Util\Utils;
use Biscuit\Util\Expose;
use Closure;

class Model extends Row {

    // props
    protected $_db      = null;     // implements Db\DbInterface
    protected $_table   = '';       // main model table
    protected $_primary = 'id';     // primary column
    protected $_explain = '';       // prepend EXPLAIN to query
    protected $_maps    = array();  // query string data
    protected $_data    = array();  // query exec data
    protected $_limit   = '';       // LIMIT x,y
    protected $_num     = 1;        // key map collision prevention

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Set db connection handler
     */
    public function setConnection( DbInterface $db )
    {
        $this->_db = $db;
    }

    /**
     * Access to error message on DB object instance
     */
    public function getError()
    {
        return $this->_db->error();
    }

    /**
     * Sets current working table name
     */
    public function setTable( $table='' )
    {
        if( !empty( $table ) && is_string( $table ) )
        {
            $this->_table = Sanitize::toText( $table );
        }
        $this->reset();
    }

    /**
     * Adds an outer table to the list
     */
    public function addTable( $table='' )
    {
        if( !empty( $table ) && is_string( $table ) )
        {
            $this->_maps['tables'][ trim( $table ) ] = '%s';
        }
    }

    /**
     * Get current model table name
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * Get all added tables
     */
    public function getTables()
    {
        return $this->_tables;
    }

    /**
     * Sets table primary column name
     */
    public function setPrimary( $column='' )
    {
        if( is_string( $column ) )
        {
            $this->_primary = Sanitize::toKey( $column );
        }
    }

    /**
     * Get table primary column name
     */
    public function getPrimary()
    {
        return $this->_primary;
    }

    /**
     * Reset query building data
     */
    public function reset()
    {
        $this->_explain = '';
        $this->_maps    = array();
        $this->_data    = array();
        $this->_limit   = '';
        $this->_num     = 1;
        $this->addTable( $this->_table );
        return $this;
    }

    /**
     * Adds EXPLAIN before a query
     */
    public function explain()
    {
        $this->_explain = 'EXPLAIN ';
        return $this;
    }

    /**
     * Adds a list of columns to be selected
     */
    public function columns()
    {
        $this->_maps['columns'] = array();

        foreach( $this->_args( func_get_args() ) as $column )
        {
            $column = $this->_clause( $column );

            if( !empty( $column ) )
            {
                $this->_maps['columns'][ $column ] = '%s';
            }
        }
        return $this;
    }

    /**
     * Adds a column ordering rule to the ORDER BY list
     */
    public function order( $column='', $order='' )
    {
        $this->_maps['order'] = array();

        if( !empty( $column ) && !empty( $order ) )
        {
            $column = $this->_clause( $column );
            $order  = strtoupper( trim( $order ) );

            $this->_maps['order'][ $column ] = $this->_clause( '%s '.$order );
        }
        return $this;
    }

    /**
     * Adds a list of columns to group by
     */
    public function group()
    {
        $this->_maps['group'] = array();

        foreach( $this->_args( func_get_args() ) as $column )
        {
            $column = $this->_clause( $column );

            if( !empty( $column ) )
            {
                $this->_maps['group'][ $column ] = '%s';
            }
        }
        return $this;
    }

    /**
     * Sets data to be used with INSERT/UPDATE type queries
     */
    public function data( $data=array() )
    {
        $this->_data['user'] = array();

        if( is_array( $data ) )
        {
            foreach( $data as $column => $value )
            {
                if( !empty( $column ) && !is_numeric( $column ) )
                {
                    $key = ':'.trim( $column, ': ' );
                    $this->_data['user'][ $key ] = Sanitize::toString( $value );
                }
            }
        }
        return $this;
    }

    /**
     * Adds a LEFT OUTER JOIN on another table on foreign = local columns
     */
    public function join( $table='', $foreign='', $local='' )
    {
        if( !empty( $table ) && !empty( $foreign ) && !empty( $local ) )
        {
            $tb2       = trim( $table );
            $table     = $this->_backtick( $table );
            $condition = $this->_clause( $tb2.'.'.$foreign.' = '.$this->_table.'.'.$local );

            $this->_maps['joins'][ $condition ] = $this->_clause( 'LEFT OUTER JOIN '.$table.' ON (%s)' );
        }
        return $this;
    }

    /**
     * Adds a new WHERE clause operational statement
     */
    public function where( $column='', $operator='', $value=null, $next='' )
    {
        if( !empty( $column ) && !empty( $operator ) && !is_null( $value ) )
        {
            $column   = $this->_clause( $column );
            $operator = strtoupper( trim( $operator ) );
            $next     = strtoupper( trim( $next ) );
            $key      = ':val'.$this->_num;

            $this->_maps['where'][ $column ] = $this->_clause( '(%s '.$operator.' '.$key.') '.$next );
            $this->_data['query'][ $key ] = Sanitize::toString( $value );
            $this->_num++;
        }
        return $this;
    }

    /**
     * Adds MATCH AGAINST to the WHERE clause
     */
    public function match( $columns='', $value=null, $next='' )
    {
        if( !empty( $columns ) && !is_null( $value ) )
        {
            $columns  = $this->_clause( $columns );
            $next     = strtoupper( trim( $next ) );
            $key      = ':val'.$this->_num;

            $this->_maps['where'][ $columns ] = $this->_clause( '(MATCH (%s) AGAINST ('.$key.' IN BOOLEAN MODE)) '.$next );
            $this->_data['query'][ $key ] = Sanitize::toString( $value );
            $this->_num++;
        }
        return $this;
    }

    /**
     * Adds col IN(...) to the WHERE clause
     */
    public function within( $column='', $values=array(), $next='' )
    {
        if( !empty( $column ) && !empty( $values ) )
        {
            $column = $this->_clause( $column );
            $values = array_values( $values );
            $next   = strtoupper( trim( $next ) );
            $keys   = array();

            foreach( $values as $value )
            {
                $key = ':val'.$this->_num;
                $keys[] = $key;
                $this->_data['query'][ $key ] = Sanitize::toString( $value );
                $this->_num++;
            }
            $this->_maps['where'][ $column ] = $this->_clause( '(%s IN ('.implode( ',', $keys ).')) '.$next );
        }
        return $this;
    }

    /**
     * Adds col BETWEEN( min AND max ) to the WHERE clause
     */
    public function between( $column='', $min=null, $max=null, $next='' )
    {
        if( !empty( $column ) && !is_null( $min ) && !is_null( $max ) )
        {
            $column = $this->_clause( $column );
            $values = array( $min, $max );
            $next   = strtoupper( trim( $next ) );
            $keys   = array();

            foreach( $values as $value )
            {
                $key = ':val'.$this->_num;
                $keys[] = $key;
                $this->_data['query'][ $key ] = Sanitize::toString( $value );
                $this->_num++;
            }
            $this->_maps['where'][ $column ] = $this->_clause( '(%s BETWEEN '.implode( ' AND ', $keys ).') '.$next );
        }
        return $this;
    }

    /**
     * Adds a WHERE LIKE operational statement
     */
    public function like( $column='', $value=null, $next='' )
    {
        return $this->where( $column, 'LIKE', '%'.$value.'%', $next );
    }

    /**
     * Adds a WHERE = operational statement
     */
    public function equal( $column='', $value=null, $next='' )
    {
        return $this->where( $column, '=', $value, $next );
    }

    /**
     * Adds a WHERE != operational statement
     */
    public function not( $column='', $value=null, $next='' )
    {
        return $this->where( $column, '!=', $value, $next );
    }

    /**
     * Adds a WHERE > operational statement
     */
    public function bigger( $column='', $value=null, $next='' )
    {
        return $this->where( $column, '>', $value, $next );
    }

    /**
     * Adds a WHERE < operational statement
     */
    public function smaller( $column='', $value=null, $next='' )
    {
        return $this->where( $column, '<', $value, $next );
    }

    /**
     * Sets the values used with the LIMIT clause (limit), (offset,limit)
     */
    public function limit()
    {
        $args = $this->_args( func_get_args() );
        $this->_limit = '';

        if( !empty( $args ) )
        {
            $this->_limit = 'LIMIT '.implode( ',', $args );
        }
        return $this;
    }

    /**
     * Get row count (commit)
     */
    public function count( $format=false )
    {
        if( empty( $this->_maps['columns'] ) )
        {
            $this->columns( 'COUNT('.$this->_primary.')' );
        }
        list( $query, $data ) = $this->_buildQuery( 'SELECT', false );
        $count = $this->_db->getCount( $query, $data );
        return ( $format === true ) ? number_format( $count ) : $count + 0;
    }

    /**
     * Get multiple rows (commit)
     */
    public function select( $filter=null )
    {
        list( $query, $data ) = $this->_buildQuery( 'SELECT' );
        $rows = $this->_db->getRows( $query, $data );
        return $this->_filter( $rows, $filter );
    }

    /**
     * Insert new data (commit)
     */
    public function insert( $callback=null )
    {
        list( $query, $data ) = $this->_buildQuery( 'INSERT' );
        $result = $this->_db->getId( $query, $data );
        return $this->_callback( $result, $callback );
    }

    /**
     * Replace data (commit)
     */
    public function replace( $callback=null )
    {
        list( $query, $data ) = $this->_buildQuery( 'REPLACE' );
        $result = $this->_db->getId( $query, $data );
        return $this->_callback( $result, $callback );
    }

    /**
     * Update data (commit)
     */
    public function update( $callback=null )
    {
        list( $query, $data ) = $this->_buildQuery( 'UPDATE' );
        $result = $this->_db->getAffected( $query, $data );
        return $this->_callback( $result, $callback );
    }

    /**
     * Delete row/s (commit)
     */
    public function delete( $callback=null )
    {
        list( $query, $data ) = $this->_buildQuery( 'DELETE' );
        $result = $this->_db->getAffected( $query, $data );
        return $this->_callback( $result, $callback );
    }

    /**
     * Delete a row from table by ID and pass row data to a callback on success
     */
    public function fetchDelete( $id=0, $callback=null )
    {
        $row    = $this->reset()->where( $this->_primary, '=', $id )->fetchOne();
        $result = $this->reset()->where( $this->_primary, '=', $id )->limit( 1 )->delete();
        if( !empty( $result ) ) $this->_callback( $row, $callback );
        return $result;
    }

    /**
     * Same as select(), but will return the first row in the list
     */
    public function fetchOne( $filter=null )
    {
        $rows = $this->select( $filter );
        return array_shift( $rows );
    }

    /**
     * Get the first added row in current table
     */
    public function fetchFirst( $filter=null )
    {
        $this->reset();
        $this->order( $this->_primary, 'ASC' );
        $this->limit( 1 );
        return $this->fetchOne( $filter );
    }

    /**
     * Get the last added row in current table
     */
    public function fetchLast( $filter=null )
    {
        $this->reset();
        $this->order( $this->_primary, 'DESC' );
        $this->limit( 1 );
        return $this->fetchOne( $filter );
    }

    /**
     * Get single row from current table that matches primary column id
     */
    public function fetchId( $id=0, $filter=null )
    {
        $this->reset();
        $this->where( $this->_primary, '=', $id );
        $this->limit( 1 );
        return $this->fetchOne( $filter );
    }

    /**
     * Builds the final query using local params
     */
    private function _buildQuery( $type, $reset=true )
    {
        $query  = strtoupper( trim( $type ) ).' ';
        $parts  = array();
        $fields = array();
        $pairs  = array();
        $keys   = array();
        $data   = array();
        $build  = '';

        // compile query parts from _maps
        foreach( $this->_maps as $mapcat => $maplist )
        {
            $build = '';
            $split = ( $mapcat == 'tables' || $mapcat == 'columns' ) ? ',' : ' ';

            foreach( $maplist as $subject => $format )
            {
                $subject = $this->_col( $subject );
                $build  .= sprintf( $format, $subject ) . $split;
            }
            $parts[ $mapcat ] = preg_replace( '/(AND|OR)$/i','', trim( $build, ', ' ) );
        }

        // compile final query data used for insert/update
        if( !empty( $this->_data['query'] ) )
        {
            $data = array_merge( $data, $this->_data['query'] );
        }
        if( !empty( $this->_data['user'] ) )
        {
            $data = array_merge( $data, $this->_data['user'] );

            foreach( $this->_data['user'] as $datacol => $dataval )
            {
                $cplain   = trim( $datacol, ': ' );
                $cticked  = $this->_col( $cplain );
                $fields[] = $cticked;
                $pairs[]  = $cticked.'= :'.$cplain;
                $keys[]   = ':'.$cplain;
            }
        }
        // set fallback column and table
        $columns = Utils::getValue( @$parts['columns'], $this->_col( '*' ) );
        $tables  = Utils::getValue( @$parts['tables'],  $this->_col( $this->_table ) );

        // finalize query string
        if( $type === 'SELECT' )
        {
            $query .= $columns.' FROM '.$tables.' ';
        }
        if( $type === 'INSERT' || $type === 'REPLACE' )
        {
            $query .= 'INTO '.$tables.' ('.implode( ',', $fields ).') VALUES ('.implode( ',', $keys ).') ';
        }
        if( $type === 'UPDATE' )
        {
            $query .= $tables.' SET '.implode( ',', $pairs ).' ';
        }
        if( $type === 'DELETE' )
        {
            $query .= 'FROM '.$tables.' ';
        }
        if( !empty( $parts['joins'] ) ) $query .= $parts['joins'].' ';
        if( !empty( $parts['where'] ) ) $query .= 'WHERE '.$parts['where'].' ';
        if( !empty( $parts['group'] ) ) $query .= 'GROUP BY '.$parts['group'].' ';
        if( !empty( $parts['order'] ) ) $query .= 'ORDER BY '.$parts['order'].' ';

        // finalize query
        $query = $this->_explain.' '.$query.' '.$this->_limit;
        $query = trim( preg_replace( '/\s\s+/', ' ', $query ) );

        // reset and return output
        if( $reset === true ) $this->reset();
        return array( $query, $data );
    }

    /**
     * Sanitizes a clause string
     */
    private function _clause( $clause='' )
    {
        return trim( preg_replace( '/\s\s+/', ' ', $clause ) );
    }

    /**
     * Parses given arguments into an array of string values
     */
    private function _args( $args=array() )
    {
        $output = array();

        if( !empty( $args ) )
        {
            if( is_string( $args ) )
            {
                $output = explode( ',', trim( $args, ',' ) );
            }
            else if( is_array( $args ) )
            {
                $output = array_values( $args );
            }
        }
        foreach( $output as $i => $value )
        {
            $output[ $i ] = trim( $value );
        }
        return $output;
    }

    /**
     * Finalizes a column name value by splitting it and adding backticks
     */
    private function _col( $value='' )
    {
        $output = array();

        foreach( explode( ',', trim( $value, ',' ) ) as $column )
        {
            $column = trim( $column );

            if( strpos( $column, '.' ) === false && !empty( $this->_maps['joins'] ) && $column !== $this->_table )
            {
                $column = $this->_table.'.'.$column;
            }
            $output[] = $this->_backtick( $column );
        }
        return implode( ',', $output );
    }

    /**
     * Adds backticks around table/column names
     */
    private function _backtick( $value='' )
    {
        $value  = preg_replace( '/[^\w\.\,\*\(\)\=\ ]+/i', '', $value );
        $value  = trim( preg_replace( '/\s\s+/i',' ', $value ) );

        // matches: tb1.col2 AS col2Alias
        if( preg_match( '/^(?P<left>[\w\.\(\)\*\ ]+)\s+(?P<middle>[AS\=]+)\s+(?P<right>[\w\.]+)$/i', $value, $m ) === 1 )
        {
            return $this->_backtick( $m['left'] ).' '.strtoupper( trim( $m['middle'] ) ).' '.$this->_backtick( $m['right'] );
        }
        // matches: COUNT(tb1.col2)
        if( preg_match( '/^(?P<left>[\w]+\()(?P<middle>[\w\.\*\ ]+)(?P<right>\))$/i', $value, $m ) === 1 )
        {
            return strtoupper( trim( $m['left'] ) ) . $this->_backtick( $m['middle'] ) . trim( $m['right'] );
        }
        // add backticks
        $value = '`'.implode( '`.`', explode( '.', $value ) ).'`';
        $value = str_replace( '`*`', '*', $value );
        return $value;
    }

    /**
     * Fires a callback for a non-empty result
     */
    public function _callback( $result=null, $callback=null )
    {
        if( !empty( $result ) && $callback instanceof Closure )
        {
            $callback = $callback->bindTo( $this, $this );
            call_user_func( $callback, $result );
        }
        return $result;
    }

    /**
     * Filters a list of rows with a callback filter
     */
    public function _filter( $rows=array(), $callback=null )
    {
        if( !empty( $rows ) && $callback instanceof Closure )
        {
            $callback = $callback->bindTo( $this, $this );

            foreach( $rows as $i => $row )
            {
                $rows[ $i ] = call_user_func( $callback, $row );
            }
        }
        return $rows;
    }

}