<form action="<?php echo esc_url(admin_url("admin-post.php?action=wpstg_download_sysinfo"))?>" method="post" dir="ltr">
    <textarea class="wpstg-sysinfo" readonly="readonly" id="system-info-textarea" name="wpstg-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo \WPStaging\Core\WPStaging::getInstance()->get("systemInfo")?></textarea>
    <p class="submit">
        <?php submit_button("Download System Info File", "primary", "wpstg-download-sysinfo", false )?>
    </p>
</form>
