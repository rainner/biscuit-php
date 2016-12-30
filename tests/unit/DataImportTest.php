<?php
/**
 * Tests
 */
class DataImportTest extends TestCase {

    public function testSerializedDataLoadedFromFile()
    {
        $import = new Biscuit\Data\Import();
        $import->setFile( BASE."/tests/assets/data/importdata.json" );
        $import->setFormat( "json_encode" );
        $import->setKeymap([
            "fooKey" => "foo",
            "barKey" => "bar",
        ]);

        $data = $import->parse();

        $this->assertEquals( "1234", @$data["fooKey"] );
        $this->assertEquals( "abcd", @$data["barKey"] );
    }
}