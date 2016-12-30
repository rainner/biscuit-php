<?php
/**
 * Tests
 */
class DataViewTest extends TestCase {

    public function testViewClassMethods()
    {
        $uniqid = uniqid( true );

        $view = new Biscuit\Data\View();
        $view->setTemplate( BASE."/tests/assets/views/testview.php" );
        $view->setKey( "test", $uniqid );

        $html = $view->render();

        $this->assertEquals( '<div>'.$uniqid.'</div>', $html );
    }
}