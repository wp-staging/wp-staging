<div id="wpstg-step-1">
    <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn button-primary wpstg-button" data-action="wpstg_scanning">
        <?php echo __("Create new staging site", "wp-staging")?>
    </button>
</div>

<?php if (isset($availableClones) && !empty($availableClones)):?>
    <!-- Existing Clones -->
    <div id="wpstg-existing-clones">
        <h3>
            <?php _e("Your Staging Sites:", "wp-staging")?>
        </h3>
<?php //wp_die(var_dump($availableClones)); ?>
        <?php foreach ($availableClones as $name => $data):?>
            <div id="<?php echo $data["directoryName"]; ?>" class="wpstg-clone">

                <?php $urlLogin = $data["url"];?>

                <a href="<?php echo $urlLogin?>" class="wpstg-clone-title" target="_blank">
                    <?php //echo $name?>
                    <?php echo $data["directoryName"]; ?>
                </a>

                <?php echo apply_filters("wpstg_before_stage_buttons", $html = '', $name, $data)?>

                <a href="<?php echo $urlLogin?>" class="wpstg-open-clone wpstg-clone-action" target="_blank">
                    <?php _e("Open", "wp-staging"); ?>
                </a>

                <a href="#" class="wpstg-execute-clone wpstg-clone-action" data-clone="<?php echo $name?>">
                    <?php _e("Update", "wp-staging"); ?>
                </a>

                <a href="#" class="wpstg-remove-clone wpstg-clone-action" data-clone="<?php echo $name?>">
                    <?php _e("Delete", "wp-staging"); ?>
                </a>

                <?php echo apply_filters("wpstg_after_stage_buttons", $html = '', $name, $data)?>
                <div class="wpstg-staging-info">                   
                    <?php 
                    $prefix = !empty ($data['prefix']) ? __("DB Prefix: <span class='wpstg-bold'>" . $data['prefix'], "wp-staging") . '</span> ' : '&nbsp;&nbsp;&nbsp;';
                    echo $prefix;
                    echo '</br>';
                    $dbname = !empty ($data['databaseDatabase']) ? __("DB Name: <span class='wpstg-bold'>" . $data['databaseDatabase'], "wp-staging") . '</span></br> ' : '';
                    echo $dbname;
                    $datetime = !empty ($data['datetime']) ? __("Updated: <span>" . date("D, d M Y H:i:s T",$data['datetime']) , "wp-staging") . '</span> ' : '&nbsp;&nbsp;&nbsp;';
                    echo $datetime;
                    
                    
                    ?>
                </div>
            </div>
        <?php endforeach?>
    </div>
    <!-- /Existing Clones -->
<?php endif?>

<!-- Remove Clone -->
<div id="wpstg-removing-clone">

</div>
<!-- /Remove Clone -->