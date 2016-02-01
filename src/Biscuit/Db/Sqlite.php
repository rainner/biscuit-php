<?php
/**
 * Handles a Sqlite database connection.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Db;

use Biscuit\Util\Utils;
use Biscuit\Util\Sanitize;
use PDO;
use PDOException;
use Exception;

class Sqlite implements DbInterface {

    // PDO object
    protected $pdo = null;

    // total query count
    protected $qcount = 0;

    // connection error
    protected $error = '';

    // connection options
    protected $options = array(

        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_TO_STRING,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    );

    /**
     * Connect to a database using PDO
     */
    public function connect( $config=array() )
    {
        if( $this->connected() !== true )
        {
            $dsn = array();

            // look for connection details in $_ENV
            $env_dbfile  = Utils::getValue( @$_ENV['Sqlite_Database'], 'biscuit.sqlite3' );
            $env_charset = Utils::getValue( @$_ENV['Sqlite_Charset'],  'utf8' );

            // look for connection details in $config
            $dbfile  = Utils::getValue( @$config['dbfile'],   $env_dbfile );
            $charset = Utils::getValue( @$config['charset'],  $env_charset );

            // build connection dns
            if( !empty( $dbfile ) )  $dsn[] = trim( $dbfile );
            if( !empty( $charset ) ) $dsn[] = 'charset=' . trim( $charset );

            // merge options
            if( !empty( $config['options'] ) && is_array( $config['options'] ) )
            {
                $this->options = array_merge( $this->options, $config['options'] );
            }
            // connect
            try
            {
                $dsn = 'sqlite:'.implode( ';', $dsn );
                $this->pdo = new PDO( $dsn, null, null, $this->options );
                return true;
            }
            catch( PDOException $e ){ return $this->error( $e->getMessage() ); }
            catch( Exception $e )   { return $this->error( $e->getMessage() ); }
        }
        return true;
    }

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( $config=array(), $callback=null )
    {
        if( $this->connect( $config ) !== true )
        {
            if( is_callable( $callback ) )
            {
                call_user_func( $callback, $this->error );
            }
        }
    }

    /**
     * Checks for an active PDO object instance
     */
    public function connected()
    {
        return ( $this->pdo instanceof PDO );
    }

    /**
     * Clear current connection object
     */
    public function disconnect()
    {
        $this->pdo = null; // destruct
        return true;
    }

    /**
     * Executes a query and returns PDOStatement, or false
     */
    public function query( $query='', $data=array() )
    {
        if( $this->connected() === true )
        {
            try
            {
                $this->qcount += 1;
                $handler = $this->pdo->prepare( $query );
                $handler->execute( $data );
                return $handler;
            }
            catch( PDOException $e ){ return $this->error( $e->getMessage() ); }
            catch( Exception $e )   { return $this->error( $e->getMessage() ); }
        }
        return $this->error( 'Tried to execute a query wihtout an active connection.' );
    }

    /**
     * Returns a single row from query handler
     */
    public function getRow( $query='', $data=array() )
    {
        if( $handler = $this->query( $query, $data ) )
        {
            if( $output = $handler->fetch() )
            {
                $handler = null;
                return $output;
            }
        }
        return array();
    }

    /**
     * Returns amultiple rows from query handler
     */
    public function getRows( $query='', $data=array() )
    {
        if( $handler = $this->query( $query, $data ) )
        {
            if( $output = $handler->fetchAll() )
            {
                $handler = null;
                return $output;
            }
        }
        return array();
    }

    /**
     * Returns number of rows from query handler when using COUNT()
     */
    public function getCount( $query='', $data=array() )
    {
        if( $handler = $this->query( $query, $data ) )
        {
            if( $output = $handler->fetchColumn() )
            {
                $handler = null;
                return $output + 0;
            }
        }
        return 0;
    }

    /**
     * Returns number of affected rows from query handler
     */
    public function getAffected( $query='', $data=array() )
    {
        if( $handler = $this->query( $query, $data ) )
        {
            if( $output = $handler->rowCount() )
            {
                $handler = null;
                return $output + 0;
            }
        }
        return 0;
    }

    /**
     * Returns last inserted row ID for a query
     */
    public function getId( $query='', $data=array() )
    {
        if( $handler = $this->query( $query, $data ) )
        {
            if( $output = $this->pdo->lastInsertId() )
            {
                $handler = null;
                return $output + 0;
            }
        }
        return 0;
    }

    /**
     * Returns the number of total executed queries
     */
    public function getQueryCount()
    {
        return $this->qcount;
    }

    /**
     * Set and error and return false, or get last error
     */
    public function error( $error=null )
    {
        if( is_string( $error ) )
        {
            $this->error = trim( $error );
            return false;
        }
        return $this->error;
    }

}