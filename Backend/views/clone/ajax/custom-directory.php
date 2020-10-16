<?php
/**
 * This file is currently being called only for the Free version:
 * src/Backend/views/clone/ajax/scan.php:113
 *
 * @file src/Backend/Pro/views/clone/ajax/custom-directory.php For the Pro counterpart.
 */
?>
<fieldset disabled style="opacity:0.8;border-top: 1px solid white;margin-top: 20px;">


   <p>
       <strong style="font-size: 14px;"> <?php _e( 'Copy Staging Site to Custom Directory', 'wp-staging' ); ?></strong>
       <br>
           <?php _e( 'Path must be writeable by PHP and an absolute path like <code>/www/public_html/dev</code>.', 'wp-staging' ); ?>
   </p>
   <table cellspacing="0" id="wpstg-clone-directory">
       <tbody>
           <tr><th style="text-align:left;min-width: 120px;">Target Directory: </th>
               <td> <input readonly style="width:300px;" type="text" name="wpstg_clone_dir" id="wpstg_clone_dir" value="" title="wpstg_clone_dir" placeholder="<?php echo \WPStaging\WPStaging::getWPpath(); ?>" autocapitalize="off"></td>
           </tr>
           <tr>
               <td></td>
               <td><code>Default: <?php echo \WPStaging\WPStaging::getWPpath(); ?></code></td>
           </tr>
           <tr>
               <td>&nbsp;</td>
               <td></td>
           </tr>
           <tr><th style="text-align:left;min-width:120px;">Target Hostname: </th><td> <input readonly style="width:300px;" type="text" name="wpstg_clone_hostname" id="wpstg_clone_hostname" value="" title="wpstg_clone_hostname" placeholder="<?php echo get_site_url(); ?>" autocapitalize="off">
               </td>
           </tr>
           <tr>
               <td></td>
               <td><code>Default: <?php echo get_site_url(); ?></code></td>
           </tr>
       </tbody>
   </table>
</fieldset>

