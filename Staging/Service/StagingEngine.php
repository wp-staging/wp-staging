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

    /**
     * Master switch for the Next-Gen engine. Temporarily disabled while the
     * data-corruption issue #5346 is fixed. Flip to true to re-enable it
     * everywhere (UI, runtime routing and the prepare AJAX endpoints).
     *
     * @var bool
     */
    const NEXT_GEN_ENABLED = false;

    /** @var string[] */
    const ENGINES = [
        self::ENGINE_LEGACY,
        self::ENGINE_NEXT_GEN,
    ];

    public function isNextGenEnabled(): bool
    {
        return self::NEXT_GEN_ENABLED;
    }

    /**
     * Effective engine used at runtime. Coerces Next-Gen to Classic while the
     * Next-Gen engine is disabled so all routing falls back to the Classic path.
     */
    public function getEngine(): string
    {
        $engine = $this->getStoredEngine();
        if ($engine === self::ENGINE_NEXT_GEN && !$this->isNextGenEnabled()) {
            return self::ENGINE_LEGACY;
        }

        return $engine;
    }

    /**
     * Raw engine preference as saved by the user, ignoring the master switch.
     * Used by the upgrade routine to detect Next-Gen users before reverting them.
     */
    public function getStoredEngine(): string
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
