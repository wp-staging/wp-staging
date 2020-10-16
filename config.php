<?php
/**
 * @example 'services' or 'components' key can have parameters such as;
 * `Foo::class => ['slug' => '{{slug}}'],`
 * `slug` key matches the class constructor variable name (order does not matter) such as `__construct($slug)`
 * `{{slug}}` value matches the `params.slug` of this file.
 *
 * **IMPORTANT TOPICS**
 * 1. Order of params does not matter as we check & match variable names
 * 2. Not all params in constructor needs to be defined, only the ones we want
 * 3. Hand-typed variables are allowed; `Foo::class => ['slug' => 'bar'],`
 */

use Psr\Log\LoggerInterface;
use WPStaging\Utils\Logger;

return [
    // Params we can use all around the application with easy access and without duplication / WET; keep it DRY!
    'params' => [
        'version' => 'Free',
        'slug' => 'wp-staging',
        'domain' => 'wp-staging',
    ],
    // Services are not initialized, they are only initialized once when they are requested. If they are already
    // initialized when requested, the same instance would be used.
    'services' => [],
    // Components are initialized upon plugin init / as soon as the Container is set; such as a class that sets;
    // Ajax Request, Adds a Menu, Form etc. needs to be initialized without being requested hence they go here!
    'components' => [
    ],
    // Map specific interfaces to specific classes.
    // If you map LoggerInterface::class to Logger::class, when you use LoggerInterface as a dependency,
    // it will load / pass Logger class instead
    'mapping' => [
        LoggerInterface::class => Logger::class,
    ],
];
