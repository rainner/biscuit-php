<?php
/**
 * Interface for classes that handle DB connetion.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Db;

use Closure;

interface DbInterface {

    /**
     * Connect to a database using PDO
     */
    public function connect();

    /**
     * Try to connect, fire a custom callback on error
     */
    public function connectOr( Closure $callback );

    /**
     * Checks for an active PDO object instance
     */
    public function connected();

    /**
     * Clear current connection object
     */
    public function disconnect();

    /**
     * Executes a query and returns result object, or false
     */
    public function query( $query="", $data=[] );

    /**
     * Returns a single row from result object
     */
    public function getRow( $query="", $data=[] );

    /**
     * Returns multiple rows from result object
     */
    public function getRows( $query="", $data=[] );

    /**
     * Returns number of rows from result object when using COUNT()
     */
    public function getCount( $query="", $data=[] );

    /**
     * Returns number of affected rows from result object
     */
    public function getAffected( $query="", $data=[] );

    /**
     * Returns last inserted row ID for a query
     */
    public function getId( $query="", $data=[] );

    /**
     * Set an error message and return false
     */
    public function setError( $error="" );

    /**
     * Checks is an error message has been set
     */
    public function hasError();

    /**
     * Get last error message
     */
    public function getError();

}