<?php

namespace WPStaging\Framework\DependencyResolver;

use WPStaging\Framework\DependencyResolver\Exceptions\CircularReferenceException;
use WPStaging\Framework\DependencyResolver\Exceptions\MissingReferenceException;
use WPStaging\Framework\DependencyResolver\Exceptions\ResolveException;








class DependencyResolver
{




    public static function resolve(array $tree, $resolveBehaviour = null)
    {
        if (is_null($resolveBehaviour)) {
            $resolveBehaviour = ResolveBehaviour::create()->setThrowOnCircularReference(true);
        }

        $resolved = [];
        $unresolved = [];

 
        foreach (array_keys($tree) as $table) {
            list($resolved, $unresolved, $returnImmediately) = self::resolver($table, $tree, $resolved, $unresolved, $resolveBehaviour);

            if ($returnImmediately) {
                return $resolved;
            }
        }

        return $resolved;
    }






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

 
        if (!in_array($item, $resolved, true)) {
            $resolved[] = $item;
        }

 
        while (($index = array_search($item, $unresolved, true)) !== false) {
            unset($unresolved[$index]);
        }

        return [$resolved, $unresolved, false];
    }
}
