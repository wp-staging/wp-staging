<?php
/**
 * @var array $adminEmails
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
