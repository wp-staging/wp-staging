<?php

namespace WPStaging\Utils;

use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\DI\InjectionAware;

class Report extends InjectionAware {

   /**
    * Send customer issue report
    *
    * @param  string  $email   User e-mail
    * @param  string  $message User message
    * @param  integer $terms   User accept terms
    *
    * @return array
    */
   public function send( $email, $message, $terms, $syslog ) {
      $errors = array();
      
      if( !empty( $syslog ) ) {
            $message .= "\n\n'" . $this->getSyslog();
      }

      if( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
         $errors[] = __( 'Email address is not valid.', 'wp-staging' );
      } elseif( empty( $message ) ) {
         $errors[] = __( 'Please enter your issue.', 'wp-staging' );
      } elseif( empty( $terms ) ) {
         $errors[] = __( 'Please accept our privacy policy.', 'wp-staging' );
      } else {

         if( false === $this->sendMail( $email, $message ) ) {
            $errors[] = 'Can not send report. <br>Please send us a mail to<br>support@wp-staging.com';
            //         $response = wp_remote_post(
//                 'https://wp-staging.com', array(
//             'timeout' => 15,
//             'body' => array(
//                 'email' => $email,
//                 'message' => $message,
//             ),
//                 )
//         );
//
//         if( is_wp_error( $response ) ) {
//            $errors[] = sprintf( __( 'Something went wrong: %s', 'wp-staging' ), $response->get_error_message() );
//         }
         }
      }

      return $errors;
   }

   private function getSyslog() {

      $syslog = new SystemInfo( $this->di );
      return $syslog->get();
   }

   /**
    * send feedback via email
    * 
    * @return boolean
    */
   private function sendMail( $from, $text ) {

      $headers = array();

      $headers[] = "From: $from";
      $headers[] = "Reply-To: $from";

      $subject = 'Report Issue!';

      $success = wp_mail( 'support@wp-staging.com', $subject, $text, $headers );

      if( $success ) {
         return true;
      } else {
         return false;
      }
      die();
   }

}
