<?php

namespace WPStaging\Framework\Experiments;

/**
 * Immutable definition of a single A/B experiment. Which variant an installation
 * belongs to is stored by {@see ExperimentManager}.
 *
 * Every variant must offer the same feature set: experiments compare user
 * experience, never entitlement.
 */
class Experiment
{
    /** @var string */
    private $id;

    /** @var string[] */
    private $variants;

    /** @var bool */
    private $isRunning;

    /**
     * @param string   $id       Snake case identifier, also used as the analytics `experiment` value.
     * @param string[] $variants Variant names, control first. At least two.
     * @param bool     $isRunning False retires the experiment: no new assignments are made,
     *                            existing ones keep reporting so past cohorts stay analysable.
     */
    public function __construct(string $id, array $variants, bool $isRunning = true)
    {
        $this->id        = $id;
        $this->variants  = array_values($variants);
        $this->isRunning = $isRunning;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function hasVariant(string $variant): bool
    {
        return in_array($variant, $this->variants, true);
    }
}
