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
<p style="font-weight:bold;background-color:#e6e6e6;padding:15px;border-top: 1px solid white;margin-top: 20px;"><?php _e('That\'s a Pro Feature', 'wp-staging'); ?>
    <br>
    <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=db-external&utm_term=db-external" target="_blank" class="quads-button green wpstg-button" style="border-radius:3px;font-size: 14px;border: 1px solid white;"><?php _e("Get WP Staging Pro", "wp-staging"); ?></a>
</p>

