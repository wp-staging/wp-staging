<?php

namespace WPStaging\Notifications;

use WPStaging\Notifications\Transporter\EmailNotification;

class NotificationsProvider
{



    public function getProviders(): array
    {
        return [
            EmailNotification::class
        ];
    }
}
