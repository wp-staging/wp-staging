<?php

namespace WPStaging\Framework\Experiments;

/**
 * Assigns this installation to a variant of an A/B experiment and remembers it.
 *
 * There is no central service to hand out buckets, so the split is drawn locally
 * and stored in wp_options. Once written it is never re-drawn.
 *
 * @see ExperimentsRegistry for the list of experiments.
 */
class ExperimentManager
{
    /** @var string Map of experiment id => assigned variant. Not autoloaded. */
    const OPTION_ASSIGNMENTS = 'wpstg_experiments';

    /** @var ExperimentsRegistry */
    private $registry;

    /** @var array<string, string>|null */
    private $assignments = null;

    public function __construct(ExperimentsRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * The variant this installation belongs to, assigning one on first call.
     *
     * Call this only from a surface that has already decided the installation is
     * eligible, so ineligible installations are never enrolled.
     *
     * @return string The variant name, or an empty string when the experiment
     *                is unknown or no longer running and nothing was assigned.
     */
    public function getVariant(string $experimentId): string
    {
        $assigned = $this->getAssignedVariant($experimentId);
        if ($assigned !== '') {
            return $assigned;
        }

        $experiment = $this->registry->get($experimentId);
        if ($experiment === null || !$experiment->isRunning()) {
            return '';
        }

        return $this->assign($experiment);
    }

    /**
     * The stored variant, without enrolling the installation.
     *
     * @return string Empty when this installation was never assigned, or when
     *                the stored variant is no longer part of the experiment.
     */
    public function getAssignedVariant(string $experimentId): string
    {
        $assignments = $this->getAssignments();
        if (!isset($assignments[$experimentId])) {
            return '';
        }

        $experiment = $this->registry->get($experimentId);
        if ($experiment === null || !$experiment->hasVariant($assignments[$experimentId])) {
            return '';
        }

        return $assignments[$experimentId];
    }

    public function isVariant(string $experimentId, string $variant): bool
    {
        return $this->getAssignedVariant($experimentId) === $variant;
    }

    /**
     * Experiment identity to attach to analytics events, so a staging site or
     * backup created long after the onboarding is still attributable.
     *
     * If several are ever assigned, the first declared one wins so the value
     * stays a stable scalar pair.
     *
     * @return array An `experiment` and `variant` pair, empty when this
     *               installation takes part in no experiment.
     */
    public function getAttribution(): array
    {
        foreach ($this->registry->all() as $experiment) {
            $variant = $this->getAssignedVariant($experiment->getId());
            if ($variant === '') {
                continue;
            }

            return [
                'experiment' => $experiment->getId(),
                'variant'    => $variant,
            ];
        }

        return [];
    }

    /**
     * Forget every assignment, so the next call draws again.
     */
    public function reset()
    {
        $this->assignments = null;
        delete_option(self::OPTION_ASSIGNMENTS);
    }

    private function assign(Experiment $experiment): string
    {
        // Not derived from the site: host names and salts correlate with hosting
        // provider and site age, which would bias the buckets.
        $variants = $experiment->getVariants();
        $variant  = $variants[wp_rand(0, count($variants) - 1)];

        // add_option() is atomic, so the loser of a race reads back the winner's draw.
        if (add_option(self::OPTION_ASSIGNMENTS, [$experiment->getId() => $variant], '', false)) {
            $this->assignments = [$experiment->getId() => $variant];

            return $variant;
        }

        $this->assignments = null;

        $assigned = $this->getAssignedVariant($experiment->getId());
        if ($assigned !== '') {
            return $assigned;
        }

        $assignments = $this->getAssignments();
        $assignments[$experiment->getId()] = $variant;
        update_option(self::OPTION_ASSIGNMENTS, $assignments, false);
        $this->assignments = $assignments;

        return $variant;
    }

    private function getAssignments(): array
    {
        if ($this->assignments !== null) {
            return $this->assignments;
        }

        $assignments = get_option(self::OPTION_ASSIGNMENTS, []);

        if (!is_array($assignments)) {
            $assignments = [];
        }

        $this->assignments = array_filter($assignments, 'is_string');

        return $this->assignments;
    }
}
