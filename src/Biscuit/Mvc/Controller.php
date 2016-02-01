<?php
/**
 * Abstract controller class to be extended by area controllers.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Mvc;

abstract class Controller {

    // dependency injection container
    protected $_container = null;

    /**
	 * Constructor
	 */
	public function __construct()
	{
        // void
	}

    /**
     * Pass new object to container
     */
    public function __set( $name='', $object=null )
    {
        if( $this->_container instanceof Container )
        {
            return $this->_container->setObject( $name, $object );
        }
        return false;
    }

    /**
     * Returns an object from container
     */
    public function __get( $name='' )
    {
        if( $this->_container instanceof Container )
        {
            return $this->_container->getObject( $name );
        }
        return null;
    }

    /**
     * Set the container object
     */
    public function setContainer( Container $container )
    {
        $this->_container = $container;
    }

    /**
     * Returns the local container object
     */
    public function getContainer()
    {
        return $this->_container;
    }

}



