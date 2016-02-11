<?php
/**
 * Handles building nested data trees from a single data set.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Data;

use Closure;

class Tree {

    // external data to be used
    protected $data_set = array();

    // primary key name in data set
    protected $key_primary = 'id';

    // parent key name in data set
    protected $key_parent = 'parent_id';

    // callback used to build each item
    protected $cb_item = null;

    // callback used to build main container
    protected $cb_build = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cb_item  = function( $item ){ return ''; };
        $this->cb_build = function( $items ){ return ''; };
    }

    /**
     * Seed this class with some external data
     */
    public function setData( $data=array(), $key_primary='id', $key_parent='parent_id' )
    {
        $this->data_set    = array();
        $this->key_primary = $key_primary;
        $this->key_parent  = $key_parent;

        if( is_array( $data ) && !empty( $key_primary ) && !empty( $key_parent ) )
        {
            foreach( $data as $item )
            {
                if( !empty( $item[ $key_primary ] ) && isset( $item[ $key_parent ] ) )
                {
                    $this->data_set[ $item[ $key_parent ] ][] = $item;
                }
            }
        }
        return $this;
    }

    /**
     * Set the item builder closure
     */
    public function onItem( Closure $callback )
    {
        $this->cb_item = $callback->bindTo( $this );
    }

    /**
     * Set the container builder closure
     */
    public function onBuild( Closure $callback )
    {
        $this->cb_build = $callback->bindTo( $this );
    }

    /**
     * Build final output string
     */
    public function build( $parent=0, $depth=1, $indent=1 )
    {
        $items  = '';
        $output = '';

        if( isset( $this->data_set[ $parent ] ) )
        {
            foreach( $this->data_set[ $parent ] as $item )
            {
                $items .= call_user_func( $this->cb_item, $item, $depth, $indent );
                $items .= $this->build( @$item[ $this->key_primary ], $depth + 1, $indent + 2 );
            }
            $output .= call_user_func( $this->cb_build, $items, $depth, $indent );
        }
        return $output;
    }

    /**
     * Formats a single line
     */
    public function line( $indent=1, $line='' )
    {
        return "\n" . str_repeat( "\t", $indent ) . trim( $line );
    }

}

