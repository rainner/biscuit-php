<?php
/**
 * Tests
 */
class BootRuntimeTest extends TestCase {

    public function testEnvDataLoadedFromFile()
    {
        $runtime = new Biscuit\Boot\Runtime();
        $runtime->loadEnv( BASE."/tests/assets/data/envdata.ini" );

        $this->assertEquals( "1234", @$_ENV["FOO"] );
        $this->assertEquals( "abcd", @$_ENV["BAR"] );
    }
}