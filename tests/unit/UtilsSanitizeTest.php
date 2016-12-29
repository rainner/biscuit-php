<?php
/**
* Tests
*/
class UtilsSanitizeTest extends TestCase {

    public function testClassMethods()
    {
        $fn = function(){};

        $this->runTests( "Biscuit\\Utils\\Sanitize", [

            [ "toString", "foobar", "foobar" ],
            [ "toString", "12345", "12345" ],
            [ "toString", "12345", 12345 ],
            [ "toString", "", array() ],
            [ "toString", "", null ],

            [ "toArray", ["foo"=> "bar"], ["foo"=> "bar"] ],
            [ "toArray", [], array() ],
            [ "toArray", [], "array" ],
            [ "toArray", [], null ],

            [ "toClosure", $fn, function(){} ],
            [ "toClosure", $fn, [] ],
            [ "toClosure", $fn, 1 ],
            [ "toClosure", $fn, null ],

            [ "toFloat", 1.5, 1.50 ],
            [ "toFloat", 1.0, 1.000 ],
            [ "toFloat", 1.0, "1.000" ],
            [ "toFloat", 0.0 ],

            [ "toNumber", 1, 1.75 ],
            [ "toNumber", 2, "2.99" ],
            [ "toNumber", 0 ],

            [ "toBool", true, true ],
            [ "toBool", true, 1 ],
            [ "toBool", true, "1" ],
            [ "toBool", true, "Y" ],
            [ "toBool", true, "yes" ],
            [ "toBool", true, "on" ],
            [ "toBool", true, "true" ],
            [ "toBool", false, false ],
            [ "toBool", false, 0 ],
            [ "toBool", false, "0" ],
            [ "toBool", false, "N" ],
            [ "toBool", false, "no" ],
            [ "toBool", false, "off" ],
            [ "toBool", false, "false" ],

            [ "toText", "", "<img onload='hack' />" ],
            [ "toText", "var foo = 'bar';", "<script>var foo = 'bar';</script>" ],
            [ "toText", '"quoted text"', '"quoted text"' ],
            [ "toText", "1234", 1234 ],

            [ "toAlnum", "abcdef123", "(abc!@#def$%^123)" ],
            [ "toAlnum", "1234", 12.34 ],
            [ "toAlnum", "1234", 1234 ],

            [ "toSingleSpaces", "123 456, 789 - abc", "\t 123    456,    789 - abc \n\n" ],
            [ "toSingleSpaces", "1234", 1234 ],

            [ "toCaps", "Test Caps", "test caps" ],
            [ "toCaps", "TEST CAPS", "TEST CAPS" ],
            [ "toCaps", "Test-Caps", "test-caps" ],
            [ "toCaps", "Test,Caps/Foo(Bar)", "test,caps/foo(bar)", [",","/","(",")"] ],

            [ "toUpperCase", "TEST UPPER CASE", "test upper case" ],
            [ "toUpperCase", "TEST-UPPER-CASE", "test-upper-case" ],

            [ "toLowerCase", "test lower case", "Test LOWER case" ],
            [ "toLowerCase", "test-lower-case", "TEST-Lower-Case" ],

            [ "toCamelCase", "testCamelCase", "test CAMEL case" ],
            [ "toCamelCase", "testCamelCase", "test/camel-case" ],
            [ "toCamelCase", "testCamelCase", "testCamelCase" ],
            [ "toCamelCase", "testCamelCase", "TEST CAMEL CASE" ],

            [ "toFullCamelCase", "TestFullCamelCase", "test Full CAMEL case" ],
            [ "toFullCamelCase", "TestFullCamelCase", "test/full,camel-case" ],
            [ "toFullCamelCase", "TestFullCamelCase", "TestFullCamelCase" ],
            [ "toFullCamelCase", "TestFullCamelCase", "TEST FULL CAMEL CASE" ],

            [ "toKey", "test_key", "--test/key--" ],
            [ "toKey", "test_key", "/#/test/key" ],
            [ "toKey", "test.key", "_test.key_" ],

            [ "toSlug", "test-slug", "--test/slug--" ],
            [ "toSlug", "test-slug", "/#/test/slug" ],
            [ "toSlug", "test_slug", "_test_slug_" ],

            [ "toParam", "\\foo@bar", "\\\\foo@@bar" ],
            [ "toParam", "<foobar/>", "&lt;foobar/&gt;" ],
            [ "toParam", "?foo=bar&x=y", "?foo=bar&amp;x=y" ],

            [ "toSqlName", "SELECT `foo`.`bar` AS `baz` FROM `db`.`table`", "SELECT foo.bar AS baz FROM db.table" ],
            [ "toSqlName", "SELECT COUNT(`id`) FROM `db`.`tb`", "SELECT COUNT(id) FROM db.tb" ],
            [ "toSqlName", "WHERE `tb`.`foo` = :foo", "WHERE tb.foo = :foo" ],
            [ "toSqlName", "SET `a`.`foo`='1', `b`.`bar`=:baz", "SET a.foo='1', b.bar=:baz" ],

            [ "toPath", "/foo/bar", "\\foo\\bar\\" ],
            [ "toPath", "/foo/bar", "//foo//bar//" ],
            [ "toPath", "C:/foo/bar", "C:\\foo\\bar\\" ],
            [ "toPath", "", "/" ],

            [ "toExtension", "php", "/file/foo.bar.php" ],
            [ "toExtension", "exe", "some-file-[name].exe" ],
            [ "toExtension", "txt", "some text file.foo.txt" ],
            [ "toExtension", "htaccess", ".htaccess" ],

            [ "toTitle", "A (Test) Title [FOO]", "A (Test) Title [FOO] .txt" ],

            [ "toName", "Full Name", "Full (Name)" ],
            [ "toName", "Full-Name", "Full-Name" ],
            [ "toName", "Full O'Name", "Full O',Name?" ],

            [ "toPhone", "555-123-4567", "555.123.4567" ],
            [ "toPhone", "+1-555-123-4567", "+1 555 123 4567" ],

            [ "toEmail", "foo.bar@email.com", "foo.<bar>@email.com" ],
            [ "toEmail", "foobar@email.com", "foo bar @@@ email.com" ],

            [ "toUrl", "http://foo-bar.com/", "http://foo-bar.com/<script type=\"foo\">" ],
            [ "toUrl", "https://www.foo-bar.net/a/b/?a=1&b=2#foo", "https://www.foo-bar.net/a/b/?a=1&b=2#foo" ],
            [ "toUrl", "file://foo/bar.txt", "file:\\\\foo\\bar.txt" ],

            [ "toHostname", "localhost", "localhost" ],
            [ "toHostname", "localhost", "http://localhost/" ],
            [ "toHostname", "127.0.0.1", "//127.0.0.1/foo/bar" ],
            [ "toHostname", "www.foobar.com", "https://www.foobar.com/#hash" ],

            [ "toHandle", "@handle", "handle" ],
            [ "toHandle", "@foobar", "@foo-bar" ],
            [ "toHandle", "@foo_bar1", "@foo_bar1" ],
            [ "toHandle", "@" ],

            [ "toHashtag", "#hashtag", "hashtag" ],
            [ "toHashtag", "#foobar", "#foo-bar" ],
            [ "toHashtag", "#foo_bar1", "#foo_bar1" ],
            [ "toHashtag", "#" ],

            [ "toIp", "192.168.0.1", "192.168.0.1/24" ],
            [ "toIp", "192.168.0.100", "192.168.0.100:8080" ],
            [ "toIp", "192.168.0.100", "http://192.168.0.100:80/?foo=bar" ],
            [ "toIp", "", "http://foo.com/" ],
            [ "toIp", "", "localhost" ],

            [ "toIpv6", "2001:cdba:0000:0000:0000:0000:3257:9652", "http://[2001:cdba:0000:0000:0000:0000:3257:9652]/" ],
            [ "toIpv6", "2001:cdba::3257:9652", "2001:cdba::3257:9652" ],
        ]);
    }
}
