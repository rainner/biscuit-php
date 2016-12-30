<?php
/**
 * Home controller file used by Biscuit\Http\Router.
 */

$this->action( "GET", "test", function( $a="", $b="" )
{
    return $a . $b; // 'foobar'
});
