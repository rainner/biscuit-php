<?php
/**
 * Tests
 */
class UtilsValidateTest extends TestCase {

    public function testClassMethods()
    {
        $this->runTests( "Biscuit\\Utils\\Validate", [

            [ "isKey", true, "test.key_value" ],
            [ "isKey", false, "test key/value" ],

            [ "isSlug", true, "test-slug_value" ],
            [ "isSlug", false, "test slug/value" ],

            [ "isName", true, "Jack O'Neill" ],
            [ "isName", false, "Jack O_Neill" ],

            [ "isMd5", true, md5( "foobar" ) ],
            [ "isMd5", false, uniqid() ],

            [ "isNumber", true, 1234 ],
            [ "isNumber", true, "12.34" ],
            [ "isNumber", false, "abcd" ],

            [ "isInteger", true, 1234 ],
            [ "isInteger", true, "1234" ],
            [ "isInteger", false, "1.275" ],
            [ "isInteger", false, "abcd" ],

            [ "isFloat", true, 1.275 ],
            [ "isFloat", true, "1.275" ],
            [ "isFloat", false, 1234 ],
            [ "isFloat", false, "abcd" ],

            [ "isBool", true, "true" ],
            [ "isBool", true, "false" ],
            [ "isBool", true, 0 ],
            [ "isBool", true, 1 ],
            [ "isBool", true, true ],
            [ "isBool", true, false ],
            [ "isBool", true, "Y" ],
            [ "isBool", true, "N" ],
            [ "isBool", false, 1234 ],
            [ "isBool", false, null ],

            [ "isZipcode", true, "11111" ],
            [ "isZipcode", true, "11111-2222" ],
            [ "isZipcode", false, "123456789" ],
            [ "isZipcode", false, "123" ],

            [ "isPhone", true, "+1 800 555 1234" ],
            [ "isPhone", true, "888-777-1234" ],
            [ "isPhone", true, "123.456.7890" ],
            [ "isPhone", false, "123/456-987" ],
            [ "isPhone", false, "1234" ],
            [ "isPhone", false, "abcd-1234" ],

            [ "isEmail", true, "user.name@service.foo.bar" ],
            [ "isEmail", false, "@service.foo.bar" ],

            [ "isHandle", true, "@handle_name" ],
            [ "isHandle", false, "@handle+name" ],

            [ "isHashtag", true, "#hash_tag" ],
            [ "isHashtag", false, "#hash/tag" ],

            [ "isIp", true, "192.168.0.1" ],
            [ "isIp", false, "192.168.0.1/24" ],
            [ "isIp", false, "localhost" ],

            [ "isIpv6", true, "2001:cdba:0000:0000:0000:0000:3257:9652" ],
            [ "isIpv6", false, "http://2001:cdba:0000:0000:0000:0000:3257:9652/" ],

            [ "isUrl", true, "https://user:pw@www.site.com/path/?foo=bar" ],
            [ "isUrl", true, "//www.site.com/path/" ],
            [ "isUrl", true, "file://foo/bar" ],
            [ "isUrl", false, "/foo/bar" ],

            [ "isType", true, null, "null" ],
            [ "isType", true, true, "boolean" ],
            [ "isType", true, false, "boolean" ],
            [ "isType", true, true, "true" ],
            [ "isType", true, false, "false" ],
            [ "isType", true, function(){}, "function" ],
            [ "isType", true, function(){}, "closure" ],
            [ "isType", true, new stdClass, "object" ],
            [ "isType", true, __DIR__, "directory" ],
            [ "isType", true, __FILE__, "file" ],
            [ "isType", true, "foobar", "string" ],

        ]);
    }
}