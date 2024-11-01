<?php
/*
Plugin Name: UVisualize! Admin Functions
Plugin URI: http://cba.fro.at/uvisualize
Description: Different Filters and action calls
Author: Ingo Leindecker
Author URI: http://www.fro.at/ingol
*/


/**
 * Truncates a string by given attributes
 *
 * @param string : The string to truncate
 * @param int : How many characters should rely
 * @param string : Everything after the truncated string
 * @param boolean : Hard break within words if true; leaves words if false even the number of characters is higher than $length
 * @return string : The truncated string
 *
 * Original code by the smarty template engine project: http://www.smarty.net
 *
 */
function uvis_truncate( $string, $length = 80, $etc = '...', $break_words = false ) {

    if( $length == 0 ) {
        return '';
    }

    if( strlen( $string ) > $length ) {
        $length -= strlen( $etc );
        if ( ! $break_words ) {
            $string = preg_replace( '/\s+?(\S+)?$/', '', substr( $string, 0, $length + 1 ) );
        }

        return substr( $string, 0, $length ) . $etc;
    } else {
        return $string;
    }

}

?>