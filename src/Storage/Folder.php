<?php
/**
 * Handles working with a folder.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Storage;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Numeric;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class Folder extends FsItem implements FsInterface {

    /**
     * Createa the current folder
     */
    public function create( $chmod=0775 )
    {
        $path = $this->getPath();

        if( !empty( $path ) )
        {
            return ( is_dir( $path ) || mkdir( $path, $chmod, true ) );
        }
        return false;
    }

    /**
     * Create list of files from a given array with keys [path, data]
     */
    public function write( $files=array() )
    {
        $path   = $this->getPath();
        $output = 0;

        if( is_dir( $path ) && is_array( $files ) )
        {
            foreach( $files as $item )
            {
                if( !empty( $item["path"] ) )
                {
                    $file = new File( $path."/".$item["path"] );
                    $file->write( @$item["data"] ) && $output++;
                }
            }
            if( count( $files ) === $output )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Read list of items in current path
     */
    public function read()
    {
        $path   = $this->getPath();
        $output = array();

        if( is_dir( $path ) )
        {
            foreach( scandir( $path ) as $item )
            {
                if( $item === "." || $item === ".." ) continue;

                $output[] = new FsItem( $path."/".$item );
            }
        }
        return $output;
    }

    /**
     * Rename folder with new path name
     */
    public function rename( $newpath="" )
    {
        $path    = $this->getPath();
        $newpath = Sanitize::toPath( $newpath );
        $parent  = dirname( $newpath );
        $output  = false;

        if( is_dir( $path ) && !empty( $newpath ) )
        {
            if( is_dir( $parent ) || mkdir( $parent, 0777, true ) )
            {
                $output = rename( $path, $newpath );
            }
        }
        return $output;
    }

    /**
     * Copy folder to new location
     */
    public function copy( $newpath="" )
    {
        $path    = $this->getPath();
        $newpath = Sanitize::toPath( $newpath );
        $output  = 0;

        if( is_dir( $path ) && !empty( $newpath ) )
        {
            mkdir( $newpath, 0777, true );
            $stream = opendir( $path );

            while( false !== ( $item = @readdir( $stream ) ) )
            {
                if( $item === "." || $item === ".." ) continue;

                $from = Sanitize::toPath( $path."/".$item );
                $to   = Sanitize::toPath( $newpath."/".$item );

                if( is_dir( $from ) )
                {
                    $folder = new Folder( $from );
                    $folder->copy( $to ) && $output++;
                    continue;
                }
                if( is_file( $from ) )
                {
                    $file = new File( $from );
                    $file->copy( $to ) && $output++;
                    continue;
                }
            }
            @closedir( $stream );
        }
        return $output ? true : false;
    }

    /**
     * Move folder to new location
     */
    public function move( $newpath="" )
    {
        if( $this->rename( $newpath ) )
        {
            $this->setPath( $newpath );
            return true;
        }
        return false;
    }

    /**
     * Delete entire folder
     */
    public function delete( $keepdir=false )
    {
        $path   = $this->getPath();
        $output = 0;

        if( is_dir( $path ) )
        {
            $dir   = new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS );
            $items = new RecursiveIteratorIterator( $dir, RecursiveIteratorIterator::CHILD_FIRST );

            foreach( $items as $item )
            {
                $target = Sanitize::toPath( $item->getRealPath() );

                if( is_file( $target ) )
                {
                    unlink( $target ) && $output++;
                }
                else if( is_dir( $target ) )
                {
                    rmdir( $target ) && $output++;
                }
            }
            if( $keepdir !== true )
            {
                rmdir( $path );
            }
        }
        return $output ? true : false;
    }

    /**
     * Delete folder contents
     */
    public function flush()
    {
        return $this->delete( true );
    }

    /**
     * Get folder items list
     */
    public function getList()
    {
        $path   = $this->getPath();
        $output = array();

        if( is_dir( $path ) )
        {
            foreach( scandir( $path ) as $item )
            {
                if( $item === "." || $item === ".." ) continue;
                $output[] = Sanitize::toPath( $path."/".$item );
            }
        }
        return $output;
    }

    /**
     * Get folder items list (recursive)
     */
    public function getRecursiveList()
    {
        $path   = $this->getPath();
        $output = array();

        if( is_dir( $path ) )
        {
            $dir   = new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS );
            $items = new RecursiveIteratorIterator( $dir, RecursiveIteratorIterator::CHILD_FIRST );

            foreach( $items as $item )
            {
                $output[] = Sanitize::toPath( $item->getRealPath() );
            }
        }
        return $output;
    }

    /**
     * Get folder items count
     */
    public function getCount( $format=true, $append="" )
    {
        $count = count( $this->getList() );
        return ( $format ? number_format( $count ) : $count ) . $append;
    }

    /**
     * Get folder items count (recursive)
     */
    public function getRecursiveCount( $format=true, $append="" )
    {
        $count = count( $this->getRecursiveList() );
        return ( $format ? number_format( $count ) : $count ) . $append;
    }

    /**
     * Get folder items size
     */
    public function getSize()
    {
        $output = 0;

        foreach( $this->getList() as $item )
        {
            if( is_file( $item ) )
            {
                $output += (int) @filesize( $item );
            }
        }
        return $output;
    }

    /**
     * Get folder items size (recursive)
     */
    public function getRecursiveSize()
    {
        $output = 0;

        foreach( $this->getRecursiveList() as $item )
        {
            if( is_file( $item ) )
            {
                $output += (int) @filesize( $item );
            }
        }
        return $output;
    }

    /**
     * Get folder size in human readable format
     */
    public function getByteSite( $recursive=false )
    {
        $bytes = $recursive ? $this->getRecursiveSize() : $this->getSize();
        return Numeric::toBytes( $bytes );
    }

    /**
     * Delete old files, some hidden files, and empty folders
     */
    public function garbageCollect( $oldtime=0 )
    {
        $oldtime = is_numeric( $oldtime ) ? intval( $oldtime ) : 0;
        $output  = 0;

        foreach( $this->getRecursiveList() as $item )
        {
            if( is_dir( $item ) )
            {
                // delete empty directories
                if( ( new FilesystemIterator( $item ) )->valid() !== true )
                {
                    rmdir( $item ) && $output++;
                    continue;
                }
            }
            if( is_file( $item ) )
            {
                // delete junk files
                if( preg_match( "/\.(DS_Store)$/ui", basename( $item ) ) )
                {
                    unlink( $item ) && $output++;
                    continue;
                }
                // delete old files
                if( $oldtime > 0 && filemtime( $item ) < $oldtime )
                {
                    unlink( $item ) && $output++;
                    continue;
                }
            }
        }
        return $output;
    }

}