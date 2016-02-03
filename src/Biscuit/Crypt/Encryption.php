<?php
/**
 * Handles encryption and decryption of data using OpenSSL.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Crypt;

use Biscuit\Util\Utils;
use Exception;

class Encryption {

    // encryption key to use
    protected $_cryptKey    = '';
    protected $_cryptMethod = 'AES-128-CBC';

    /**
     * Constructor
     */
    public function __construct( $key='', $method='' )
    {
        // try to resolve a key, will look for "CryptKey" in _ENV.
        $cryptKey = Utils::getValue( @$_ENV['CryptKey'], '' );
        $cryptKey = Utils::getValue( $key, $cryptKey );

        // try to resolve a method, will look for "CryptMethod" in _ENV.
        $cryptMethod = Utils::getValue( @$_ENV['CryptMethod'], '' );
        $cryptMethod = Utils::getValue( $method, $cryptMethod );

        // set inital values
        $this->setCryptKey( $cryptKey );
        $this->setCryptMethod( $cryptMethod );
    }

    /**
     * Set the encryption key to use
     */
    public function setCryptKey( $key='' )
    {
        if( !empty( $key ) && is_string( $key ) )
        {
            $this->_cryptKey = trim( $key );
        }
    }

    /**
     * Get the encryption key
     */
    public function getCryptKey()
    {
        return $this->_cryptKey;
    }

    /**
     * Set the encryption method to use
     */
    public function setCryptMethod( $method='' )
    {
        if( !empty( $method ) && is_string( $method ) )
        {
            $this->_cryptMethod = trim( $method );
        }
    }

    /**
     * Get the encryption method
     */
    public function getCryptMethod()
    {
        return $this->_cryptMethod;
    }

    /**
	 * Encrypt plaintext data
	 */
	public function encrypt( $plaintext='', $encode=true )
	{
        // checks
		if( function_exists( 'openssl_encrypt' ) !== true )
        {
            throw new Exception( 'The openssl_encrypt library is require for data encryption.' );
        }
        if( empty( $this->_cryptKey ) )
        {
            throw new Exception( 'The encryption key has not been set or found.' );
        }
        if( empty( $this->_cryptMethod ) )
        {
            throw new Exception( 'The encryption method has not been set or found.' );
        }

        // generate IV
        $secure = false;
        $ivlen  = openssl_cipher_iv_length( $this->_cryptMethod );
        $iv     = openssl_random_pseudo_bytes( $ivlen, $secure );

        // encrypt if IV is secure
        if( $secure )
        {
            $cipher = $iv . openssl_encrypt( $plaintext, $this->_cryptMethod, $this->_cryptKey, OPENSSL_RAW_DATA, $iv );
            return ( $encode === true ) ? base64_encode( $cipher ) : $cipher;
        }
		throw new Exception( 'The initialization vector algorithm used is not strong enough.' );
	}

    /**
	 * Decrypt ciphertext data
	 */
	public function decrypt( $cipher='', $decode=true )
	{
		if( function_exists( 'openssl_decrypt' ) !== true )
        {
            throw new Exception( 'The openssl_decrypt library is require for data decryption.' );
        }
        if( empty( $this->_cryptKey ) )
        {
            throw new Exception( 'The decryption key has not been set or found.' );
        }
        if( empty( $this->_cryptMethod ) )
        {
            throw new Exception( 'The decryption method has not been set or found.' );
        }

        // get IV from cipher
        $cipher = ( $decode === true ) ? base64_decode( $cipher ) : $cipher;
        $ivlen  = openssl_cipher_iv_length( $this->_cryptMethod );
        $iv     = substr( $cipher, 0, $ivlen );
        $cipher = substr( $cipher, $ivlen );

        // get decrypted string
		return openssl_decrypt( $cipher, $this->_cryptMethod, $this->_cryptKey, OPENSSL_RAW_DATA, $iv );
	}

}

