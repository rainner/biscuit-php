<?php
/**
 * Biscuit autoloader.
 */
spl_autoload_register( function( $class='' )
{
    $base  = rtrim( str_replace( '\\', '/', __DIR__ ), '/' );
    $class = trim( str_replace( '\\', '/', $class ), '/' );
    $file  = realpath( $base .'/src/'. $class .'.php' );
    $file && require_once( $file );

}, true );