<?php

namespace WPStaging\Vendor;

// Don't redefine the functions if included multiple times.
if (!\function_exists('WPStaging\\Vendor\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
