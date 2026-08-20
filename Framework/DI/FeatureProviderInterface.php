<?php







namespace WPStaging\Framework\DI;






interface FeatureProviderInterface
{









    public static function isEnabledInProduction();











    public static function getFeatureTrigger();









    public function register();
}
