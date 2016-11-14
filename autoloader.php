<?php
/**
 * Autoloader
 */
spl_autoload_register( function( $class="" ) {
    $base  = rtrim( str_replace( "\\", "/", __DIR__ ), "/" ) ."/src";
    $class = rtrim( str_replace( "\\", "/", $class ), "/" );
    $file  = preg_replace( "/\/\/+/", "/", $base ."/". $class .".php" );
    is_file( $file ) && require_once( $file );
}, true );