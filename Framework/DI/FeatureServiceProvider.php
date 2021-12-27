<?php

/**
 * The base class that models a Service Provider that will completely provide a feature or not.
 *
 * Feature Service Providers will differ from normal providers in that they completely manage
 * a feature and all the required bindings and hooks. Feature Providers follow the concept of a
 * truthy constant to enable them, and a falsy value environment variable to disable them if activated.
 * This allows Feature Providers to launch a feature "darkly" by simply not setting their trigger constant,
 * once launched, users can disable the feature either by setting en environment variable with the same
 * name as the constant to a falsy value or by setting the constant to `false` in their `wp-config.php` file.
 *
 * @package WPStaging\Framework\DI
 */

namespace WPStaging\Framework\DI;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * Class FeatureServiceProvider
 *
 * @package WPStaging\Framework\DI
 */
abstract class FeatureServiceProvider extends ServiceProvider implements FeatureProviderInterface
{
    /**
     * Returns the constant, or environment variables, that will trigger the feature provider
     * registration when set to truthy values.
     *
     * Note: if set, the constant MUST override the value of the environment variable. The provider
     * registration should still honor its requirements.
     * If the constant is defined AND truthy, then the feature MUST still be disabled setting an environment variable
     * by the same name as the constant to a falsy value.
     *
     * @return string The name of the constant, or environment variable, that will trigger the
     *                feature provider registration when set to truthy values.
     *
     * @throws WPStagingException If the method is not overridden by the extending Service Provider class.
     */
    public static function getFeatureTrigger()
    {
        die('As I should not be invoked.');
        throw new WPStagingException('Every Feature Service Provider MUST define a feature trigger.');
    }

    /**
     * Returns whether the feature provided by the provider is enabled or not.
     * This is used if a feature is ready and activated in production.
     *
     * The check will happen on the feature provider trigger by checking if
     * the feature is available (the trigger constant is defined and true) and, if so,
     * if the environment var by the same name is not set to falsy value.
     *
     * @return bool Whether the feature provided is enabled or not.
     */
    public static function isEnabledInProduction()
    {
        $trigger = static::getFeatureTrigger();

        if (defined($trigger) && constant($trigger) === false) {
            // The feature will be disabled if the constant is set and `false`.
            return false;
        }

        if (getenv($trigger) !== false && (bool)getenv($trigger) === false) {
            // The feature can be disabled by setting an environment variable by the trigger name to a falsy value.
            return false;
        }

        return true;
    }

    /**
     * Returns whether the feature provided by the provider is enabled or not.
     *
     * This is used if a feature is still in development and not released in production.
     * The default value is false and a feature must be explicitely activated by defining the feature constant and setting it to true
     *
     * If the feature constant is not set the feature stays disabled.
     *
     * @return bool Whether the feature provided is enabled or not.
     */
    public static function isEnabledInDevelopment()
    {
        $trigger = static::getFeatureTrigger();

        if (!defined($trigger)) {
            return false;
        }

        if (defined($trigger) && constant($trigger) === false) {
            // The feature will be disabled if the constant is set and `false`.
            return false;
        }

        if (getenv($trigger) !== false && (bool)getenv($trigger) === false) {
            // The feature can be disabled by setting an environment variable by the trigger name to a falsy value.
            return false;
        }

        return true;
    }
}
