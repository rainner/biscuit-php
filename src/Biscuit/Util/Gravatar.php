<?php
/**
 * Gravatar helper class.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
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