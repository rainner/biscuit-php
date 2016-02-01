<?php
/**
 * Handles working with a file.
 *
 * @author     Rainner Lins | http://rainnerlins.com
 * @license    See: /docs/license.txt
 * @copyright  All Rights Reserved
 */
namespace Biscuit\Storage;

use Biscuit\Util\Sanitize;
use Biscuit\Util\Numeric;
use Biscuit\Util\Utils;

class File extends FsItem implements FsInterface {

    /**
     * Createa the current file
     */
    public function create( $chmod=0775 )
    {
        $path = $this->getPath();

        if( is_file( $path ) !== true && $this->write() )
        {
            chmod( $path, $chmod );
            return true;
        }
        return true;
    }

    /**
     * Write data to a file
     */
    public function write( $data='' )
    {
        $path   = $this->getPath();
        $parent = $this->getParent();
        $ext    = $this->getExtension();
        $data   = Sanitize::toString( $data );
        $output = false;

        if( !empty( $path ) && !empty( $parent ) && !empty( $ext ) )
        {
            if( is_dir( $parent ) || mkdir( $parent, 0777, true ) )
            {
                $stream = fopen( $path, 'wb' );
                $output = fwrite( $stream, $data );
                fclose( $stream );
            }
        }
        return $output;
    }

    /**
     * Read data from a file
     */
    public function read()
    {
        $path   = $this->getPath();
        $output = '';

        if( is_file( $path ) )
        {
            $stream = fopen( $path, 'rb' );

            while( !feof( $stream ) )
            {
                $output .= fread( $stream, 8192 );
            }
            fclose( $stream );
        }
        return $output;
    }

    /**
     * Rename file with new path name
     */
    public function rename( $newpath='' )
    {
        $path    = $this->getPath();
        $newpath = Sanitize::toPath( $newpath );
        $parent  = dirname( $newpath );
        $output  = false;

        if( is_file( $path ) && !empty( $newpath ) )
        {
            if( is_dir( $parent ) || mkdir( $parent, 0777, true ) )
            {
                $output = rename( $path, $newpath );
            }
        }
        return $output;
    }

    /**
     * Copy existing file to another location
     */
    public function copy( $newpath='' )
    {
        $path    = $this->getPath();
        $newpath = Sanitize::toPath( $newpath );
        $parent  = dirname( $newpath );
        $output  = false;

        if( is_file( $path ) && !empty( $newpath ) )
        {
            if( is_dir( $parent ) || mkdir( $parent, 0777, true ) )
            {
                $strin  = fopen( $path, "rb" );
                $strout = fopen( $newpath, "wb" );
                $output = stream_copy_to_stream( $strin, $strout );

                fclose( $strin );
                fclose( $strout );
            }
        }
        return $output;
    }

    /**
     * Move file to new location
     */
    public function move( $newpath='' )
    {
        if( $this->rename( $newpath ) )
        {
            $this->setPath( $newpath );
            return true;
        }
        return false;
    }

    /**
     * Delete current file
     */
    public function delete()
    {
        $path = $this->getPath();

        if( is_file( $path ) )
        {
            return unlink( $path );
        }
        return true;
    }

    /**
     * Delete file contents
     */
    public function flush()
    {
        return $this->write( '' );
    }

    /**
     * Get current file bytes with support for large files in 32bit OS envs
     */
    public function getSize()
    {
        $path   = $this->getPath();
        $output = 0;

        if( is_file( $path ) )
        {
            $size   = 1073741824;
            $stream = fopen( $path, 'rb' );

            fseek( $stream, 0, SEEK_SET );

            while( $size > 1 )
            {
                fseek( $stream, $size, SEEK_CUR );

                if( fgetc( $stream ) === false )
                {
                    fseek( $stream, -$size, SEEK_CUR );
                    $size = (int)( $size / 2 );
                    continue;
                }
                fseek( $stream, -1, SEEK_CUR );
                $output += $size;
            }
            while( fgetc( $stream ) !== false )
            {
                $output++;
            }
            fclose( $stream );
        }
        return $output;
    }

    /**
     * Get file size in human readable format
     */
    public function getByteSite()
    {
        return Numeric::toSize( $this->getSize() );
    }

}