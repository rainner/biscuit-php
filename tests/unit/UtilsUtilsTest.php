<?php
/**
 * Tests
 */
class UtilsUtilsTest extends TestCase {

    public function testClassMethods()
    {
        define( "TEST_CONST", 1 );
        $phpv = phpversion();
        $defval = "";

        $this->runTests( "Biscuit\\Utils\\Utils", [

            [ "value",  "foo",    "",  null,  "foo",  $defval ],
            [ "value",  false,    "",  null,  false,  $defval ],
            [ "value",  0,        0,   null,  "foo",  $defval ],
            [ "value",  $defval,  "",  null,  "",     $defval ],

            [ "constant", 1, "FOO", "TEST_CONST", $defval ],
            [ "constant", $phpv, "FOO", "PHP_VERSION", $defval ],
            [ "constant", $defval, "FOO", "PHP_VERSION_TWO", $defval ],

            [ "merge", ["foo", "bar"], ["foo"], ["bar"] ],

            [ "split", ["foo", "bar", "baz"], "foo, bar, baz", "," ],
            [ "split", ["foo", "bar", "baz"], ["foo", "bar, baz"], "," ],
            [ "split", ["foo", "bar", "baz"], ["foo", "bar", "baz"], "," ],

            [ "concat", "foo bar baz", ["foo", ["bar", "baz"]], " " ],
            [ "concat", "foo,bar,baz", ["foo", "bar", "baz"], "," ],

            [ "exists", true, "foo", ["foo"=> "bar"] ],
            [ "exists", true, "bar", ["foo"=> "bar"] ],
            [ "exists", true, "foo", "foo" ],
            [ "exists", false, null, [] ],

            [ "attributes", 'foo="1" bar="2"', ["foo"=> 1, "bar"=> 2 ] ],

            [ "serialize", "1", true ],
            [ "serialize", "0", false ],
            [ "serialize", "", null ],
            [ "serialize", "1234", 1234 ],
            [ "serialize", '{"foo":"bar"}', ["foo"=> "bar"] ],

            [ "unserialize", 1, "1" ],
            [ "unserialize", 0, "0" ],
            [ "unserialize", true, "true" ],
            [ "unserialize", false, "false" ],
            [ "unserialize", null, "null" ],
            [ "unserialize", ["foo"=> "bar"], '{"foo":"bar"}' ],
            [ "unserialize", [], "[]" ],
            [ "unserialize", [], "{}" ],

            [ "render", "test 1234 bar", "test %foo% bar", ["foo"=> 1234], "%", "%" ],
            [ "render", "test 1234 bar", "test {{foo}} bar", ["foo"=> 1234], "{{", "}}" ],

            [ "replace", "test 1234 bar", "test foo bar", "/foo/i", "1234" ],
            [ "replace", "test foo bar", "test foo bar", "/xyz/i", "1234" ],

            [ "escape", "O\\'Neil", "O'Neil" ],
            [ "escape", "O\\'Neil", "O\\\\'Neil" ],

            [ "unescape", "O'Neil", "O\\'Neil" ],
            [ "unescape", "O'Neil", "O\\\\'Neil" ],

            [ "shrink", "this is a...", "this is a long string", 12, "..." ],
            [ "shrink", "this is a lo", "this is a long string", 12, "" ],

            [ "fill", "v1.......:", "v1", 10, ".", ":" ],
            [ "fill", "value2...:", "value2", 10, ".", ":" ],

            [ "crop", "value", "test=[value]", "=[", "]" ],

            [ "cardType", "American Express", "371449635398431" ],
            [ "cardType", "Diners Club", "30569309025904" ],
            [ "cardType", "Discover", "6011000990139424" ],
            [ "cardType", "JCB", "3530111333300000" ],
            [ "cardType", "MasterCard", "5555555555554444" ],
            [ "cardType", "Visa", "4012888888881881" ],

            [ "buildPath", "/foo/bar", "foo", "bar" ],
            [ "buildPath", "/foo/bar", "/foo/", "bar/" ],

        ]);
    }
}