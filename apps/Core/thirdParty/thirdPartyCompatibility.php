<?php

namespace WPStaging\thirdParty;

use WPStaging\DI\InjectionAware;

/**
 * Methods to use for third party plugin compatibility
 *
 * @author IronMan
 */
class thirdPartyCompatibility extends InjectionAware
{

   /**
    * Define a list of tables which should not run through search & replace method
    * @param string table name e.g. wpsptg1_cerber_files or wpstgtmp_4_cerber_files
    * @return array
    */
   public function isSearchReplaceExcluded($table) {
      $excludedTables = array(
          '_cerber_files', // Cerber Security Plugin
      );
      
      foreach($excludedTables as $excludedTable){
         if( false !== strpos($table, $excludedTable) ){
            return true;
         }
      }
      return false;
   }

}
