<div class="wpstg_admin">
    <span class="wp-staginglogo">
        <img src="<?php echo $this->url . "img/logo_clean_small_212_25.png" ?>">
    </span>

    <span class="wpstg-version">
        <?php if( \WPStaging\WPStaging::SLUG === "wp-staging-pro" ) echo "Pro" ?> Version <?php echo \WPStaging\WPStaging::VERSION ?>
    </span>

    <div class="wpstg-header">
        <div class='wpstg-share-button-container'>
            <div class='wpstg-share-button wpstg-share-button-twitter' data-share-url="https://wordpress.org/plugins/wp-staging">
                <div clas='box'>
                    <a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one%20click%20WordPress%20testing%20site&via=wpstg" target='_blank'>
                        <span class='wpstg-share'><?php echo __( 'Tweet #wpstaging', 'wpstg' ); ?></span>
                    </a>
                </div>
            </div>
            <div class="wpstg-share-button wpstg-share-button-twitter">
                <div class="box">
                    <a href="https://twitter.com/intent/follow?original_referer=http%3A%2F%2Fsrc.wordpress-develop.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dwpstg-settings&ref_src=twsrc%5Etfw&region=follow_link&screen_name=renehermenau&tw_p=followbutton" target="_blank">
                        <span class='wpstg-share'><?php echo __( 'Follow @wpstaging', 'wpstg' ); ?></span>
                    </a>
                </div>
            </div>
            <div class="wpstg-share-button wpstg-share-button-facebook" data-share-url="https://wordpress.org/plugins/wp-staging">
                <div class="box">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwordpress.org%2Fplugins%2Fwp-staging" target="_blank">
                        <span class='wpstg-share'><?php echo __( 'Share on Facebook', 'wpstg' ); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav-tab-wrapper">
        <?php
        $tabs = $this->di->get( "tabs" )->get();
        $activeTab = (isset( $_GET["tab"] ) && array_key_exists( $_GET["tab"], $tabs )) ? $_GET["tab"] : "general";

        # Loop through tabs
        foreach ( $tabs as $id => $name ):
           $url = esc_url( add_query_arg( array(
               "settings-updated" => false,
               "tab" => $id
           ) ) );

           $activeClass = ($activeTab === $id) ? " nav-tab-active" : '';
           ?>
           <li>
               <a href="<?php echo $url ?>" title="<?php echo esc_attr( $name ) ?>" class="nav-tab<?php echo $activeClass ?>">
                   <?php echo esc_html( $name ) ?>
               </a>
           </li>
           <?php
           unset( $url, $activeClass );
        endforeach;
        ?>
    </ul>
    <h2 class="nav-tab-wrapper"></h2>

    <div id="tab_container" class="tab_container">
        <div class="panel-container">
            <form method="post" action="options.php">
                <?php
                settings_fields( "wpstg_settings" );

                foreach ( $tabs as $id => $name ):
                   $form = $this->di->get( "forms" )->get( $id );

                   if( null === $form ) {
                      continue;
                   }
                   ?>
                   <div id="<?php echo $id ?>__wpstg_header">
                       <table class="form-table">
                           <thead>
                               <tr class="row">
                                   <th class="row th" colspan="2">
                           <div class="col-title">
                               <strong><?php echo $name ?></strong>
                               <span class="description"></span>
                           </div>
                           </th>
                           </tr>
                           </thead>

                           <tbody>
                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php
                                           echo $form->label( "wpstg_settings[queryLimit]" )
                                           ?>
                                           <span class="description">
                                               Number of DB rows, that will be copied within one ajax request.
                                               The higher the value the faster the database copy process.
                                               To find out the highest possible values try a high value like 1.000 or more. If you get timeout issues, lower it
                                               until you get no more errors during copying process.
                                               <br>
                                               <strong> Default: 5000 </strong>
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[queryLimit]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php
                                           echo $form->label( "wpstg_settings[fileLimit]" )
                                           ?>
                                           <span class="description">
                                               Number of files to copy that will be copied within one ajax request.
                                               The higher the value the faster the file copy process.
                                               To find out the highest possible values try a high value like 500 or more. If you get timeout issues, lower it
                                               until you get no more errors during copying process.
                                               <br>
                                               <strong> Default: 1 </strong>
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[fileLimit]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[batchSize]" ) ?>
                                           <span class="description">
                                               Buffer size for the file copy process in megabyte.
                                               The higher the value the faster large files will be copied.
                                               To find out the highest possible values try a high one and lower it until
                                               you get no errors during file copy process. Usually this value correlates directly
                                               with the memory consumption of php so make sure that
                                               it does not exceed any php.ini max_memory limits.
                                               <br>
                                               <strong>Default:</strong> 2
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[batchSize]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[cpuLoad]" ) ?>
                                           <span class="description">
                                               Using high will result in fast as possible processing but the cpu load
                                               increases and it's also possible that staging process gets interrupted because of too many ajax requests
                                               (e.g. <strong>authorization error</strong>).
                                               Using a lower value results in lower cpu load on your server but also slower staging site creation.
                                               <br>
                                               <strong>Default: </strong> Medium
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[cpuLoad]" ) ?>
                                   </td>
                               </tr>
                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[disableAdminLogin]" ) ?>
                                           <span class="description">
                                               If you want to remove the requirement to login to the staging site you can deactivate it here.
                                               <strong>Note:</strong> The staging site discourages search engines from indexing the site by setting the 'noindex' tag into header of the staging site.
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[disableAdminLogin]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[wpSubDirectory]" ) ?>
                                           <span class="description">
                                               Use this option when you gave wordpress its own subdirectory.
                                               if you enable this, WP Staging will reset the index.php of the clone site to the originally one.
                                               <br>
                                               <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Read more in the WordPress Codex</a>
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[wpSubDirectory]" ) ?>
                                   </td>
                               </tr>
                               <!-- Deactivated -->
                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[debugMode]" ) ?>
                                           <span class="description">
                                               This will enable an extended debug mode which creates additional entries
                                               in <strong>wp-content/uploads/wp-staging/logs/logfile.log</strong>.
                                               Please enable this when we ask you to do so.
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[debugMode]" ) ?>
                                   </td>
                               </tr>
                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[optimizer]" ) ?>
                                           <span class="description">
                                               The Optimizer is a mu plugin which disables all other plugins during WP Staging processing. Usually this makes the cloning process more reliable. If you experience issues, disable the Optimizer.
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[optimizer]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[unInstallOnDelete]" ) ?>
                                           <span class="description">
                                               Check this box if you like WP Staging to completely remove all of its data when the plugin is deleted.
                                               This will not remove staging sites files or database tables.
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[unInstallOnDelete]" ) ?>
                                   </td>
                               </tr>

                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[checkDirectorySize]" ) ?>
                                           <span class="description">
                                               Check this box if you like WP Staging to check sizes of each directory on scanning process.
                                               <br>
                                               Warning this might cause timeout problems in big directories / file structures.
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo $form->render( "wpstg_settings[checkDirectorySize]" ) ?>
                                   </td>
                               </tr>
                               <tr class="row">
                                   <td class="row th">
                                       <div class="col-title">
                                           <?php echo $form->label( "wpstg_settings[loginSlug]" ) ?>
                                           <span class="description">
                                               <?php _e( 'Enter the string which links to your login page if you are using a custom login page instead the default WordPress login. <br/><br/><strong>This does not affect already existing staging sites.</strong><br/> You need to create a new staging site if you like to change the login url of a staging site.', 'wpstg' ); ?>
                                           </span>
                                       </div>
                                   </td>
                                   <td>
                                       <?php echo get_home_url() . '/?' . $form->render( "wpstg_settings[loginSlug]" ); ?>
                                   </td>
                               </tr>
                           </tbody>
                       </table>
                   </div>
                   <?php
                endforeach;
                // Show submit button any tab but add-ons
                if( $activeTab !== "add-ons" ) {
                   submit_button();
                }
                unset( $tabs );
                ?>
            </form>
        </div>
    </div>
</div>
