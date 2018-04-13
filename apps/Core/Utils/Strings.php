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

      if( empty( $search ) )
         return $subject;

   $pos = strpos( $subject, $search );
   if( $pos !== false ) {
      return substr_replace( $subject, $replace, $pos, strlen( $search ) );
   }
   return $subject;
}

   /**
    * Get last string after last certain element in string
    * Example: getLastElemAfterString('/', '/path/stagingsite/subfolder') returns 'subfolder'
    * @param string $needle
    * @param string $haystack
    * @return string
    */
   public function getLastElemAfterString( $needle, $haystack ) {
      $pos = strrpos( $haystack, $needle );
      return $pos === false ? $haystack : substr( $haystack, $pos + 1 );
}

}
