<?php
/**
 * Tests
 */
class DbTableBuilderTest extends TestCase {

    // common test table scheme for both sqlite and mysql
    public function getTableSchema( $name, $is_sqlite )
    {
        $table = new Biscuit\Db\TableBuilder( $name, $is_sqlite );
        $table->setEngine( "InnoDB" );
        $table->setCharset( "utf8" );
        $table->setCollate( "utf8_unicode_ci" );

        $table->addPrimary( "id", "integer", true );
        $table->addColumn( "created", "int", "0" );
        $table->addColumn( "title", "char(100)", "" );
        $table->addColumn( "content", "text" );
        $table->uniqueIndex( "title" );

        $table->addRow([
            "created" => time(),
            "title" => "First row",
            "content" => "this is the full text content.",
        ]);

        $table->addRow([
            "created" => time(),
            "title" => "Another row",
            "content" => "this is the full text content.",
        ]);
        return $table;
    }

    // run final checks on a db object (sqlite/mysql) to check the table
    public function runFinalDbAsserts( $db, $name )
    {
        $this->assertEquals( true, $db->hasTable( $name ), "Table was not created" );
        $this->assertEquals( 2, $db->count( $name )->getCount(), "Table has no rows" );
        $this->assertEquals( true, $db->dropTable( $name ), "Table drop failed" );
        $this->assertEquals( false, $db->hasTable( $name ), "Table still exists" );
        $db->disconnect();
    }

    // test table creation with sqlite
    public function testCreateSQLiteTable()
    {
        // table name
        $name = "foobar";

        // connect to sqlite db
        $db = new Biscuit\Db\SQLite();
        $db->setDbFile( ":memory:" );
        $db->connect();

        // check connection
        $this->assertEquals( true, $db->connected(), "SQLite connection failed" );

        // setup table schema object for sqlite
        $table = $this->getTableSchema( $name, true );
        $db->dropTable( $name );

        // build and run queries
        foreach( $table->getQueries() as $query )
        {
            $result = $db->query( $query );
            $error  = $db->getError();
            $this->assertEquals( true, $result instanceof PDOStatement, "SQLite query failed: ". $error );
        }
        // run final checks
        $this->runFinalDbAsserts( $db, $name );
    }

    // test table creation with sqlite
    public function testCreateMySQLTable()
    {
        // table name
        $name = "foobar";

        // connect to mysql db (local/Travis)
        $db = new Biscuit\Db\MySQL();
        $db->setServer( "127.0.0.1", 3306 );
        $db->setAuth( "root", "" );
        $db->setDatabase( "biscuit_test_db" );
        $db->connect();

        // check connection
        $this->assertEquals( true, $db->connected(), "MySQL connection failed" );

        // setup table schema object for sqlite
        $table = $this->getTableSchema( $name, false );
        $db->dropTable( $name );

        // build and run queries
        foreach( $table->getQueries() as $query )
        {
            $result = $db->query( $query );
            $error  = $db->getError();
            $this->assertEquals( true, $result instanceof PDOStatement, "MySQL query failed: ". $error );
        }
        // run final checks
        $this->runFinalDbAsserts( $db, $name );
    }

}
