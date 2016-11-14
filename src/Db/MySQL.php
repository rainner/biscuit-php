<?php
/**
 * Handles a MySQL database connection.
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

class MySQL extends SqlBuilder implements DbInterface {

    // props
    protected $_pdo      = null;         // PDO instance
    protected $_server   = "localhost";  // MySQL server address
    protected $_port     = "3306";       // MySQL server port
    protected $_database = "test";       // database name
    protected $_username = "root";       // db username
    protected $_password = "";           // db password
    protected $_error    = "";           // last error
    protected $_options  = array(        // connection options

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
     * Set info about the server to connect to
     */
    public function setServer( $server, $port=3306 )
    {
        if( is_string( $server ) )
        {
            $this->_server = trim( $server );
        }
        if( is_numeric( $port ) )
        {
            $this->_port = trim( $port );
        }
    }

    /**
     * Set the name of the database to connect to on the server
     */
    public function setDbName( $dbname )
    {
        if( is_string( $dbname ) )
        {
            $this->_database = trim( $dbname );
        }
    }

    /**
     * Set the database username
     */
    public function setUsername( $username )
    {
        if( is_string( $username ) )
        {
            $this->_username = trim( $username );
        }
    }

    /**
     * Set the database username
     */
    public function setPassword( $password )
    {
        if( is_string( $password ) )
        {
            $this->_password = trim( $password );
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
        $dsn = "mysql:" . "host=".$this->_server.";" . "port=".$this->_port.";" . "dbname=".$this->_database;

        if( empty( $this->_server ) )
        {
            return $this->setError( "No MySQL database server address specified." );
        }
        if( empty( $this->_port ) )
        {
            return $this->setError( "No MySQL database server port number specified." );
        }
        try
        {
            $this->_pdo = new PDO( $dsn, $this->_username, $this->_password, $this->_options );
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
            $this->query( "OPTIMIZE TABLE ".$table );
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