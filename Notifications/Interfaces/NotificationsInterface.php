<?php

namespace WPStaging\Notifications\Interfaces;

interface NotificationsInterface
{



    public function send(string $message): bool;
}
