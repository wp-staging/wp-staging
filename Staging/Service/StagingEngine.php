<?php

namespace WPStaging\Staging\Service;

/**
 * Stores and validates the preferred staging engine for staging jobs.
 */
class StagingEngine
{
    /** @var string */
    const OPTION_NAME = 'wpstg_staging_engine_preference';

    /** @var string */
    const LEGACY_OPTION_NAME = 'wpstg_staging_engine_preferences';

    /** @var string */
    const ENGINE_LEGACY = 'legacy';

    /** @var string */
    const ENGINE_NEXT_GEN = 'next_gen';

    /** @var string[] */
    const ENGINES = [
        self::ENGINE_LEGACY,
        self::ENGINE_NEXT_GEN,
    ];

    public function getEngine(): string
    {
        $stored = get_option(self::OPTION_NAME, null);
        if ($stored === null) {
            $stored = get_option(self::LEGACY_OPTION_NAME, self::ENGINE_LEGACY);
        }

        return $this->resolveEngine($stored);
    }

    public function saveEngine(string $engine): bool
    {
        if (!$this->isValidEngine($engine)) {
            return false;
        }

        if (get_option(self::OPTION_NAME, null) === $engine) {
            return true;
        }

        return update_option(self::OPTION_NAME, $engine, false);
    }

    public function isValidEngine($engine): bool
    {
        return is_string($engine) && in_array($engine, self::ENGINES, true);
    }

    /**
     * @param mixed $stored
     */
    private function resolveEngine($stored): string
    {
        if ($this->isValidEngine($stored)) {
            return $stored;
        }

        if (!is_array($stored)) {
            return self::ENGINE_LEGACY;
        }

        $legacyActions = ['create', 'update', 'reset', 'push'];
        foreach ($legacyActions as $action) {
            if (isset($stored[$action]) && $stored[$action] === self::ENGINE_NEXT_GEN) {
                return self::ENGINE_NEXT_GEN;
            }
        }

        foreach ($legacyActions as $action) {
            if (isset($stored[$action]) && $this->isValidEngine($stored[$action])) {
                return $stored[$action];
            }
        }

        return self::ENGINE_LEGACY;
    }
}
