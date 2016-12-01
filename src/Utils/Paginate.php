<?php
/**
 * For working with page navigation numbers.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Utils;

class Paginate {

    protected $_page   = 1;    // current page
    protected $_pages  = 0;    // total calculated pages
    protected $_total  = 0;    // total number of records
    protected $_limit  = 10;   // records per page
    protected $_offset = 0;    // records offset
    protected $_first  = 1;    // first calculated page
    protected $_last   = 0;    // last calculated page
    protected $_route  = "";   // url path route for links
    protected $_params = [];   // url params

    /**
     * Constructor
     */
    public function __construct( $page=1, $total=0, $limit=10, $route="" )
    {
        $this->setPage( $page );
        $this->setTotal( $total );
        $this->setLimit( $limit );
        $this->setRoute( $route );
        $this->setParams( @$_GET );
    }

    /**
     * Set the current page
     */
    public function setPage( $page )
    {
        if( is_numeric( $page ) )
        {
            $this->_page = intval( $page );
        }
    }

    /**
     * Get the current page
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * Get the current page's offset number
     */
    public function getOffset()
    {
        return $this->_pageOffset( $this->_page );
    }

    /**
     * Set total number of records
     */
    public function setTotal( $total )
    {
        if( is_numeric( $total ) )
        {
            $this->_total = intval( $total );
        }
        $this->_calculateLastPage();
    }

    /**
     * Get total number of records
     */
    public function getTotal()
    {
        return $this->_total;
    }

    /**
     * Set limit records per page
     */
    public function setLimit( $limit )
    {
        if( is_numeric( $limit ) )
        {
            $this->_limit = intval( $limit );
        }
        $this->_calculateLastPage();
    }

    /**
     * Get limit records per page
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * Set url path route
     */
    public function setRoute( $route )
    {
        if( is_string( $route ) )
        {
            $this->_route = rtrim( trim( $route ), "/" );
        }
    }

    /**
     * Get url path route
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * Set url params (_GET)
     */
    public function setParams( $params )
    {
        if( is_array( $params ) )
        {
            unset( $params["page"] ); // if available
            $this->_params = $params;
        }
    }

    /**
     * Get url params data
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Get URL route and params for a page
     */
    public function getUrl( $page=null )
    {
        $params = $this->_params;

        if( is_numeric( $page ) )
        {
            $params["page"] = intval( $page );
        }
        return $this->_route."/?".http_build_query( $params );
    }

    /**
     * Get first page number
     */
    public function getFirstPage()
    {
        return $this->_first;
    }

    /**
     * Get first page offset
     */
    public function getFirstOffset()
    {
        return $this->_pageOffset( $this->_first );
    }

    /**
     * Get first last number
     */
    public function getLastPage()
    {
        return $this->_last;
    }

    /**
     * Get first last offset
     */
    public function getLastOffset()
    {
        return $this->_pageOffset( $this->_last );
    }

    /**
     * Get the previous page number
     */
    public function getPreviousPage()
    {
        return ( $this->_page > $this->_first ) ? ( $this->_page - 1 ) : $this->_first;
    }

    /**
     * Get the previous page offset
     */
    public function getPreviousOffset()
    {
        return $this->_pageOffset( $this->getPreviousPage() );
    }

    /**
     * Get the next page number
     */
    public function getNextPage()
    {
        return ( $this->_page < $this->_last ) ? ( $this->_page + 1 ) : $this->_last;
    }

    /**
     * Get the next page offset
     */
    public function getNextOffset()
    {
        return $this->_pageOffset( $this->getNextPage() );
    }

    /**
     * Get total number of records
     */
    public function getTotalPages()
    {
        return ( $this->_total > $this->_limit ) ? ceil( $this->_total / $this->_limit ) : 1;
    }

    /**
     * Get SQL LIMIT condition for current page
     */
    public function getSqlClause()
    {
        return "LIMIT ".$this->getOffset().",".$this->getLimit();
    }

    /**
     * Get list of page html links
     */
    public function getLinks( $max=7, $default="Page 1/1" )
    {
        $list  = "";
        $data  = $this->getData( $max );
        $prev  = $this->_pageData( $data["previous"], "«", "pg-prev" );
        $next  = $this->_pageData( $data["next"], "»", "pg-next" );
        $total = count( $data["list"] );

        if( $total > 1 )
        {
            if( $data["page"] > 1 )
            {
                $list .= '<li>'.$prev["link"].'</li>';
            }
            foreach( $data["list"] as $page )
            {
                $active = !empty( $page["active"] ) ? "active" : "";
                $list .= '<li class="'.$active.'">'.$page["link"].'</li>';
            }
            if( $data["page"] < $data["pages"] )
            {
                $list .= '<li>'.$next["link"].'</li>';
            }
        }
        else {
            $list .= '<li><a class="disabled" href="#">'.$default.'</a></li>';
        }
        return '<ul class="pagination pg-wrap">' . $list . '</ul>';
    }

    /**
     * Get pagination data
     */
    public function getData( $max=null )
    {
        $list   = array();
        $page   = $this->getPage();
        $pages  = $this->getTotalPages();
        $offset = $this->getOffset();
        $limit  = $this->getLimit();
        $first  = $this->getFirstPage();
        $last   = $this->getLastPage();
        $prev   = $this->getPreviousPage();
        $next   = $this->getNextPage();
        $max    = is_numeric( $max ) ? intval( $max ) : 7; // max links to show
        $start  = ( $page >= $max ) ? ceil( $page - $max / 2 ) : 1;
        $end    = ( $start > 1 ) ? ( $start + $max ) : $max + 1;

        // add first link
        if( $page >= $max )
        {
            $list[] = $this->_pageData( 1, '1..', "pg-first" );
        }
        // add middle pages
        if( $pages > 1 )
        {
            for( $i = 1; $i <= $pages; $i++ )
            {
                if( $i >= $start && $i < $end )
                {
                    $list[] = $this->_pageData( $i, $i, "pg-page" );
                }
            }
        }
        // add last link
        if( $end <= $pages )
        {
            $list[] = $this->_pageData( $pages, '..'.$pages, "pg-last" );
        }

        // return data
        return array(
            "first"    => $first,
            "last"     => $last,
            "page"     => $page,
            "pages"    => $pages,
            "offset"   => $offset,
            "limit"    => $limit,
            "previous" => $prev,
            "next"     => $next,
            "list"     => $list,
        );
    }

    /**
     * Calculate the offset for a page
     */
    private function _pageOffset( $page=1 )
    {
        $page = is_numeric( $page ) ? intval( $page ) : 1;
        return ( $page > 0 ) ? ( ( $page - 1 ) * $this->_limit ) : 0;
    }

    /**
     * Build data for a page number
     */
    private function _pageData( $page=1, $display=null, $class=null )
    {
        $url     = $this->getUrl( $page );
        $offset  = $this->_pageOffset( $page );
        $active  = ( $this->_page === $page ) ? 1 : 0;
        $display = is_string( $display ) ? trim( $display ) : trim( $page );

        $classes = [];
        if( is_string( $class ) ) $classes[] = trim( $class );
        if( $active === 1 ) $classes[] = "active";

        return array(
            "page"    => $page,
            "offset"  => $offset,
            "active"  => $active,
            "url"     => $url,
            "display" => $display,
            "link"    => '<a class="'.implode( " ", $classes ).'" href="'.$url.'">'.$display."</a>",
        );
    }

    /**
     * Calculate the last page number
     */
    private function _calculateLastPage()
    {
        $this->_last = ( $this->_total > $this->_limit ) ? ( $this->_total - $this->_limit ) : $this->_total;
    }

}