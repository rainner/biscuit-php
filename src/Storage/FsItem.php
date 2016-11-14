<?php
/**
 * Represents an item (folder/file) on the filesystem.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Storage;

use Biscuit\Utils\Sanitize;
use Biscuit\Utils\Utils;

class FsItem {

    // path of current item (folder/file)
    protected $_path = "";

    // dirname of current item
    protected $_parent = "";

    // file name of current item
    protected $_name = "";

    // file extension of current item
    protected $_extension = "";

    // local cache
    protected $_cache = array();

    /**
     * Constructor
     */
    public function __construct( $path="" )
    {
        $this->setPath( $path );
    }

    /**
     * Set current item path and resolve item details
     */
    public function setPath( $path="" )
    {
        $path   = Sanitize::toPath( $path );
        $output = pathinfo( $path );

        $this->_path      = $path;
        $this->_parent    = Utils::value( @$output["dirname"], "" );
        $this->_name      = Sanitize::toTitle( Utils::value( @$output["basename"], "" ) );
        $this->_extension = Sanitize::toLowerCase( Utils::value( @$output["extension"], "" ) );
    }

    /**
     * Checks if the item exists
     */
    public function exists()
    {
        return file_exists( $this->_path );
    }

    /**
     * Get the item path
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Get the item path
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Get the item name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get the item filename
     */
    public function getFileName()
    {
        if( !empty( $this->_extension ) )
        {
            return $this->_name .".". $this->_extension;
        }
        return $this->_name;
    }

    /**
     * Get the item filename without spaces
     */
    public function getSafeName()
    {
        if( !empty( $this->_extension ) )
        {
            return Sanitize::toSlug( $this->_name ) .".". $this->_extension;
        }
        return $this->_name;
    }

    /**
     * Get the item file extension
     */
    public function getExtension( $default="" )
    {
        if( !empty( $this->_extension ) )
        {
            return $this->_extension;
        }
        return $default;
    }

    /**
     * Get the item path type (folder/file)
     */
    public function getType( $default="" )
    {
        $path = $this->getPath();

        if( file_exists( $path ) )
        {
            return is_dir( $path ) ? "folder" : "file";
        }
        return $default;
    }

    /**
     * Get the item permissions string
     */
    public function getPermissions( $octet=false )
    {
        $path   = $this->getPath();
        $output = "-";

        if( file_exists( $path ) )
        {
            $perms  = fileperms( $path );
            $chmod  = $perms ? decoct( $perms & 0777 ) : 755;
            $output = "u";

            // type
            if( ( $perms & 0xC000 ) == 0xC000 ){ $output = "s"; } else // Socket
            if( ( $perms & 0xA000 ) == 0xA000 ){ $output = "l"; } else // Symbolic Link
            if( ( $perms & 0x8000 ) == 0x8000 ){ $output = "-"; } else // Regular
            if( ( $perms & 0x6000 ) == 0x6000 ){ $output = "b"; } else // Block special
            if( ( $perms & 0x4000 ) == 0x4000 ){ $output = "d"; } else // Directory
            if( ( $perms & 0x2000 ) == 0x2000 ){ $output = "c"; } else // Character special
            if( ( $perms & 0x1000 ) == 0x1000 ){ $output = "p"; }      // FIFO pipe

            // owner
            $output .= ( ( $perms & 0x0100 ) ? "r" : "-" );
            $output .= ( ( $perms & 0x0080 ) ? "w" : "-" );
            $output .= ( ( $perms & 0x0040 ) ? ( ( $perms & 0x0800 ) ? "s" : "x" ) : ( ( $perms & 0x0800 ) ? "S" : "-" ) );

            // group
            $output .= ( ( $perms & 0x0020 ) ? "r" : "-" );
            $output .= ( ( $perms & 0x0010 ) ? "w" : "-" );
            $output .= ( ( $perms & 0x0008 ) ? ( ( $perms & 0x0400 ) ? "s" : "x" ) : ( ( $perms & 0x0400 ) ? "S" : "-" ) );

            // world
            $output .= ( ( $perms & 0x0004 ) ? "r" : "-" );
            $output .= ( ( $perms & 0x0002 ) ? "w" : "-" );
            $output .= ( ( $perms & 0x0001 ) ? ( ( $perms & 0x0200 ) ? "t" : "x" ) : ( ( $perms & 0x0200 ) ? "T" : "-" ) );

            if( $octet === true )
            {
                $output .= " (".$chmod.")";
            }
        }
        return $output;
    }

    /**
     * Get the item owner name
     */
    public function getOwner( $default="" )
    {
        $path   = $this->getPath();
        $output = "";

        if( file_exists( $path ) )
        {
            $uid = Utils::value( @fileowner( $path ), 0 );

            // try from cache
            if( empty( $output ) && !empty( $this->_cache[ "username" ][ $uid ] ) )
            {
                $output = $this->_cache[ "username" ][ $uid ];
            }
            // try using posix_getpwuid
            if( empty( $output ) && function_exists( "posix_getpwuid" ) )
            {
                $pwuid = @posix_getpwuid( $uid );
                $output = Utils::value( @$pwuid["name"], "" );
                $output = Sanitize::toName( $output );
            }
            // try using system stat command
            if( empty( $output ) && function_exists( "system" ) )
            {
                @ob_start();
                @system( "stat -c %U ".$path );
                $output = Sanitize::toName( @ob_get_clean() );
            }
            // try using system ls command
            if( empty( $output ) && function_exists( "system" ) )
            {
                @ob_start();
                @system( "ls -ld ".$path );
                @list( , , $output, ) = explode( " ", @ob_get_clean(), 4 );
                $output = Sanitize::toName( $output );
            }
        }
        if( !empty( $output ) )
        {
            $this->_cache[ "username" ][ $uid ] = $output;
            return $output;
        }
        return $default;
    }

    /**
     * Get the item mime/content-type string
     */
    public function getMimeType( $default="" )
    {
        $path   = $this->getPath();
        $ext    = $this->getExtension( "none" );
        $output = "";

        if( is_dir( $path ) )
        {
            $output = "inode/directory";
        }
        else if( is_file( $path ) && !empty( $ext ) )
        {
            // try from cache
            if( empty( $output ) && !empty( $this->_cache[ "mimetype" ][ $ext ] ) )
            {
                $output = $this->_cache[ "mimetype" ][ $ext ];
            }
            // try using finfo_open
            if( empty( $output ) && function_exists( "finfo_open" ) )
            {
                $finfo  = @finfo_open( @FILEINFO_MIME_TYPE );
                $output = Sanitize::toPath( @finfo_file( $finfo, $path ) );
                @finfo_close( $finfo );
            }
            // try using system file command
            if( empty( $output ) && function_exists( "system" ) )
            {
                @ob_start();
                @system( "file -bi ".escapeshellarg( $path ) );
                $output = Sanitize::toPath( preg_replace( "/\;.*$/ui", "", @ob_get_clean() ) );
            }
            // try using exif_imagetype
            if( empty( $output ) && function_exists( "exif_imagetype" ) && ( $imgtype = @exif_imagetype( $path ) ) )
            {
                $output = Sanitize::toPath( @image_type_to_mime_type( $imgtype ) );
            }
        }
        if( !empty( $output ) )
        {
            $this->_cache[ "mimetype" ][ $ext ] = $output;
            return $output;
        }
        return $default;
    }

    /**
     * Get a common file category type out of the mimetype string
     */
    public function getCategory( $default="file" )
    {
        $mime   = $this->getMimeType();
        $types  = explode( "/", $mime );
        $output = Sanitize::toSlug( array_pop( $types ) );
        return !empty( $output ) ? $output : $default;
    }

    /**
     * Get array of item timestamps
     */
    public function getTimestamps()
    {
        $path = $this->getPath();
        $now  = time();

        $created  = Utils::value( @filectime( $path ), $now );
        $modified = Utils::value( @filemtime( $path ), $now );
        $accessed = Utils::value( @fileatime( $path ), $now );

        return array(
            "created"  => min( $created, $modified, $accessed ),
            "modified" => max( $created, $modified ),
            "accessed" => max( $modified, $accessed ),
        );
    }

    /**
     * Get the item mime/content-type string
     */
    public function getInfo()
    {
        $time  = $this->getTimestamps();
        $title = Sanitize::toTitle( $this->_name );
        $title = Sanitize::toCaps( $title );

        return array(

            "path"      => $this->_path,
            "parent"    => $this->_parent,
            "extension" => $this->_extension,
            "name"      => $this->_name,
            "filename"  => $this->getFileName(),
            "type"      => $this->getType(),
            "perms"     => $this->getPermissions(),
            "owner"     => $this->getOwner(),
            "mimetype"  => $this->getMimeType(),
            "category"  => $this->getCategory(),
            "title"     => $title,
            "created"   => $time["created"],
            "modified"  => $time["modified"],
            "accessed"  => $time["accessed"],
            "writable"  => is_writable( $this->_path ),
        );
    }

}