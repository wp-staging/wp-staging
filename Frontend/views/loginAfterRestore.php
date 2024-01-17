<?php
/**
 * @var array $adminEmails
 * @var bool $isRestoredFromWpCom // true if the backup was restored from wordpress.com but current site is not a wordpress.com site
 * @var string $resetPasswordArticleLink // link to article on how to reset password in different ways
 */
?>
<style>
    #adminEmails {
        display: none;
    }
    .unselectable {
        -moz-user-select: none;
        -webkit-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    #loginAfterRestore {
        width: 320px;
        padding-top: 20px;
        margin: auto;
    }
    #loginAfterRestore h1 {
        margin-bottom: 20px;
    }
    #loginAfterRestore p {
        margin-bottom: 10px;
    }
    #showAdminEmails {
        cursor: pointer;
        font-weight: bold;
        text-decoration: underline;
        color: #2196f3;
    }
    div#adminEmails ul {
        padding-left: 15px;
    }
    #login {
        padding-top:40px;
    }
</style>
<div id="loginAfterRestore">
    <h1 class="unselectable"><?php esc_html_e('Congratulations!', 'wp-staging'); ?></h1>
    <p class="unselectable"><?php esc_html_e('You have just restored a WP STAGING backup.', 'wp-staging'); ?></p>
    <p class="unselectable"><?php echo wp_kses_post(__('Now you need to log-in using email and password that were in the Backup that you just restored. If you don\'t remember the credentials, click <span id="showAdminEmails">here</span> to see a list of admin e-mails you can use to restore your access by clicking "Forgot your password". This message will appear only once.', 'wp-staging')); ?></p>
    <div id="adminEmails">
        <p>
            <?php if (is_array($adminEmails) && !empty($adminEmails)) : ?>
        <ul>
                <?php
                foreach ($adminEmails as $adminEmail) {
                    echo sprintf('<li>%s</li>', esc_html($adminEmail));
                }
                ?>
        </ul>
            <?php else : ?>
                <?php esc_html_e('Sorry, there are no admin e-mails to show.', 'wp-staging'); ?>
            <?php endif; ?>
    </div>
    </p>
    <?php if ($isRestoredFromWpCom) : ?>
    <p>
        <?php esc_html_e('This site was restored from a WordPress.com backup. Your WordPress.com password may not work and you will need to reset your password to get a new one!', 'wp-staging'); ?>
        <?php echo sprintf(esc_html__('Read this %s to find out how to reset the password.', 'wp-staging'), '<a href="' . esc_url($resetPasswordArticleLink) . '" target="_blank">' . esc_html__('article', 'wp-staging') . '</a>') ?>
    </p>
    <?php endif; ?>
</div>
<script>
    document.getElementById('showAdminEmails').addEventListener('click', function() {
      if (document.getElementById('adminEmails').style.display === "block") {
        document.getElementById('adminEmails').style.display = "none";
      } else {
        document.getElementById('adminEmails').style.display = "block";
      }
    });
</script>
