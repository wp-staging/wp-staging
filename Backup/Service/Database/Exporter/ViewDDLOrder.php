<?php

namespace WPStaging\Backup\Service\Database\Exporter;

use WPStaging\Framework\DependencyResolver\DependencyResolver;
use WPStaging\Framework\DependencyResolver\Exceptions\CircularReferenceException;
use WPStaging\Framework\DependencyResolver\Exceptions\MissingReferenceException;

class ViewDDLOrder
{
    protected $viewsToBeWritten = [];

    public function enqueueViewToBeWritten($viewName, $query)
    {
        $this->viewsToBeWritten[$viewName] = $query;
    }

    /**
     * @return array
     */
    public function tryGetOrderedViews()
    {
        // Early bail: No views to order.
        if (empty($this->viewsToBeWritten)) {
            return [];
        }

        $dependsOn = [];

        foreach ($this->viewsToBeWritten as $viewNameCurrent => $queryCurrent) {
            $dependsOn[$viewNameCurrent] = [];
            foreach ($this->viewsToBeWritten as $view => $query) {
                // Early bail: Current
                if ($viewNameCurrent === $view) {
                    continue;
                }

                // The current query has a dependency on this view
                if (strpos($queryCurrent, $view) !== false) {
                    $dependsOn[$viewNameCurrent][] = $view;
                }
            }
        }

        try {
            $orderedDependencies = DependencyResolver::resolve($dependsOn);
        } catch (CircularReferenceException $e) {
            /*
             * For some reason we tried to order something that has a circular dependency.
             *
             * Let's log this and return the unmodified views, as there's a big chance they won't conflict,
             * as they would only conflict if the views read data from other views, which is very rare.
             */
            \WPStaging\functions\debug_log('WP STAGING Backup Create: Circular dependency when creating views dependency tree. ' . $e->getMessage());

            return $this->viewsToBeWritten;
        } catch (MissingReferenceException $e) {
            // This should never happen, but we will return the unmodified views as well if it does.
            \WPStaging\functions\debug_log('WP STAGING Backup Create: Missing Reference when creating views dependency tree. ' . $e->getMessage());

            return $this->viewsToBeWritten;
        }

        $orderedViewsWithQueries = [];

        foreach ($orderedDependencies as $viewName) {
            $orderedViewsWithQueries[$viewName] = $this->viewsToBeWritten[$viewName];
        }

        return $orderedViewsWithQueries;
    }
}
