<?php

namespace WPStaging\Core\Utils;

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

class Filesystem {

    /**
     * Create a file with content
     *
     * @param  string $path    Path to the file
     * @param  string $content Content of the file
     * @return boolean
     */
    public function create( $path, $content ) {
        if( !@file_exists( $path ) ) {
            if( !@is_writable( dirname( $path ) ) ) {
                return false;
            }

            if( !@touch( $path ) ) {
                return false;
            }
        } elseif( !@is_writable( $path ) ) {
            return false;
        }

        $written = false;
        if( ( $handle     = @fopen( $path, 'w' ) ) !== false ) {
            if( @fwrite( $handle, $content ) !== false ) {
                $written = true;
            }

            @fclose( $handle );
        }

        return $written;
    }

    /**
     * Create a file with marker and content
     *
     * @param  string $path    Path to the file
     * @param  string $marker  Name of the marker
     * @param  string $content Content of the file
     * @return boolean
     */
    public function createWithMarkers( $path, $marker, $content ) {
        return @insert_with_markers( $path, $marker, $content );
    }

}
