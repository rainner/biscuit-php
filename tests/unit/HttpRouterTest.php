<?php
/**
 * Tests
 */
class HttpRouterTest extends TestCase {

    public function testRouteControllerActionResult()
    {
        // PATH: /controller/action/arg1/arg2/...

        $_SERVER["REQUEST_METHOD"] = "GET";
        $_SERVER["PATH_INFO"] = "/home/test/foo/bar/";

        $router = new Biscuit\Http\Router();
        $router->setBasePath( BASE."/tests/routes" );
        $router->inject( "stdclass", new stdClass );

        $result = $router->resolve( function() { return false; } );

        // resolved controller actions in ../routes/ should return someting,
        // or else fallback function will return false above (404).

        $this->assertInstanceOf( stdClass::class, $router->stdclass );
        $this->assertEquals( true, $router->isArea( "site" ) );
        $this->assertEquals( true, $router->isController( "home" ) );
        $this->assertEquals( true, $router->isAction( "test" ) );
        $this->assertEquals( "foobar", $result );
    }


}