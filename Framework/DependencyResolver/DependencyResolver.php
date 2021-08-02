<?php

namespace WPStaging\Framework\DependencyResolver;

use WPStaging\Framework\DependencyResolver\Exceptions\CircularReferenceException;
use WPStaging\Framework\DependencyResolver\Exceptions\MissingReferenceException;
use WPStaging\Framework\DependencyResolver\Exceptions\ResolveException;

/**
 * Class DependencyResolver
 *
 * This is a port of https://github.com/anthonykgross/dependency-resolver, adapted to run on our PHP requirement version.
 *
 * @package WPStaging\Framework\DependencyResolver
 */
class DependencyResolver
{
    /**
     * @throws ResolveException
     */
    public static function resolve(array $tree, ResolveBehaviour $resolveBehaviour = null)
    {
        if (is_null($resolveBehaviour)) {
            $resolveBehaviour = ResolveBehaviour::create()->setThrowOnCircularReference(true);
        }
        $resolved = [];
        $unresolved = [];

        // Resolve dependencies for each table
        foreach (array_keys($tree) as $table) {
            list($resolved, $unresolved, $returnImmediately) = self::resolver($table, $tree, $resolved, $unresolved, $resolveBehaviour);

            if ($returnImmediately) {
                return $resolved;
            }
        }

        return $resolved;
    }

    /**
     * @param int|string $item
     *
     * @throws ResolveException
     */
    private static function resolver($item, array $items, array $resolved, array $unresolved, ResolveBehaviour $resolveBehaviour)
    {
        $unresolved[] = $item;

        foreach ($items[$item] as $dep) {
            if (!array_key_exists($dep, $items)) {
                if ($resolveBehaviour->isThrowOnMissingReference()) {
                    throw new MissingReferenceException($item, $dep);
                }

                return [$resolved, $unresolved, true];
            }

            if (in_array($dep, $resolved, true)) {
                continue;
            }

            if (in_array($dep, $unresolved, true)) {
                if ($resolveBehaviour->isThrowOnCircularReference()) {
                    throw new CircularReferenceException($item, $dep);
                }

                return [$resolved, $unresolved, true];
            }

            $unresolved[] = $dep;
            list($resolved, $unresolved, $returnImmediately) = self::resolver($dep, $items, $resolved, $unresolved, $resolveBehaviour);

            if ($returnImmediately) {
                return [$resolved, $unresolved, $returnImmediately];
            }
        }

        // Add $item to $resolved if it's not already there
        if (!in_array($item, $resolved, true)) {
            $resolved[] = $item;
        }

        // Remove all occurrences of $item in $unresolved
        while (($index = array_search($item, $unresolved, true)) !== false) {
            unset($unresolved[$index]);
        }

        return [$resolved, $unresolved, false];
    }
}
