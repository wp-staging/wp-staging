<?php







use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\PluginLifecycle;
use WPStaging\Framework\Onboarding\FirstInstall;

if (!class_exists('WPStaging\Core\Cron\Cron')) {
    return;
}





$isNewInstall = FirstInstall::hasNeverSeenWpStaging();

$isActivatingProEdition = wpstgPluginFileIsProEdition($pluginFilePath);

FirstInstall::markIfFirstInstall();

PluginLifecycle::recordActivation($isNewInstall);




$cron = (new Cron)->scheduleEvent();




$optimizer = (new Optimizer)->installOptimizer();




if (!$isActivatingProEdition) {
    set_transient('wpstg_activation_redirect', true, 3600);
}




$htaccess = new Htaccess();
if (extension_loaded('litespeed')) {
    $htaccess->createLitespeed(ABSPATH . '.htaccess');
}




$settings = (new Settings())->setDefault();





if ($isActivatingProEdition) {
    add_option('wpstgpro_install_date', date('Y-m-d h:i:s'));
} else {
    add_option('wpstg_free_install_date', date('Y-m-d h:i:s'));
}

 
add_option('wpstg_installDate', date('Y-m-d h:i:s'));




$backupScheduler = WPStaging::make(\WPStaging\Backup\BackupScheduler::class);
$backupScheduler->reCreateCronIfSchedulesExist();




WPStaging::make(\WPStaging\Backup\BackupServiceProvider::class)->createBackupsDirectory();
