<?php

namespace WPStaging\Framework\Experiments;








class Experiment
{
 
    private $id;

 
    private $variants;

 
    private $isRunning;







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
