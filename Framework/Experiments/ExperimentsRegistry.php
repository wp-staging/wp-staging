<?php

namespace WPStaging\Framework\Experiments;









class ExperimentsRegistry
{






    const EXPERIMENT_FREE_ONBOARDING = 'free_onboarding_v1';

 
    const VARIANT_CONTROL = 'control';

 
    const VARIANT_TASK_SELECTOR = 'task_selector';

 
    private $experiments = [];

    public function __construct()
    {
        $this->experiments[self::EXPERIMENT_FREE_ONBOARDING] = new Experiment(
            self::EXPERIMENT_FREE_ONBOARDING,
            [self::VARIANT_CONTROL, self::VARIANT_TASK_SELECTOR],
            false
        );
    }

    public function get(string $experimentId)
    {
        return isset($this->experiments[$experimentId]) ? $this->experiments[$experimentId] : null;
    }

    public function all(): array
    {
        return $this->experiments;
    }
}
