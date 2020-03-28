<?php

namespace WPStaging\Test;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static function setUpBeforeClass()
    {
        foreach ($_ENV as $key => $defaultValue) {
            $value = getenv($key);
            if (false !== $value) {
                $_ENV[$key] = $value;
            }
        }
    }
}
