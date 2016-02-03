<?php
/**
 * Interface for classes that handle DB connetion.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Db;

interface DbInterface {

    /**
     * Connect to a database using PDO
     */
    public function connect( $config=array() );

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( $config=array(), $callback=null );

    /**
     * Checks for an active PDO object instance
     */
    public function connected();

    /**
     * Clear current connection object
     */
    public function disconnect();

    /**
     * Executes a query and returns PDOStatement, or false
     */
    public function query( $query='', $data=array() );

    /**
     * Returns a single row from PDOStatement
     */
    public function getRow( $query='', $data=array() );

    /**
     * Returns multiple rows from PDOStatement
     */
    public function getRows( $query='', $data=array() );

    /**
     * Returns number of rows from PDOStatement when using COUNT()
     */
    public function getCount( $query='', $data=array() );

    /**
     * Returns number of affected rows from PDOStatement
     */
    public function getAffected( $query='', $data=array() );

    /**
     * Returns last inserted row ID for a query
     */
    public function getId( $query='', $data=array() );

    /**
     * Returns the number of total executed queries
     */
    public function getQueryCount();

    /**
     * Set an error and return false, or get last error
     */
    public function error( $error=null );

}