<?php
/**
 * Tests
 */
class UtilsNumericTest extends TestCase {

    public function testClassMethods()
    {
        $format   = "m/d/Y";
        $now      = time();
        $onehour  = strtotime( "+1 hour" );
        $oneday   = strtotime( "+1 day" );
        $oneyear  = strtotime( "+1 year" );
        $today    = date( $format, $now );
        $tomorrow = date( $format, $oneday );

        $this->runTests( "Biscuit\\Utils\\Numeric", [

            [ "toBytes", "1 Kb",    1024, 0, "b" ],
            [ "toBytes", "1.00 KB", 1024, 2, "B" ],
            [ "toBytes", "0 b" ],

            [ "toNoun", "0 entries", 0, "entry", "entries" ],
            [ "toNoun", "1 entry",   1, "entry", "entries" ],
            [ "toNoun", "2 entries", 2, "entry", "entries" ],
            [ "toNoun", "0 items" ],

            [ "toRounded", 6, 5.675 ],
            [ "toRounded", 5, 5.475 ],
            [ "toRounded", 0 ],

            [ "toPennies", 100, 1.00 ],
            [ "toPennies", 125, "$1.25" ],

            [ "toPercent", "50%",    50,        100, 0, "%" ],
            [ "toPercent", "45.55%", 45.554321, 100, 2, "%" ],
            [ "toPercent", "0.0%" ],

            [ "toCurrency", "$1.00",     1,    ".", ",", "$" ],
            [ "toCurrency", "$100.00",   100,  ".", ",", "$" ],
            [ "toCurrency", "$1,000.00", 1000, ".", ",", "$" ],
            [ "toCurrency", "$0.00" ],

            [ "toZip5", "11111", "11111" ],
            [ "toZip5", "11111", "11111-2222" ],
            [ "toZip5", "" ],

            [ "toZip4", "",     "11111" ],
            [ "toZip4", "2222", "11111-2222" ],
            [ "toZip4", "" ],

            [ "padZero", "0012", 12,       2 ],
            [ "padZero", "0100", 100,      1 ],
            [ "padZero", "0100", "000100", 1 ],
            [ "padZero", "" ],

            [ "unpadZero", 100,  "0100" ],
            [ "unpadZero", 1000, "0001000" ],
            [ "unpadZero", 12,   12 ],
            [ "unpadZero", 0 ],

            [ "toTimestamp", $onehour,  "+1 hour" ],
            [ "toTimestamp", $onehour,  $onehour ],
            [ "toTimestamp", $now,      time() ],
            [ "toTimestamp", $now ],

            [ "toDate", date( $format, $now ),    $today,    $format, "Never" ],
            [ "toDate", date( $format, $oneday ), $tomorrow, $format, "Never" ],
            [ "toDate", date( $format, $oneday ), "+1 day",  $format, "Never" ],
            [ "toDate", "Never" ],

            [ "toElapsed", "just now",               $now ],
            [ "toElapsed", "less than a minute ago", $now - 30 ],
            [ "toElapsed", "1 day ago",              "-1 day" ],
            [ "toElapsed", "2 months ago",           "-2 months" ],
            [ "toElapsed", "just now" ],

            [ "toCountdown", "5 minutes",  $now,      60 * 5 ],
            [ "toCountdown", "1 hour",     $now,      60 * 60 ],
            [ "toCountdown", "0 seconds",  $now,      0 ],
            [ "toCountdown", "0 seconds",  "-1 hour", 60 * 60 ],
            [ "toCountdown", "0 seconds" ],

            [ "toAge", "1 year old",   "-1 year" ],
            [ "toAge", "20 years old", strtotime( "-20 years" ) ],
            [ "toAge", "0 years old" ],
        ]);
    }
}