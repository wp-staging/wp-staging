        <main class="wp-staging-login" >
            <div class="wpstg-text-center">
              <img width="220" src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/img/logo.svg'); ?>" alt="WP Staging Login" />
            </div>
            <form class="wp-staging-form" name="<?php echo esc_attr($args['form_id']); ?>" id="<?php echo esc_attr($args['form_id']); ?>" action="" method="post">
                <?php if ($showNotice) { ?>
                    <div class="wpstg-alert wpstg-alert-info wpstg-text-justify">
                        <p><?php echo esc_html($notice); ?></p>
                    </div>
                <?php } ?>
                <div class="form-group login-username">
                    <label for="<?php echo esc_attr($args['id_username']); ?>"><?php echo esc_html($args['label_username']); ?></label>
                    <input type="text" name="wpstg-username" id="<?php echo esc_attr($args['id_username']); ?>" class="input form-control" value="<?php echo esc_attr($args['value_username']); ?>" size="20" />
                </div>
                <div class="form-group login-password">
                    <label for="<?php echo esc_attr($args['id_password']); ?>"><?php echo esc_html($args['label_password']); ?></label>
                    <input type="password" name="wpstg-pass" id="<?php echo esc_attr($args['id_password']); ?>" class="input form-control" value="" size="20" />
                </div>

                <?php if ($args['remember']) { ?>
                <div class="form-group login-remember"><label><input name="rememberme" type="checkbox" id="<?php echo esc_attr($args['id_remember']); ?>" value="forever"<?php echo ( $args['value_remember'] ? ' checked="checked"' : '' ); ?> /> <span><?php echo esc_html($args['label_remember']); ?></span></label></div>
                <?php } ?>

                <div class="login-submit">
                    <button type="submit" name="wpstg-submit" id="<?php echo esc_attr($args['id_submit']); ?>" class="btn" value="<?php echo esc_attr($args['label_log_in']); ?>"><?php esc_html_e('Login', 'wp-staging') ?></button>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($args['redirect']); ?>" />
                </div>
                <div class="password-lost">
                    <a href="<?php echo esc_url($args['lost_password_url']); ?>"><?php esc_html_e('Lost your password?', 'wp-staging') ?></a>
                </div>

                <p class="error-msg">
                    <?php echo wp_kses_post($this->error); ?>
                </p>
            </form>
        </main>
