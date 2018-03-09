<?php

namespace WPStaging\Utils;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

class Strings{

/**
 * Replace first occurance of certain string
 * @param string $search
 * @param string $replace
 * @param string $subject
 * @return string
 */
public function str_replace_first( $search, $replace, $subject ) {
   $pos = strpos( $subject, $search );
   if( $pos !== false ) {
      return substr_replace( $subject, $replace, $pos, strlen( $search ) );
   }
   return $subject;
}

}
