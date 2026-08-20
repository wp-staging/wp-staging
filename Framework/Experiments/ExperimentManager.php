<?php

namespace WPStaging\Framework\Experiments;









class ExperimentManager
{
 
    const OPTION_ASSIGNMENTS = 'wpstg_experiments';

 
    private $registry;

 
    private $assignments = null;

    public function __construct(ExperimentsRegistry $registry)
    {
        $this->registry = $registry;
    }










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




    public function reset()
    {
        $this->assignments = null;
        delete_option(self::OPTION_ASSIGNMENTS);
    }

    private function assign(Experiment $experiment): string
    {
 
 
        $variants = $experiment->getVariants();
        $variant  = $variants[wp_rand(0, count($variants) - 1)];

 
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
