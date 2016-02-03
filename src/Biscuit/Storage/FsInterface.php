<?php
/**
 * Interface methods for working with filesystem items.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Storage;

interface FsInterface {

    /**
     * Creates a file, or folder
     */
    public function create( $chmod=0775 ); // : bool

    /**
     * Write data to a file, or file to a folder
     */
    public function write( $mixed=null ); // : bool/int

    /**
     * Read data from file, or folder items
     */
    public function read(); // : string/array

    /**
     * Rename file or folder with new path name
     */
    public function rename( $newpath='' ); // : bool

    /**
     * Copy file or folder to new location
     */
    public function copy( $newpath='' ); // : bool

    /**
     * Move file or folder to new location
     */
    public function move( $newpath='' ); // : bool

    /**
     * Delete file or folder
     */
    public function delete(); // : bool

    /**
     * Delete contents of file or folder
     */
    public function flush(); // : bool

}