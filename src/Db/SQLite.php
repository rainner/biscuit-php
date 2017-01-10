<?php
/**
 * Handles a SQLite3 database connection.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Db;

use PDO;
use Exception;
use Closure;
use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class SQLite extends SQLBuilder implements DbInterface {

    // props
    protected $_pdo      = null;     // PDO instance
    protected $_file     = "";       // database file
    protected $_error    = "";       // last error
    protected $_options  = array(    // connection options

        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_TIMEOUT => 5,
    );

    /**
     * Set the database file to be used
     */
    public function setDbFile( $file )
    {
        if( is_string( $file ) )
        {
            $this->_file = Sanitize::toPath( $file );
        }
    }

    /**
     * Set connection options
     */
    public function setOptions( $options )
    {
        if( is_array( $options ) )
        {
            $this->_options = array_merge( $this->_options, $options );
        }
    }

    /**
     * Start a new PDO connection instance
     */
    public function connect()
    {
        $this->_error = "";
        $folder = dirname( $this->_file );
        $dsn = "sqlite:". $this->_file;

        if( empty( $this->_file ) )
        {
            return $this->setError( "No SQLite database filename specified." );
        }
        if( !is_dir( $folder ) && !mkdir( $folder, 0775, true ) )
        {
            return $this->setError( "Database file base directory could not be found or created." );
        }
        try
        {
            $this->_pdo = new PDO( $dsn, null, null, $this->_options );
            return true;
        }
        catch( Exception $e )
        {
            return $this->setError( $e->getMessage() );
        }
        return false;
    }

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( Closure $callback )
    {
        if( $this->connect() !== true )
        {
            call_user_func( $callback, $this->_error );
        }
    }

    /**
     * Checks for an active SQLite3 instance
     */
    public function connected()
    {
        return ( $this->_pdo instanceof PDO );
    }

    /**
     * Clear current connection object
     */
    public function disconnect()
    {
        $this->_pdo = null;
        return true;
    }

    /**
     * Executes a query and returns result object
     */
    public function query( $query="", $data=[] )
    {
        $this->_error = "";

        if( $this->connected() )
        {
            try
            {
                $statement = $this->_pdo->prepare( $query );
                $statement->execute( $data );
                return $statement;
            }
            catch( Exception $e )
            {
                $this->_error = $e->getMessage();
            }
        }
        return false;
    }

    /**
     * Checks if a table has been created
     */
    public function hasTable( $table )
    {
        $table = Sanitize::toSqlName( $table );

        if( $result = $this->query( "SELECT 1 FROM ".$table." LIMIT 1" ) )
        {
            return $result;
        }
        return false;
    }

    /**
     * Remove all rows from a table
     */
    public function emptyTable( $table )
    {
        $table = Sanitize::toSqlName( $table );

        if( $result = $this->query( "DELETE FROM ".$table ) )
        {
            $this->query( "VACUUM" );
            return $result;
        }
        return false;
    }

    /**
     * Remove table and all rows
     */
    public function dropTable( $table )
    {
        $table = Sanitize::toSqlName( $table );

        if( $result = $this->query( "DROP TABLE ".$table ) )
        {
            $this->query( "VACUUM" );
            return $result;
        }
        return false;
    }

    /**
     * Returns a single row from query handler
     */
    public function getRow( $query="", $data=[] )
    {
        if( empty( $query ) )
        {
            list( $query, $data ) = $this->build();
        }
        if( $result = $this->query( $query, $data ) )
        {
            if( $output = $result->fetch() )
            {
                return $output;
            }
        }
        return [];
    }

    /**
     * Returns amultiple rows from query handler
     */
    public function getRows( $query="", $data=[] )
    {
        if( empty( $query ) )
        {
            list( $query, $data ) = $this->build();
        }
        if( $result = $this->query( $query, $data ) )
        {
            if( $output = $result->fetchAll() )
            {
                return $output;
            }
        }
        return [];
    }

    /**
     * Returns number of rows from query handler when using COUNT()
     */
    public function getCount( $query="", $data=[] )
    {
        if( empty( $query ) )
        {
            list( $query, $data ) = $this->build();
        }
        if( $result = $this->query( $query, $data ) )
        {
            if( $output = $result->fetchColumn() )
            {
                return intval( $output );
            }
        }
        return 0;
    }

    /**
     * Returns number of affected rows from query handler
     */
    public function getAffected( $query="", $data=[] )
    {
        if( empty( $query ) )
        {
            list( $query, $data ) = $this->build();
        }
        if( $result = $this->query( $query, $data ) )
        {
            if( $output = $result->rowCount() )
            {
                return intval( $output );
            }
        }
        return 0;
    }

    /**
     * Returns last inserted row ID for a query
     */
    public function getId( $query="", $data=[] )
    {
        if( empty( $query ) )
        {
            list( $query, $data ) = $this->build();
        }
        if( $result = $this->query( $query, $data ) )
        {
            if( $output = $this->_pdo->lastInsertId() )
            {
                return intval( $output );
            }
        }
        return 0;
    }

    /**
     * Set and error and return false
     */
    public function setError( $error="" )
    {
        $this->_error = Utils::value( $error, "There has been an unspecified database error." );
        return false;
    }

    /**
     * Checks is an error message has been set
     */
    public function hasError()
    {
        return !empty( $this->_error );
    }

    /**
     * Get last error message
     */
    public function getError()
    {
        return $this->_error;
    }

}