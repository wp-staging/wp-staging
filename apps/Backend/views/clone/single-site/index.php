<ul id="wpstg-steps">
    <li class="wpstg-current-step">
        <span class="wpstg-step-num">1</span>
        <?php echo __("Overview", "wpstg")?>
    </li>
    <li>
        <span class="wpstg-step-num">2</span>
        <?php echo __("Scanning", "wpstg")?>
    </li>
    <li>
        <span class="wpstg-step-num">3</span>
        <?php echo __("Cloning", "wpstg")?>
    </li>
    <li>
        <span id="wpstg-loader" style="display:none;"></span>
    </li>
</ul>

<div id="wpstg-workflow">

    <div id="wpstg-step-1">
        <button id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn button-primary" data-action="wpstg_scanning">
            <?php echo __("Create new staging site", "wpstg")?>
        </button>
    </div>

</div>