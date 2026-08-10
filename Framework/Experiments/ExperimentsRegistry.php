<?php

namespace WPStaging\Framework\Experiments;

/**
 * The list of A/B experiments WP STAGING knows about.
 *
 * Adding one means an entry here plus the surface that calls
 * {@see ExperimentManager::getVariant()}. Retire one by flipping its third
 * constructor argument to false rather than deleting the entry, so assigned
 * installations keep reporting and past results stay analysable.
 */
class ExperimentsRegistry
{
    /** @var string First-run experience for new WP STAGING Free installations. */
    const EXPERIMENT_FREE_ONBOARDING = 'free_onboarding_v1';

    /** @var string Current first-run page, unchanged. */
    const VARIANT_CONTROL = 'control';

    /** @var string Dedicated "What would you like to do first?" action selector. */
    const VARIANT_TASK_SELECTOR = 'task_selector';

    /** @var Experiment[] Keyed by experiment id. */
    private $experiments = [];

    public function __construct()
    {
        $this->experiments[self::EXPERIMENT_FREE_ONBOARDING] = new Experiment(
            self::EXPERIMENT_FREE_ONBOARDING,
            [self::VARIANT_CONTROL, self::VARIANT_TASK_SELECTOR]
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
