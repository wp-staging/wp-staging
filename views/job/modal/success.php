<div id="wpstg--success-modal" class="wpstg--success-modal">
    <div id="wpstg--success-modal--inner">
        <h2 class="wpstg--success-modal--title">{title}</h2>
        <div class="wpstg--success-modal--header">
            <p class="wpstg--success-modal--text">{text}<button class="wpstg--success-modal--logs-button">{btnTxtLog}</button></p>
        </div>
        <div class="wpstg--success-modal--logs-wrapper">
            <div class="wpstg--success-modal--logs wpstg--logs--container">
                <?php require(WPSTG_VIEWS_DIR . 'logs/logs-template.php'); ?>
            </div>
        </div>
        <div class="wpstg--success-modal--additional-content"></div>
    </div>
</div>
