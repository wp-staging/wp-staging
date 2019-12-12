<div id="wpstg-clonepage-wrapper">

    <?php
    require_once($this->path . "views/_main/header.php");
    require_once($this->path . "views/_main/report-issue.php");
    ?>

    <?php
    do_action( "wpstg_notifications" );

    if( wpstg_is_stagingsite() ) {
        // Staging site
        require_once($this->path . "views/clone/staging-site/index.php");
    } elseif( !defined('WPSTGPRO_VERSION') && is_multisite() ) {
        require_once($this->path . "views/clone/multi-site/index.php");
    }
    // Single site
    else {
        require_once($this->path . "views/clone/single-site/index.php");
    }

    // Footer
    require_once($this->path . "views/_main/footer.php");
    ?>
</div>