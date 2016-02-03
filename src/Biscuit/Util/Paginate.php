<?php
/**
 * For working with page navigation numbers
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Util;

class Paginate {

    /**
     * Get a page's limit offset value
     * @param  integer $page  Current page number
     * @param  integer $limit Limit of items per page
     * @return integer        Page offset value
     */
    public static function pageOffset( $page=1, $limit=30 )
    {
        $page  = intval( $page );
        $limit = intval( $limit );
        return ( $page > 0 ) ? ( ( $page - 1 ) * $limit ) : 0;
    }

    /**
     * Build link data for a page number
     * @param  integer $page    Page number
     * @param  integer $offset  Page offset number
     * @param  integer $current Current selected page number
     * @param  array   $args    List of URL GET arguments
     * @param  string  $prefix  URL path prefix
     * @return array
     */
    public static function pageNumberData( $page=1, $offset=0, $current=0, $args=array(), $prefix='/' )
    {
        $active = ( $offset == $current ) ? 1 : 0;
        $class  = ( $offset == $current ) ? ' class="active"' : '';
        $query  = '?'.http_build_query( array_merge( $args, array( 'offset' => $offset ) ) );

        return array(
            'page'    => $page,
            'offset'  => $offset,
            'current' => $current,
            'active'  => $active,
            'query'   => $query,
            'link'    => '<a'.$class.' href="'.$prefix.$query.'">'.$page.'</a>'
        );
    }

    /**
     * Returns data that can be used to create navigational links for paginated lists
     * @param  integer $total   Total items in database
     * @param  integer $offset  Current starting offset
     * @param  integer $limit   How many items are listed per page
     * @param  integer $max     Only show max # of page links
     * @param  integer $prefix  Url PATH to prefix to new page query string
     * @return array
     */
    public static function pageNavData( $total=0, $offset=0, $limit=30, $max=7, $prefix='/' )
    {
        // calculate
        $list  = array();
        $page  = ( $offset >= $limit ) ? ( ceil( $offset / $limit ) + 1 ) : 1;
        $pages = ( $total > $limit ) ? ceil( $total / $limit ) : 1;
        $start = ( $page > $max ) ? ( $page - ( $max / 2 ) ) : 1;
        $end   = ( $start > 1 ) ? ( $start + $max ) : ( $max + 1 );
        $prev  = ( $offset >= $limit ) ? ( $offset - $limit ) : 0;
        $next  = ( $offset < $total ) ? ( $offset + $limit ) : $total;
        $last  = ( $total > $limit ) ? ( $total - $limit ) : $total;
        $first = 0;

        // remove offset key for GET args
        $args = $_GET;
        unset( $args['offset'] );

        // add first link
        if( $page > $max )
        {
            $list[] = self::pageNumberData( '1..', $first, $offset, $args, $prefix );
        }

        // add middle pages
        if( $pages > 1 )
        {
            for( $i=1; $i <= $pages; $i++ )
            {
                if( $i >= $start && $i < $end )
                {
                    $o = ( $i > 0 ) ? ( ( $i - 1 ) * $limit ) : 0;
                    $list[] = self::pageNumberData( $i, $o, $offset, $args, $prefix );
                }
            }
        }

        // add last link
        if( $page < ( $pages - $max ) )
        {
            $list[] = self::pageNumberData( '..'.$pages, $last, $offset, $args, $prefix );
        }

        // return data
        return array(

            'prev_offset'    => $prev,
            'next_offset'    => $next,
            'first_offset'   => $first,
            'last_offset'    => $last,
            'current_offset' => $offset,
            'current_page'   => $page,
            'total_pages'    => $pages,
            'total_items'    => $total,
            'total_limit'    => $limit,
            'list'           => $list
        );
    }

}