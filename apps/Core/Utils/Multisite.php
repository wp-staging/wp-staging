<?php

namespace WPStaging\Utils;

use WPStaging\Utils\Helper;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

class Multisite {

   /**
    * Get multisite main site homeurl
    * @return string
    */
   public function getHomeURL() {
      $helper = new Helper();

      $url = $helper->get_home_url();
      $result = parse_url( $url );
      return $result['scheme'] . "://" . $result['host'];
   }

}
