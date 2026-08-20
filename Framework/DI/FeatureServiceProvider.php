<?php














namespace WPStaging\Framework\DI;

use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Utils\Env;






abstract class FeatureServiceProvider extends ServiceProvider implements FeatureProviderInterface
{














    public static function getFeatureTrigger()
    {
        die('As I should not be invoked.');
        throw new WPStagingException('Every Feature Service Provider MUST define a feature trigger.');
    }











    public static function isEnabledInProduction()
    {
        $trigger = static::getFeatureTrigger();

        if (defined($trigger) && constant($trigger) === false) {
 
            return false;
        }

        $triggerValue = Env::get($trigger);
        if ($triggerValue !== false && (bool)$triggerValue === false) {
 
            return false;
        }

        return true;
    }











    public static function isEnabledInDevelopment()
    {
        $trigger = static::getFeatureTrigger();

        if (!defined($trigger)) {
            return false;
        }

        if (defined($trigger) && constant($trigger) === false) {
 
            return false;
        }

        $triggerValue = Env::get($trigger);
        if ($triggerValue !== false && (bool)$triggerValue === false) {
 
            return false;
        }

        return true;
    }
}
