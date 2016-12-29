<?php
/**
 * Tests
 */
class DataRegistryTest extends TestCase {

    public function testClassMethods()
    {
        $now = time();
        $uniqid = uniqid( true );

        $registry = new Biscuit\Data\Registry();
        $registry->useData( [ "test"=> [ "foo"=> $now, "bar"=> $uniqid ] ] );
        $registry->mergeData( [ "other"=> $uniqid ] );
        $registry->loadData( BASE."/tests/assets/data" );
        $registry->setKey( "test.abcd", $now );
        $registry->setKey( "test.efgh", $uniqid );
        $registry->setKey( "test.upper", "uppercase" );
        $registry->formatKey( "test.upper", "Sanitize::toUpperCase" );
        $registry->deleteKey( "test.abcd" );

        $this->runTests( $registry, [

            [ "hasKey", true, "test.foo" ],
            [ "hasKey", true, "test.bar" ],
            [ "hasKey", false, "test.abcd" ], // deleted
            [ "hasKey", true, "test.efgh" ],
            [ "hasKey", false, "test.baz" ],
            [ "hasKey", true, "other" ], // merged
            [ "hasKey", true, "testdata" ], // loaded from file

            [ "hasKeyValue", true, "test.foo", $now ],
            [ "hasKeyValue", true, "test.bar", $uniqid ],
            [ "hasKeyValue", true, "test.upper", "UPPERCASE" ], // formatted
            [ "hasKeyValue", true, "other", $uniqid ],

            [ "isKeyType", true, "test.foo", "integer" ],
            [ "isKeyType", true, "test.bar", "string" ],
            [ "isKeyType", true, "test.baz", "null" ],

            [ "isKeyEmpty", false, "test.foo" ],
            [ "isKeyEmpty", false, "test.bar" ],
            [ "isKeyEmpty", true, "test.baz" ],

            [ "getKey", $now, "test.foo" ],
            [ "getKey", $uniqid, "test.bar" ],
            [ "getKey", null, "test.baz" ],
        ]);
    }
}