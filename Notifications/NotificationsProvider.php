<?php

namespace WPStaging\Notifications;

use WPStaging\Notifications\Transporter\EmailNotification;

class NotificationsProvider
{
    /**
     * @return array
     */
    public function getProviders(): array
    {
        return [
            EmailNotification::class
        ];
    }
}
