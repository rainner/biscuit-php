<?php
/**
 * Gravatar helper class.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Util;

class Gravatar {

    /**
     * Grab a user's Gravatar image for a specified email
     */
    public static function getUrl( $email='', $default='' )
    {
        $email   = strtolower( trim( $email ) );
        $default = !empty( $default ) ? urlencode( $default ) : 'mm';

        return 'http://www.gravatar.com/avatar/'.md5( $email ).'?'.http_build_query( array(
            's' => '200',
            'r' => 'x',
            'd' => $default
        ));
    }
}