<?php

namespace WPStaging\Notifications\Interfaces;

interface NotificationsInterface
{
    /**
     * @return bool
     */
    public function send(string $message): bool;
}
