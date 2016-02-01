<?php
/**
 * Converts data rows into multi-level data tree arrays.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Data;

class Tree {

    // external data
    protected $data = array();

    /**
     * Constructor
     */
    public function __construct( $data=array() )
    {
        $this->seed( $data );
    }

    /**
     * Seed this class with some external data (rows)
     */
    public function seed( $data=array() )
    {
        if( !empty( $data ) && is_array( $data ) )
        {
            foreach( $data as $row )
            {
                if( empty( $row['id'] ) || !isset( $row['parent_id'] ) ) continue;

                $this->data[ $row['parent_id'] ][] = $row;
            }
        }
        return $this;
    }

    /**
     * Export data as a multi-dimensional HTML list menu
     */
    public function getMenu( $parent=0, $active=null, $link='#item-' )
    {
        $html = '';

        if( isset( $this->data[ $parent ] ) )
        {
            $html .= '<ul>';

            foreach( $this->data[ $parent ] as $row )
            {
                $a = ( is_numeric( $active ) && $active == $row['id'] ) ? ' class="active"' : '';

                $html .= '<li>';
                $html .= '<a'.$a.' href="'.$link.$row['id'].'">' . $row['name']. '</a>';
                $html .= $this->getMenu( $row['id'], $active, $link );
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }


}





