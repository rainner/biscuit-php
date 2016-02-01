<?php
/**
 * Provides a better way for working with the PHP _FILES array.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Http;

class Files {

    // props
    protected $maxsize = 0;
    protected $maxpost = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->maxsize = ini_get( 'upload_max_filesize' );
        $this->maxpost = ini_get( 'post_max_size' );
    }

    /**
     * Parses a given FILES array
     */
    public function parse()
    {
        $files = array();

        if( is_array( $_FILES ) && !empty( $_FILES ) )
        {
            // 1. convert files array into an URL encoded string
            $query  = urldecode( http_build_query( $_FILES, '', '&' ) );
            $lines  = explode( '&', $query );
            $param  = '';
            $build  = '';

            // 2. loop each key=value as individual line
            foreach( $lines as $line )
            {
                // 3. split line into separate key and value
                $parts = explode( '=', $line );
                $key   = isset( $parts[0] ) ? $parts[0] : '';
                $value = isset( $parts[1] ) ? $parts[1] : '';

                // 4. extract the fileinfo property from the key
                if( preg_match( '/(\[(name|type|tmp_name|error|size)\])/', $key, $prop ) === 1 )
                {
                    // 5. remove fileinfo prop from key
                    $key = str_replace( $prop[1], '', $key );

                    // 6. add a [0] to the end of key if dealing with a single file
                    if( preg_match( '/\[[0-9]+\]$/', $key ) !== 1 ) $key .= '[0]';

                    // 7. add the fileinfo prop to the end of key
                    $key .= $prop[1];

                    // 8. clean the value and add to final string
                    $param  = preg_replace( '/[^\w]+/', '', $prop[1] );
                    $value  = $this->_value( $param, $value );
                    $build .= $key .'='. $value .'&';
                }
            }
            // 9. convert the new encoded values back into an array
            if( !empty( $build ) )
            {
                parse_str( $build, $files );
            }
            // 10. add new array format to superglobal
            if( is_array( $files ) )
            {
                $_FILES = $files;
            }
        }
    }

    /**
     * Sanitizes a value for different param types
     */
    private function _value( $param='', $value='' )
    {
        if( $param === 'name' )
        {
            $value = trim( preg_replace( '/[^\w\-\.]+/i', '_', $value ) );
        }
        else if( $param === 'type' || $param === 'tmp_name' )
        {
            $value = trim( str_replace( '\\', '/', $value ) );
        }
        else if( $param === 'size' && is_numeric( $value ) )
        {
            $value = intval( $value );
        }
        else if( $param === 'error' && is_numeric( $value ) )
        {
            switch( intval( $value ) )
            {
                case UPLOAD_ERR_INI_SIZE   : $value = "The file size exceeds the (upload_max_filesize: ".$this->maxsize.") server limit."; break;
                case UPLOAD_ERR_FORM_SIZE  : $value = "The file size exceeds the (max_file_size: ".$this->maxpost.") http/post limit."; break;
                case UPLOAD_ERR_PARTIAL    : $value = "The file was only partially uploaded and could not be saved."; break;
                case UPLOAD_ERR_NO_FILE    : $value = "The server did not receive any file contents to be saved."; break;
                case UPLOAD_ERR_NO_TMP_DIR : $value = "The server had no temporary folder to store the file."; break;
                case UPLOAD_ERR_CANT_WRITE : $value = "The server does not have permission to copy the file contents."; break;
                case UPLOAD_ERR_EXTENSION  : $value = "A server script extension stopped the file upload."; break;
                default:                     $value = "";
            }
        }
        return $value;
    }

}
