<?php

namespace WPStaging\Framework\Analytics\Actions;








class PluginLifecycle
{
    const GROUP = 'lifecycle';

    const EVENT_ACTIVATED   = 'plugin_activated';
    const EVENT_DEACTIVATED = 'plugin_deactivated';






    const TRANSIENT_DEACTIVATION_REASON = 'wpstg_deactivation_reason';

    const REASON_LIFETIME_IN_SECONDS = 300;

 
    const REASON_MAX_LENGTH = 120;










    public static function recordActivation(bool $isFirstInstall)
    {
        AnalyticsGenericEvent::logEvent(self::EVENT_ACTIVATED, self::GROUP, [
            'first_install' => $isFirstInstall ? 'yes' : 'no',
        ]);
    }











    public static function rememberDeactivationReason(array $reasons)
    {
        $reason = implode(',', array_filter(array_map(function ($reason) {
            return preg_replace('/[^a-z0-9_]/', '', strtolower((string)$reason));
        }, $reasons)));

        if ($reason === '') {
            delete_transient(self::TRANSIENT_DEACTIVATION_REASON);

            return;
        }

        set_transient(
            self::TRANSIENT_DEACTIVATION_REASON,
            substr($reason, 0, self::REASON_MAX_LENGTH),
            self::REASON_LIFETIME_IN_SECONDS
        );
    }











    public static function recordDeactivation()
    {
        $reason = get_transient(self::TRANSIENT_DEACTIVATION_REASON);
        delete_transient(self::TRANSIENT_DEACTIVATION_REASON);

        AnalyticsGenericEvent::logEventNow(self::EVENT_DEACTIVATED, self::GROUP, [
            'reason'                   => is_string($reason) && $reason !== '' ? $reason : 'none_given',
            'remove_data_on_uninstall' => self::willRemoveDataOnUninstall() ? 'yes' : 'no',
        ]);
    }

    private static function willRemoveDataOnUninstall(): bool
    {
        $settings = get_option('wpstg_settings', []);

        if (is_object($settings)) {
            $settings = json_decode(json_encode($settings), true);
        }

        return is_array($settings) && !empty($settings['unInstallOnDelete']);
    }
}
