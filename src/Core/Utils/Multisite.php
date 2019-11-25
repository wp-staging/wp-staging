<?php

namespace WPStaging\Utils;

use WPStaging\Utils\Helper;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

class Multisite {

   /**
    *
    * @var object
    */
   private $url;

   public function __construct() {
      $helper = new Helper();
      $this->url = $helper->get_home_url();
   }

   /**
    * Get multisite main site domain e.g. https://blog.domain.com or https://domain.com
    * @return string
    */
   public function getHomeDomain() {
      $result = parse_url( $this->url );
      return $result['scheme'] . "://" . $result['host'];
   }

   /**
    * Return domain without scheme e.g. blog.domain.com or domain.com
    * @param string $str
    * @return string
    */
   public function getHomeDomainWithoutScheme() {
      return preg_replace( '#^https?://#', '', rtrim( $this->getHomeDomain(), '/' ) );
   }
/**
 * Get home url e.g. blog.domain.com
 * @return type
 */
   public function getHomeUrl() {
      return $this->url;
   }

   /**
    * Return url without scheme e.g. blog.domain.com/site1 or domain.com/site1
    * @param string $str
    * @return string
    */
   public function getHomeUrlWithoutScheme() {
      return preg_replace( '#^https?://#', '', rtrim( $this->url, '/' ) );
   }

}
