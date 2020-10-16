        <main class="wp-staging-login" >
            <div class="wpstg-text-center">
              <img src="<?php echo esc_url(plugins_url('../Backend/public/img/logo_clean_small_212_25.png', dirname( __FILE__))); ?>" alt="WP Staging Login" />
            </div>    
            <form class="wp-staging-form" name="<?php echo $args['form_id']; ?>" id="<?php echo $args['form_id']; ?>" action="" method="post">
                <?php if ($showNotice) { ?>
                    <div class="wpstg-alert wpstg-alert-info wpstg-text-justify">
                        <p><?php echo esc_html( $notice ); ?></p>
                    </div>
                <?php } ?>
                <div class="form-group login-username">
                    <label for="<?php echo esc_attr( $args['id_username'] ); ?>"><?php echo esc_html( $args['label_username'] ); ?></label>
                    <input type="text" name="wpstg-username" id="<?php echo esc_attr( $args['id_username'] ); ?>" class="input form-control" value="<?php echo esc_attr( $args['value_username'] ); ?>" size="20" />
                </div>
                <div class="form-group login-password">
                    <label for="<?php echo esc_attr( $args['id_password'] ); ?>"><?php echo esc_html( $args['label_password'] ); ?></label>
                    <input type="password" name="wpstg-pass" id="<?php echo esc_attr( $args['id_password'] ); ?>" class="input form-control" value="" size="20" />
                </div>

                <?php if ($args['remember']) { ?>
                <div class="form-group login-remember"><label><input name="rememberme" type="checkbox" id="<?php echo esc_attr( $args['id_remember'] ); ?>" value="forever"<?php echo ( $args['value_remember'] ? ' checked="checked"' : '' ); ?> /> <span><?php echo esc_html( $args['label_remember'] ); ?></span></label></div>
                <?php } ?>

                <div class="login-submit">
                    <button type="submit" name="wpstg-submit" id="<?php echo esc_attr( $args['id_submit'] ); ?>" class="btn" value="<?php echo esc_attr( $args['label_log_in'] ); ?>">Login</button>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( $args['redirect'] ); ?>" />
                </div>
                <div class="password-lost">
                    <a href="<?php echo trailingslashit(esc_url( $args['redirect'] )); ?>wp-login.php?action=lostpassword">Lost your password?</a>
                </div>

                <p class="error-msg">
                    <?php echo $this->error; ?>
                </p>
            </form>
        </main>