<?php
/**
 * Tests
 */
class UtilsPaginateTest extends TestCase {

    public function testClassMethods()
    {
        $page  = 1;
        $total = 100;
        $limit = 20;
        $route = "/?page=";
        $class = new Biscuit\Utils\Paginate( $page, $total, $limit, $route );

        $this->runTests( $class, [

            [ "getPage", $page ],
            [ "getTotal", $total ],
            [ "getLimit", $limit ],
            [ "getRoute", $route ],
            [ "getOffset", 0 ],
            [ "getFirstPage", 1 ],
            [ "getLastPage", $total / $limit ],
            [ "getFirstOffset", 0 ],
            [ "getLastOffset", $total - $limit ],
            [ "getPreviousPage", 1 ],
            [ "getPreviousOffset", 0 ],
            [ "getNextPage", $page + 1 ],
            [ "getNextOffset", $page * $limit ],
        ]);
    }
}