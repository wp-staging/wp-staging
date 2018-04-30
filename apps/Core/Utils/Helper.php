<?php

namespace WPStaging\Utils;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

class Helper {

   /**
    * Retrieves the URL for a given site where the front end is accessible.
    * This is from WordPress source 4.9.5/src/wp-includes/link-template.php
    * home_url filter has been removed here to make sure that wpml can not overwrite that value!
    *
    * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
    * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
    * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
    *
    * @since 3.0.0
    *
    * @global string $pagenow
    *
    * @param  int         $blog_id Optional. Site ID. Default null (current site).
    * @param  string      $path    Optional. Path relative to the home URL. Default empty.
    * @param  string|null $scheme  Optional. Scheme to give the home URL context. Accepts
    *                              'http', 'https', 'relative', 'rest', or null. Default null.
    * @return string Home URL link with optional path appended.
    */
   public function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
      global $pagenow;

      $orig_scheme = $scheme;

      if( empty( $blog_id ) || !is_multisite() ) {
         $url = get_option( 'home' );
      } else {
         switch_to_blog( $blog_id );
         $url = get_option( 'home' );
         restore_current_blog();
      }

      if( !in_array( $scheme, array('http', 'https', 'relative') ) ) {
         if( is_ssl() && !is_admin() && 'wp-login.php' !== $pagenow )
            $scheme = 'https';
         else
            $scheme = parse_url( $url, PHP_URL_SCHEME );
      }

      $url = set_url_scheme( $url, $scheme );

      if( $path && is_string( $path ) )
         $url .= '/' . ltrim( $path, '/' );

      /**
       * Filters the home URL.
       *
       * @since 3.0.0
       *
       * @param string      $url         The complete home URL including scheme and path.
       * @param string      $path        Path relative to the home URL. Blank string if no path is specified.
       * @param string|null $orig_scheme Scheme to give the home URL context. Accepts 'http', 'https',
       *                                 'relative', 'rest', or null.
       * @param int|null    $blog_id     Site ID, or null for the current site.
       */
      return $url;
   }

}
