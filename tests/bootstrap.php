<?php
error_reporting( -1 );
ini_set( "log_errors", 0 );
ini_set( "display_errors", 1 );
ini_set( "display_startup_errors", 1 );
ini_set( "default_charset", "UTF-8" );
ini_set( "date.timezone", "UTC" );

define( "BASE", rtrim( str_replace( "\\", "/", dirname( __DIR__ ) ), "/" ) );

require( BASE . "/vendor/autoload.php" );
require( BASE . "/tests/TestCase.php" );