<?php
/**
 * Basic class for manipulating an image resource.
 *
 * @package Biscuit
 * @author Rainner Lins <rainnerlins@gmail.com>
 * @copyright 2016 Rainner Lins
 */
namespace Biscuit\Storage;

use Biscuit\Utils\Sanitize;
use Exception;

class Image {

    // input file string
    protected $img_file = "";

    // input file GD resource object
    protected $img_source = null;

    // input file image type
    protected $img_type = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        // void
    }

    /**
     * Set the original source image file to work with
     */
    public function load( $file="" )
    {
        $file = Sanitize::toPath( $file );

        if( !empty( $file ) && is_file( $file ) )
        {
            if( $imagetype = @exif_imagetype( $file ) )
            {
                $this->img_file   = $file;
                $this->img_type   = $imagetype;
                $this->img_source = null;

                if( $imagetype === IMAGETYPE_JPEG )
                {
                    $this->img_source = @imagecreatefromjpeg( $file );
                    return true;
                }
                if( $imagetype === IMAGETYPE_GIF )
                {
                    $this->img_source = @imagecreatefromgif( $file );
                    return true;
                }
                if( $imagetype === IMAGETYPE_PNG )
                {
                    $this->img_source = @imagecreatefrompng( $file );
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get GD image resource
     */
    public function getResource()
    {
        return $this->img_source;
    }

    /**
     * Get image width
     */
    public function getWidth()
    {
        if( $this->_checkResource() )
        {
            $output = imagesx( $this->img_source );
            return ( $output === false ) ? 0 : $output;
        }
        return 0;
    }

    /**
     * Get image height
     */
    public function getHeight()
    {
        if( $this->_checkResource() )
        {
            $output = imagesy( $this->img_source );
            return ( $output === false ) ? 0 : $output;
        }
        return 0;
    }

    /**
     * Resize image to a new size
     */
    public function resize( $width=0, $height=0 )
    {
        if( $this->_checkResource() )
        {
            $image = imagecreatetruecolor( $width, $height );
            imagealphablending( $image, true );
            imagesavealpha( $image, true );
            imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );
            imagecolortransparent( $image, imagecolorallocate( $image, 0, 0, 0 ) );
            imagecopyresampled( $image, $this->img_source, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight() );
            $this->img_source = $image;
        }
        return $this;
    }

    /**
     * Crop the image to a specified position/dimension
     */
    public function crop( $x=0, $y=0, $width=0, $height=0 )
    {
        if( $this->_checkResource() )
        {
            $image = imagecreatetruecolor( $width, $height );
            imagealphablending( $image, true );
            imagesavealpha( $image, true );
            imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );
            imagecolortransparent( $image, imagecolorallocate( $image, 0, 0, 0 ) );
            imagecopy( $image, $this->img_source, 0, 0, $x, $y, $width, $height );
            $this->img_source = $image;
        }
        return $this;
    }

    /**
     * Rotate the image to the left by 90 degrees
     */
    public function rotateLeft()
    {
        if( $this->_checkResource() )
        {
            $image = imagerotate( $this->img_source, 90, 0 );
            $this->img_source = $image;
        }
        return $this;
    }

    /**
     * Rotate the image to the right by 90 degrees
     */
    public function rotateRight()
    {
        if( $this->_checkResource() )
        {
            $image = imagerotate( $this->img_source, -90, 0 );
            $this->img_source = $image;
        }
        return $this;
    }

    /**
     * Flip the image horizontally
     */
    public function flipSide()
    {
        if( $this->_checkResource() )
        {
            imageflip( $this->img_source, IMG_FLIP_HORIZONTAL );
        }
        return $this;
    }

    /**
     * Flip the image vertically
     */
    public function flipTop()
    {
        if( $this->_checkResource() )
        {
            imageflip( $this->img_source, IMG_FLIP_VERTICAL );
        }
        return $this;
    }

    /**
     * Crop the image to a new size from the center
     */
    public function cropCenter( $width=0, $height=0 )
    {
        $x = floor( ( $this->getWidth() - $width ) / 2 );
        $y = floor( ( $this->getHeight() - $height ) / 2 );
        $this->crop( $x, $y, $width, $height );
        return $this;
    }

    /**
     * Scale the image to fill up (cover) a new size
     */
    public function fillCenter( $width=0, $height=0 )
    {
        if( $width >= $height )
        {
            $this->scaleWidth( $width );
        }
        else if( $height >= $width )
        {
            $this->scaleHeight( $height );
        }
        $this->cropCenter( $width, $height );
        return $this;
    }

    /**
     * Scale the image down to fit in a new size
     */
    public function fitCenter( $width=0, $height=0 )
    {
        if( $width <= $height )
        {
            $this->scaleWidth( $width );
        }
        else if( $height <= $width )
        {
            $this->scaleHeight( $height );
        }
        $this->cropCenter( $width, $height );
        return $this;
    }

    /**
     * Scale the image size using decimal or percentage value
     */
    public function scale( $scale=1 )
    {
        $scale  = ( $scale > 1 ) ? ( $scale / 100 ) : $scale;
        $width  = floor( $this->getWidth() * $scale );
        $height = floor( $this->getHeight() * $scale );
        $this->resize( $width, $height );
        return $this;
    }

    /**
     * Scale the image aspect ratio size horizontally
     */
    public function scaleWidth( $width=0 )
    {
        $ratio  = $width / $this->getWidth();
        $height = floor( $this->getHeight() * $ratio );
        $this->resize( $width, $height );
        return $this;
    }

    /**
     * Scale the image aspect ratio size vertically
     */
    public function scaleHeight( $height=0 )
    {
        $ratio = $height / $this->getHeight();
        $width = floor( $this->getWidth() * $ratio );
        $this->resize( $width, $height );
        return $this;
    }

    /**
     * Scale the image down to a max limit size
     */
    public function maxSize( $size=0 )
    {
        if( $this->getWidth() > $size )
        {
            $this->scaleWidth( $size );
        }
        else if( $this->getHeight() > $size )
        {
            $this->scaleHeight( $size );
        }
        return $this;
    }

    /**
     * Scale the image up to a min limit size
     */
    public function minSize( $size=0 )
    {
        if( $this->getWidth() < $size )
        {
            $this->scaleWidth( $size );
        }
        else if( $this->getHeight() < $size )
        {
            $this->scaleHeight( $size );
        }
        return $this;
    }

    /**
     * Adds an image watermark to the current image
     */
    public function watermark( $file="", $position="bottom right", $margin=10 )
    {
        try
        {
            $wmrk = new Image();
            $wmrk->load( $file );
            $wmrk->maxSize( min( $this->getWidth(), $this->getHeight() ) - ( $margin * 2 ) );

            $position = strtolower( Sanitize::toAlnum( $position ) );
            $minX     = $margin;
            $minY     = $margin;
            $centerX  = ( $this->getWidth()  - $wmrk->getWidth() )  / 2;
            $centerY  = ( $this->getHeight() - $wmrk->getHeight() ) / 2;
            $maxX     = $this->getWidth()  - $wmrk->getWidth()  - $margin;
            $maxY     = $this->getHeight() - $wmrk->getHeight() - $margin;
            $xPos     = $maxX;
            $yPos     = $maxY;

            if( strpos( $position, "top" )    !== false ){ $yPos = $minY;    } else
            if( strpos( $position, "middle" ) !== false ){ $yPos = $centerY; } else
            if( strpos( $position, "bottom" ) !== false ){ $yPos = $maxY;    }

            if( strpos( $position, "left" )   !== false ){ $xPos = $minX;    } else
            if( strpos( $position, "center" ) !== false ){ $xPos = $centerX; } else
            if( strpos( $position, "right" )  !== false ){ $xPos = $maxX;    }

            return imagecopy( $this->img_source, $wmrk->getResource(), $xPos, $yPos, 0, 0, $wmrk->getWidth(), $wmrk->getHeight() );
        }
        catch( Exception $e ){}
        return false;
    }

    /**
     * Save final image
     */
    public function save( $file="", $quality=80 )
    {
        $file   = Sanitize::toPath( $file );
        $folder = dirname( $file );
        $saved  = false;

        if( !empty( $file ) && $this->img_source !== null )
        {
            if( is_dir( $folder ) || mkdir( $folder, 0777, true ) )
            {
                @imagealphablending( $this->img_source, false );
                @imagesavealpha( $this->img_source, true );

                if( $this->img_type === IMAGETYPE_JPEG )
                {
                    $saved = @imagejpeg( $this->img_source, $file, $quality );
                }
                if( $this->img_type === IMAGETYPE_GIF )
                {
                    $saved = @imagegif( $this->img_source, $file );
                }
                if( $this->img_type === IMAGETYPE_PNG )
                {
                    $saved = @imagepng( $this->img_source, $file );
                }
            }
        }
        @imagedestroy( $this->img_source );
        $this->img_source = null;
        return $saved;
    }

    /**
     * Output image to browser
     */
    public function output( $quality=80 )
    {
        if( $this->img_type === IMAGETYPE_JPEG )
        {
            @header( "Content-type: image/jpeg" );
            @imagejpeg( $this->img_source, null, $quality );
            exit;
        }
        if( $this->img_type === IMAGETYPE_GIF )
        {
            @header( "Content-type: image/gif" );
            @imagegif( $this->img_source );
            exit;
        }
        if( $this->img_type === IMAGETYPE_PNG )
        {
            @header( "Content-type: image/png" );
            @imagepng( $this->img_source );
            exit;
        }
        die( "Invalid image type." );
    }

    /**
     * Delete source files and data
     */
    public function cleanup()
    {
        if( is_resource( $this->img_source ) )
        {
            @imagedestroy( $this->img_source );
        }
        if( is_file( $this->img_file ) )
        {
            @unlink( $this->img_file );
        }
        $this->img_source = null;
        $this->img_file   = "";
        $this->img_type   = 0;
        return $this;
    }

    /**
     * Checks for a valid GD image resource
     */
    private function _checkResource()
    {
        if( is_resource( $this->img_source ) !== true )
        {
            throw new Exception( "Please load an image file (JPG, JPEG, PNG, GIF) for processing." );
        }
        return true;
    }

}


