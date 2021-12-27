<?php

/**
 * The API provided by a Service Provider that completely provides a feature.
 *
 * @package WPStaging\Framework\DI
 */

namespace WPStaging\Framework\DI;

/**
 * Interface FeatureProviderInterface
 *
 * @package WPStaging\Framework\DI
 */
interface FeatureProviderInterface
{
    /**
     * Returns whether the feature provided by the provider is enabled or not.
     *
     * The check will happen on the feature provider trigger by checking if
     * the feature is available (the trigger constant is defined and true) and, if so,
     * if the environment var by the same name is not set to falsy value.
     *
     * @return bool Whether the feature provided is enabled or not.
     */
    public static function isEnabledInProduction();

    /**
     * Returns the constant, or environment variables, that will trigger the feature provider
     * registration when set to truthy values.
     *
     * A Feature Provider MUST use the same name for both the constant that will enable it if
     * defined AND true and for the environment variable that will disable it if set and falsy.
     *
     * @return string The name of the constant, or environment variable, that will trigger the
     *                feature provider registration when set to truthy values.
     */
    public static function getFeatureTrigger();

    /**
     * A Feature Provider MUST clearly indicate whether it did register or not.
     *
     * A Feature Provider might not register as it's not enabled or because its
     * requirements are not satisfied.
     *
     * @return bool Whether the Feature Provider did register, as enabled, or not.
     */
    public function register();
}
