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

    public function tryGetOrderedViews()
    {
        if (empty($this->viewsToBeWritten)) {
            return [];
        }
        $dependsOn = [];
        foreach ($this->viewsToBeWritten as $viewNameCurrent => $queryCurrent) {
            $dependsOn[$viewNameCurrent] = [];
            foreach ($this->viewsToBeWritten as $view => $query) {
                if ($viewNameCurrent === $view) {
                    continue;
                }
                if (strpos($queryCurrent, $view) !== false) {
                    $dependsOn[$viewNameCurrent][] = $view;
                }
            }
        }
        try {
            $orderedDependencies = DependencyResolver::resolve($dependsOn);
        } catch (CircularReferenceException $e) {
            \WPStaging\functions\debug_log('WP STAGING Backup Create: Circular dependency when creating views dependency tree. ' . $e->getMessage());
            return $this->viewsToBeWritten;
        } catch (MissingReferenceException $e) {
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
