<?php
/**
 * TestCase Class
 */
abstract class TestCase extends PHPUnit_Framework_TestCase {

    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        // ...
    }

    /**
     * tearDown
     */
    public function tearDown()
    {
        parent::tearDown();
        // ...
    }

    /**
     * Run tests on a class from an array list of tests
     */
    public function runTests( $class, $tests )
    {
        if( empty( $class ) )
        {
            throw new Exception( "A class name string or object is required" );
        }
        if( !is_array( $tests ) )
        {
            throw new Exception( "Tests list must be an array of methods to run" );
        }
        $classname = is_object( $class ) ? get_class( $class ) : trim( $class );

        foreach( $tests as $params ) // list of test params arrays
        {
            if( is_array( $params ) ) // test params [ method, expected, arg1, arg2, ... ]
            {
                $method    = count( $params ) ? array_shift( $params ) : "";
                $expected  = count( $params ) ? array_shift( $params ) : null;
                $callable  = [ $class, $method ];

                if( !empty( $method ) && is_callable( $callable ) )
                {
                    $actual = call_user_func_array( $callable, $params );
                    $this->assertEquals( $expected, $actual, "ERROR: (".$classname." -> ".$method.") assertEquals failed !!!" );
                }
            }
        }
    }

}