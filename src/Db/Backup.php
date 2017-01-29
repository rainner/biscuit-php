<?php
/**
 * DB Backup helper for creating backup dumps from rows in tables.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Db;

use PDO;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class Backup {

    // props
    protected $_db = null;
    protected $_path = "";
    protected $_error = "";

    /**
     * Constructor
     */
    public function __construct( DbInterface $db )
    {
        $this->_db = $db;
    }

    /**
     * Set the path where table dump files will be saved to
     */
    public function setPath( $path )
    {
        $this->_path = is_string( $path ) ? Sanitize::toPath( $path ) : "";
    }

    /**
     * Get the path where table dump files will be saved to
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Get local error if any, or db class error
     */
    public function getError()
    {
        if( !empty( $this->_error ) ) return $this->_error;
        return $this->_db->getError();
    }

    /**
     * Start scanning and saving table rows
     */
    public function save( $match="" )
    {
        $this->_error = "";
        $cname  = basename( Sanitize::toPath( get_class( $this->_db ) ) );
        $folder = $this->_path."/".$cname."_".date( "Y_m_d" )."_Backup";
        $total  = 0;

        // check errors
        if( !is_dir( $this->_path ) )
        {
            return $this->_e( "Location to save DB dump to does not exist!" );
        }
        if( !is_writable( $this->_path ) )
        {
            return $this->_e( "Location to save DB dump to is not read-only!" );
        }
        if( !is_dir( $folder ) && !mkdir( $folder, 0777, true ) )
        {
            return $this->_e( "Location to save DB dump files could not be created!" );
        }

        // scan tables
        foreach( $this->_db->getTables( $match ) as $table )
        {
            if( empty( $table ) ) continue;

            // init file stream
            $stream  = fopen( $folder."/".$table.".sql", "wb" );
            $result  = null;
            $entries = [];
            $count   = 0;

            // add header to file
            fwrite( $stream, "-- MySQL backup saved ".date( "r" )." -- \n" );
            fwrite( $stream, "-- \n\n" );

            // add MySQL table schema to file
            if( $this->_db instanceof MySQL )
            {
                if( $result = $this->_db->query( "SHOW CREATE TABLE `".$table."`" ) )
                {
                    $create = $result->fetch( PDO::FETCH_NUM );

                    if( !empty( $create[1] ) )
                    {
                        fwrite( $stream, "-- Table (".$table.") schema -- \n" );
                        fwrite( $stream, $this->_fixCreate( $create[1] )." \n\n" );
                    }
                }
            }

            // add SQLite table schema to file
            if( $this->_db instanceof SQLite )
            {
                if( $result = $this->_db->query( "SELECT `sql` FROM `sqlite_master` WHERE `type`='table' AND `name`='".$table."'" ) )
                {
                    $create  = $result->fetch( PDO::FETCH_ASSOC );
                    $indexes = $this->_getSqliteKeys( $table );

                    if( !empty( $create["sql"] ) )
                    {
                        fwrite( $stream, "-- Table (".$table.") schema -- \n" );
                        fwrite( $stream, $this->_fixCreate( $create["sql"] )." \n\n" );
                    }
                    if( !empty( $indexes ) )
                    {
                        fwrite( $stream, "-- Table index list -- \n" );
                        foreach( $indexes as $index ) fwrite( $stream, $index."; \n" );
                        fwrite( $stream, "\n" );
                    }
                }
            }

            // scan table and save rows to file
            if( $result = $this->_db->query( "SELECT * FROM `".$table."`" ) )
            {
                while( $row = $result->fetch( PDO::FETCH_ASSOC ) )
                {
                    $columns = array_keys( $row );
                    $values  = array_values( $row );

                    foreach( $values as $i => $value )
                    {
                        $values[ $i ] = $this->_escape( $value );
                    }
                    fwrite( $stream, "-- Row # ". ( $count + 1 ) ." -- \n" );
                    fwrite( $stream, "INSERT INTO `".$table."` ( `".implode( "`, `", $columns )."` ) \n" );
                    fwrite( $stream, "VALUES ( '".implode( "', '", $values )."' ); \n" );
                    $count += 1;
                }
                fclose( $stream );
                $total += $count;
            }
        }
        return $total;
    }

    /**
     * Get list of queries for creating sqlite table index keys
     */
    private function _getSqliteKeys( $table )
    {
        $output = [];

        if( $result = $this->_db->query( "PRAGMA index_list(`".$table."`)" ) )
        {
            $list = $result->fetchAll( PDO::FETCH_ASSOC );

            for( $i = 0; $i < count( $list ); $i++ )
            {
                $index   = $list[ $i ];
                $name    = !empty( $index["name"] ) ? $index["name"] : "?";
                $primary = !empty( $index["primary"] ) ? " PRIMARY" : "";
                $unique  = !empty( $index["unique"] ) ? " UNIQUE" : "";
                $columns = [];

                if( $result = $this->_db->query( "PRAGMA index_info(`".$name."`)" ) )
                {
                    while( $info = $result->fetch( PDO::FETCH_ASSOC ) )
                    {
                        $columns[] = !empty( $info["name"] ) ? $info["name"] : "?";
                    }
                }
                $output[] = "CREATE".$primary.$unique." INDEX `".$name."` ON `".$table."` (`".implode( "`,`", $columns )."`)";
            }
        }
        return $output;
    }

    /**
     * Fix table create statement
     */
    private function _fixCreate( $query )
    {
        $query = str_replace( "IF NOT EXISTS", "", $query );
        $query = str_replace( "CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $query );
        $query = rtrim( trim( $query ), ";" ) .";";
        return $query;
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

    /**
     * Set local error and return false
     */
    private function _e( $error )
    {
        $this->_error = trim( $error );
        return false;
    }

}
