<?php
/**
 * Handles the creation or parsing of XML data.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Data;

use Biscuit\Util\Sanitize;
use DOMDocument;

class Xml {

	// local properties
	protected $dom_obj  = null;
	protected $dom_node = null;

	/**
     * Constructor
     */
	public function __construct()
    {
		// void
    }

    /**
     * Checks if a given XML string is valid
     */
    public static function isXml( $string='' )
    {
        $output = false;

        if( !empty( $string ) && is_string( $string ) )
        {
            $string   = trim( $string );
            $internal = libxml_use_internal_errors( true );
            $object   = simplexml_load_string( $string );
            $errors   = libxml_get_errors();
            $output   = empty( $errors );

            libxml_clear_errors();
            libxml_use_internal_errors( $internal );
        }
        return $output;
    }

    /**
     * Parses XML string into an Object
     */
    public static function parse( $string='', $options=LIBXML_NOCDATA )
    {
        $output = null;

        if( !empty( $string ) && is_string( $string ) )
        {
            $string   = trim( $string );
            $internal = libxml_use_internal_errors( true );
            $output   = simplexml_load_string( $string, 'SimpleXMLElement', $options );

            libxml_clear_errors();
            libxml_use_internal_errors( $internal );
        }
        return $output;
    }

    /**
     * Starts a new DOMDocument XML object to be worked on
     */
    public function create( $type='', $version='', $encoding='' )
    {
        $type     = strtolower( trim( $type ) );
        $version  = !empty( $version )  ? trim( $version )  : '1.0';
        $encoding = !empty( $encoding ) ? trim( $encoding ) : 'utf-8';

        if( class_exists( 'DOMDocument' ) )
        {
            $this->dom_obj = new DOMDocument();
            $this->dom_obj->xmlVersion = $version;
            $this->dom_obj->encoding   = $encoding;

            $this->dom_node = $this->dom_obj;

            if( $type === 'rss' )
            {
                $this->node( 'rss', array( 'version' => '2.0' ) );
            }
        }
        return $this;
    }

    /**
     * Tries to build an XML output by importing an array
     */
    public function import( $data=array() )
    {
        if( is_array( $data ) )
        {
            foreach( $data as $key => $value )
            {
                if( empty( $key ) || is_numeric( $key ) ) continue;

                if( is_array( $value ) )
                {
                    $this->node( $key );
                    $this->import( $value );
                    $this->parent();
                }
                else
                {
                    $this->node( $key )->value( $value )->parent();
                }
            }
        }
        return $this;
    }

	/**
     * Add a new node to the DOMDocument object
     */
	public function node( $name='', $atts=array() )
	{
        $name = Sanitize::toSlug( $name );

        if( !empty( $this->dom_obj ) && !empty( $name ) )
        {
            $node = $this->dom_obj->createElement( $name );
            $this->dom_node->appendChild( $node );
            $this->dom_node = $node;

            if( is_array( $atts ) && count( $atts ) )
            {
                foreach( $atts as $key => $value )
                {
                    $key = Sanitize::toSlug( $key );

                    if( !empty( $key ) && !is_numeric( $key ) )
                    {
                        $this->dom_node->setAttribute( $key, trim( $value ) );
                    }
                }
            }
        }
        return $this;
    }

	/**
	 * Add string value to the current working node
	 */
	public function value( $value='', $cdata=false )
	{
        $value = trim( $value );

        if( !empty( $this->dom_node ) && !empty( $value ) )
        {
            if( $cdata === true )
            {
                $value = $this->dom_node->ownerDocument->createCDATASection( $value );
                $this->dom_node->appendChild( $value );
            }
            else{ $this->dom_node->nodeValue = $value; }
        }
        return $this;
    }

    /**
	 * Moves to a parent node
	 */
    public function parent( $key='' )
	{
        if( !empty( $this->dom_node ) )
        {
            if( !empty( $key ) )
            {
                // clone current node
                $cur = $this->dom_node;
                $key = Sanitize::toSlug( $key );

                // key is a number, go back number of times
                if( preg_match( '/^[0-9]+$/', $key ) )
                {
                    $key = (int) $key;
                    while( $key > 0 )
                    {
                        $cur = @$cur->parentNode;
                        if( empty( $cur ) ) break;
                        $this->dom_node = $cur;
                        $key--;
                    }
                }
                else
                {
                    // key is a string, find parent by name
                    while( $cur->parentNode )
                    {
                        $cur = @$cur->parentNode;
                        if( !empty( $cur->tagName ) && $cur->tagName == $key )
                        {
                            $this->dom_node = $cur;
                            break;
                        }
                    }
                }
            }
            else
            {
                // no key passed in, go to parent node
                $this->dom_node = $this->dom_node->parentNode;
            }
        }
        return $this;
    }

    /**
	 * Adds a channel node to the XML DOM, for when creating RSS feeds.
	 */
	public function channel( $pairs=array() )
	{
        $this->parent( 'rss' )->node( 'channel' );

        foreach( $pairs as $key => $value )
        {
            $key = Sanitize::toSlug( $key );
            if( empty( $key ) || is_numeric( $key ) ) continue;
            $this->node( $key )->value( $value )->parent();
        }
        return $this;
    }

    /**
	 * Adds a new item node to the XML DOM, for when creating RSS feeds.
	 */
	public function item( $pairs=array() )
	{
        $this->parent( 'channel' )->node( 'item' );

        foreach( $pairs as $key => $value )
        {
            $key = Sanitize::toSlug( $key );
            if( empty( $key ) || is_numeric( $key ) ) continue;
            $this->node( $key )->value( $value )->parent();
        }
        return $this;
    }

	/**
	 * End XML creation and resets the objects
	 */
	public function getXml()
	{
        if( !empty( $this->dom_obj ) )
        {
            $xml = $this->dom_obj->saveXML();
            $this->dom_obj  = null;
            $this->dom_node = null;
            return trim( $xml );
        }
        return '';
    }

	/**
	 * End XML creation and displays the final XML on the page
	 */
	public function showXml()
	{
        if( !empty( $this->dom_obj ) )
        {
            if( headers_sent() === false )
            {
                $rss = ( $this->type === 'rss' ) ? 'rss+' : '';
                header( 'Content-type: application/'.$rss.'xml' );
            }
            echo $this->getXML();
        }
    }

}

