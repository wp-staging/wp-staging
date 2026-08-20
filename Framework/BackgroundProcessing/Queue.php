<?php

 







namespace WPStaging\Framework\BackgroundProcessing;

use DateTimeImmutable;
use Exception;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient as Database;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\Traits\BenchmarkTrait;

use function WPStaging\functions\debug_log;






class Queue
{
    use WithQueueAwareness;

    use BenchmarkTrait;






    const TABLE_NOT_EXIST = -1;
    const TABLE_EXISTS    = 0;
    const TABLE_CREATED   = 1;





    const STATUS_READY      = 'ready';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_ANY        = 'any';
    const STATUS_CANCELED   = 'canceled';

 
    const OPTION_HTTP_AUTH_CREDENTIALS = 'wpstg_background_http_auth_credentials';




    const QUEUE_TABLE_NAME = 'wpstg_queue';






    const QUEUE_TABLE_VERSION_KEY = 'wpstg_queue_table_version';





    const QUEUE_TABLE_STRUCTURE_VERSION_KEY = 'wpstg_queue_table_structure_version';






    const QUEUE_TABLE_STRUCTURE_VERSION = '1.0.0';

 
    const STALLED_ACTIONS_BREAKPOINT_IN_MINS = 15;

 
    const SET_UPDATED_AT_TO_NOW = true;






    protected $featureDetection;









    private $tableState;






    private $logger;






    private $defaultHydrateStatuses = [self::STATUS_READY];






    private $actionCaches = [];







    private $database;






    private $unlocker;

 
    private $phpAdapter;








    public function __construct($database = null)
    {
        $services               = WPStaging::getInstance()->getContainer();
        $this->database         = $database ?: $services->make(DatabaseAdapter::class)->getClient();
        $this->logger           = $services->make('logger');
        $this->featureDetection = $services->make(FeatureDetection::class);
        $this->phpAdapter       = $services->make(PhpAdapter::class);
    }




















    public function enqueueAction($action, array $args = [], $jobId = 'default', $priority = 0)
    {
 
        $this->featureDetection->isAjaxAvailable(true);

 
        $actionObject = new Action(0, $action, $args, $jobId, $priority);

        if (!$this->tableExists()) {
 
            $this->checkTable(true);
        }

        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
 
            return false;
        }

        $assignments = [
            'action'     => $actionObject->action,
            'jobId'      => (string)$actionObject->jobId,
            'status'     => self::STATUS_READY,
            'priority'   => (int)$actionObject->priority,
            'args'       => $actionObject->args,
            'updated_at' => current_time('mysql'),
        ];

        $assignmentsList = $this->buildAssignmentsList($assignments);

        $tableName = self::getTableName();
        $query     = "INSERT INTO {$tableName} SET {$assignmentsList}";

        $result = $this->database->query($query);

        if ($result === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying insert Action information.',
                'class' => get_class($this),
                'query' => $query,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        $id = $this->database->insertId();

        if (empty($id)) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to fetch last inserted Action ID.',
                'class' => get_class($this),
                'query' => $query,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        $actionObject            = $actionObject->alter(['id' => $id, 'status' => self::STATUS_READY]);
        $this->actionCaches[$id] = $actionObject->toArray();

        set_site_transient(
            BackgroundProcessingServiceProvider::TRANSIENT_QUEUE_HAS_WORK,
            1,
            BackgroundProcessingServiceProvider::QUEUE_HAS_WORK_TTL
        );

        if (!has_action('shutdown', [$this, 'maybeFireAjaxAction'])) {
            add_action('shutdown', [$this, 'maybeFireAjaxAction']);
        }

        return (int)$id;
    }












    public function checkTable($force = false)
    {
        if (!$force && $this->tableState !== null) {
            return $this->tableState;
        }

        $this->tableState = self::TABLE_NOT_EXIST;

        $currentTableVersion = $this->getCurrentTableVersion();

 
        if (version_compare($currentTableVersion, $this->getLatestTableVersion(), '<') || !$this->tableExists()) {
            $tableState = $this->updateTable();

            if ($tableState === self::TABLE_EXISTS) {
 
                $this->tableState = self::TABLE_EXISTS;
 
                return self::TABLE_CREATED;
            }
        }

        $this->tableState = $this->tableExists() ? self::TABLE_EXISTS : self::TABLE_NOT_EXIST;

        return $this->tableState;
    }






    protected function getCurrentTableVersion()
    {
        return get_option(self::QUEUE_TABLE_STRUCTURE_VERSION_KEY, '0.0.0');
    }






    protected function getLatestTableVersion()
    {
        return self::QUEUE_TABLE_STRUCTURE_VERSION;
    }










    private function updateTable()
    {
        $tableSql = $this->getCreateTableSql();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $dbdeltaQueries = [];

        $this->addUpgradeQueries($dbdeltaQueries);

 
        $collectDbdeltaQueries = static function ($queries) use (&$dbdeltaQueries, &$collectDbdeltaQueries) {
 
            remove_filter('dbdelta_queries', $collectDbdeltaQueries);
            $dbdeltaQueries = array_merge($queries, $dbdeltaQueries);

 
            return [];
        };

        add_filter('dbdelta_queries', $collectDbdeltaQueries);
        dbDelta($tableSql, false);

 
        if ($this->database->query('START TRANSACTION') === false) {
            return self::TABLE_NOT_EXIST;
        }

        foreach ($dbdeltaQueries as $query) {
            if ($this->database->query($query) === false) {
                debug_log('Queue Table Upgrade Error: ' . $this->database->error());
                $this->database->query('ROLLBACK');
                return self::TABLE_NOT_EXIST;
            }
        }

        if ($this->database->query('COMMIT') === false) {
            return self::TABLE_NOT_EXIST;
        }

        $this->updateTableVersionOption($this->getLatestTableVersion());

        return self::TABLE_EXISTS;
    }







    public static function getTableName()
    {
        global $wpdb;

        return $wpdb->prefix . self::QUEUE_TABLE_NAME;
    }











    private function updateTableVersionOption($tableVersion)
    {
        update_option(self::QUEUE_TABLE_STRUCTURE_VERSION_KEY, $tableVersion);
    }






    public function tableExists()
    {
        $tableName = self::getTableName();
        $result    = $this->database->query("SHOW TABLES LIKE '{$tableName}'");

        if ($result === false) {
            return false;
        }

        $value = $this->database->fetchRow($result);

        return  $value === [$tableName];
    }










    public function getActionField($actionId, $field)
    {
        if (empty($this->actionCaches[$actionId])) {
 
            $this->hydrateActionCaches([$actionId]);
        }

        return isset($this->actionCaches[$actionId][$field]) ?
            $this->actionCaches[$actionId][$field]
            : null;
    }

















    private function hydrateActionCaches(array $actionIds = [])
    {
        $tableState = $this->checkTable();

        if ($tableState === self::TABLE_CREATED || $tableState === false) {
 
            return;
        }

        $queueTable = self::getTableName();








        $offset              = 0;
        $limit               = 100;
        $inputActionIdsCount = count($actionIds);
        $totalResultsCount   = 0;

        do {
            $offsetAndLimit = sprintf('%d, %d', $offset, $limit);

            if ($inputActionIdsCount > 0) {
                $ids   = implode(',', array_filter(array_map('absint', $actionIds)));
                $query = "SELECT * FROM {$queueTable} q JOIN {$queueTable} q1 ON q.status = q1.status WHERE q1.id IN ({$ids}) LIMIT {$offsetAndLimit}";
            } else {
                $stati = implode(',', array_map(function ($status) {
                    return "'{$this->database->escape($status)}'";
                }, $this->defaultHydrateStatuses));
                $query = "SELECT * FROM {$queueTable} WHERE status IN ({$stati}) LIMIT {$offsetAndLimit}";
            }

            $result = $this->database->query($query);

            if ($result === false) {
                \WPStaging\functions\debug_log(json_encode([
                    'root'  => 'Error while trying to fetch Actions information.',
                    'class' => get_class($this),
                    'query' => $query,
                    'error' => $this->database->error(),
                ]));

 
                return;
            }

            $preparedActions = [];
            while ($actionRow = $this->database->fetchAssoc($result)) {
                $totalResultsCount++;
                $preparedActions[$actionRow['id']] = $this->convertDbRowToData($actionRow);
            }

            $found = $inputActionIdsCount === count(array_diff_key($preparedActions, array_flip($actionIds)));

            if (!isset($foundRows)) {
                $foundRows = max(0, (int)$this->database->foundRows());
            }

            $offset += $limit;
        } while (!$found && $totalResultsCount < $foundRows);

        $this->actionCaches = array_replace($this->actionCaches, $preparedActions);
    }










    private function convertDbRowToData(array $actionRow)
    {
        return Action::fromDbRow($actionRow)->toArray();
    }










    public function getNextAvailable()
    {
        if ($this->checkTable() !== self::TABLE_EXISTS) {
 
            debug_log('Queue getNextAvailable: Table does not exist for getting the next available.', 'debug', false);
            return null;
        }

        $processing = self::STATUS_PROCESSING;
        $ready      = self::STATUS_READY;
        $tableName  = self::getTableName();
        $now        = current_time('mysql');

        $this->unlockQueueTable();

        $this->database->query("LOCK TABLE `$tableName` WRITE");

        if ($this->count($processing) > 0) {
            debug_log('Queue getNextAvailable: There is an action already in process. Stop!', 'debug', false);
            $this->database->query("UNLOCK TABLES");
            return null;
        }

        $claimIdQuery = "SELECT id FROM {$tableName}
                        WHERE status = '{$ready}'
                        ORDER BY priority, action, jobId ASC LIMIT 1";
        $claimedId = $this->database->query($claimIdQuery);

        if (!$claimedId) {
 
            debug_log('Queue getNextAvailable returns null because claimed Id was empty. This query failed: ' . $claimIdQuery, 'debug', false);
            $this->database->query("UNLOCK TABLES");
            return null;
        }

        $claimedId = $this->database->fetchAssoc($claimedId);

        if (!is_array($claimedId) || !array_key_exists('id', $claimedId)) {
            debug_log('Queue getNextAvailable returns null because claimedID query does not return an array or "id" does not exist. This query failed: ' . $claimIdQuery, 'debug', false);
            $this->database->query("UNLOCK TABLES");
            return null;
        }

        $claimedActionId = $claimedId['id'];






        $claimQuery = "UPDATE {$tableName}
            SET status='{$processing}', claimed_at='{$now}'
            WHERE id=$claimedActionId;";
        $claimed = $this->database->query($claimQuery);
        $this->database->query("UNLOCK TABLES");

        if (!$claimed) {
 
            debug_log('Queue getNextAvailable returns null the process could not lock the row. This query failed: ' . $claimQuery, 'debug', false);
            return null;
        }

 
        unset($this->actionCaches[$claimedActionId]);
        $actionObject = $this->getAction($claimedActionId);

        if ($actionObject instanceof Action) {
            $this->actionCaches[$claimedActionId] = $actionObject->toArray();
        }

        return $actionObject;
    }















    public function count($status = null, $jobId = null)
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $tableName = self::getTableName();

        $jobClause = '';
        if (isset($jobId)) {
            $jobIdsInterval = $this->escapeInterval((array)$jobId);
            $jobClause      = "AND jobId IN ({$jobIdsInterval})";
        }

        if (empty($status) || $status === Queue::STATUS_ANY) {
            $countQuery = "SELECT COUNT(id) FROM {$tableName} WHERE 1=1 {$jobClause}";
        } else {
            $statuses   = $this->escapeInterval((array)$status);
            $countQuery = "SELECT COUNT(id) FROM {$tableName} WHERE status IN ({$statuses}) {$jobClause}";
        }

        $countResult = $this->database->query($countQuery);

        if ($countResult === false) {
            $error = $this->database->error();

            if (!empty($error)) {
                \WPStaging\functions\debug_log(json_encode([
                    'root'  => 'Error while trying to count Actions.',
                    'class' => get_class($this),
                    'query' => $countQuery,
                    'error' => $error,
                ]));
            }

 
            return 0;
        }

        $count = $this->database->fetchRow($countResult);

        return (array_sum((array)$count));
    }




    public function getLastUpdatedAtTimestamp()
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $tableName = self::getTableName();
        $ready     = self::STATUS_READY;
        $proc      = self::STATUS_PROCESSING;

        $query  = "SELECT MAX(updated_at) FROM {$tableName} WHERE status IN ('{$ready}','{$proc}')";
        $result = $this->database->query($query);

        if ($result === false) {
            return 0;
        }

        $row = $this->database->fetchRow($result);
        if (empty($row)) {
            return 0;
        }

        $value = is_array($row) ? reset($row) : $row;
        if (empty($value)) {
            return 0;
        }

 
 
        $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone('UTC');
        try {
            $parsed = new \DateTimeImmutable((string)$value, $timezone);
            return $parsed->getTimestamp();
        } catch (\Exception $e) {
            return 0;
        }
    }



















    public function updateActionStatus($action, $newStatus, $unsafely = false)
    {
        $actionId  = absint($action instanceof Action ? $action->id : (int)$action);
        $tableName = self::getTableName();
        $status    = $this->database->escape($newStatus);
        $now       = current_time('mysql');

        $this->unlockQueueTable();

        if ($status !== self::STATUS_PROCESSING) {
 
            $statusUpdateQuery = "UPDATE {$tableName} SET status='{$status}', claimed_at=NULL, updated_at='{$now}' WHERE id={$actionId}";
        } else {
            if (!$unsafely) {
 
                throw new QueueException('Marking actions as Processing should only be done using the getNextAvailable method!');
            }

 
            $statusUpdateQuery = "UPDATE {$tableName} SET status='{$status}', claimed_at='{$now}', updated_at='{$now}' WHERE id={$actionId} ";
        }

        $updated = $this->database->query($statusUpdateQuery);

        if (!$updated && $this->reconnectDatabase()) {
            $updated = $this->database->query($statusUpdateQuery);
        }

        if (!$updated) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to update Action status.',
                'class' => get_class($this),
                'query' => $statusUpdateQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

 
        unset($this->actionCaches[$actionId]);

        return $actionId;
    }






    private function getCreateTableSql()
    {
        global $wpdb;
        $collate    = $wpdb->collate;
        $queueTable = self::getTableName();
        $tableSql   = "CREATE TABLE IF NOT EXISTS {$queueTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            action VARCHAR(1000) NOT NULL,
            jobId VARCHAR(1000) DEFAULT NULL,
            status CHAR(20) NOT NULL DEFAULT 'ready',
            priority BIGINT(20) NOT NULL DEFAULT 0,
            args LONGTEXT DEFAULT NULL,
            custom LONGTEXT DEFAULT NULL,
            claimed_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id)
            )";

        if (!empty($collate)) {
            $tableSql .= " COLLATE {$collate}";
        }

        return $tableSql;
    }









    public function dropTable()
    {
        $tableName = self::getTableName();
        $query     = "DROP TABLE IF EXISTS {$tableName}";
        $this->database->query($query);
        $this->tableState = self::TABLE_NOT_EXIST;

        return !$this->tableExists();
    }







    public function lastError()
    {
        if ($this->database === null) {
            return '';
        }

        return (string)$this->database->error();
    }










    private function fetchActionRow($actionId)
    {
        $actionId = absint($actionId);

        if (empty($actionId)) {
            return null;
        }

        $tableName   = self::getTableName();
        $fetchQuery  = "SELECT * FROM {$tableName} WHERE id={$actionId}";
        $fetchResult = $this->database->query($fetchQuery);

        if ($fetchResult === false) {
 
            return null;
        }

        $row = $this->database->fetchAssoc($fetchResult);

        return is_array($row) ? $row : null;
    }















    public function getAction($actionId, $force = false)
    {
        debug_log('Queue getAction is trying to get action ID ' . $actionId, 'debug', false);
        if ($force || empty($this->actionCaches[$actionId])) {
            $row = $this->fetchActionRow($actionId);

            debug_log(wp_json_encode($row), 'debug', false);

            if ($row !== null) {
                $this->actionCaches[$actionId] = $row;
            }
        }

        return isset($this->actionCaches[$actionId]) ?
            Action::fromDbRow($this->actionCaches[$actionId])
            : null;
    }











    public function getSupportedActionStatuses()
    {
        return [
            self::STATUS_PROCESSING,
            self::STATUS_READY,
            self::STATUS_COMPLETED,
        ];
    }









    public function getDanglingBreakpointDate()
    {
        return $this->getBreakpointDate(HOUR_IN_SECONDS);
    }


















    public function markDanglingAs(string $newStatus, $breakpointDate = null, bool $updateUpdatedAt = false): int
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Queue markDanglingAs: The table does not exist so there is nothing to update.', 'debug', false);
            return 0;
        }

        $this->unlockQueueTable();

        $tableName          = self::getTableName();
        $newStatus          = $this->database->escape($newStatus);
        $danglingBreakpoint = empty($breakpointDate) ? $this->getDanglingBreakpointDate()->format('Y-m-d H:i:s') : $breakpointDate->format('Y-m-d H:i:s');
        $now                = current_time('mysql');
        $updatedAtQuery     = $updateUpdatedAt ? ", updated_at='{$now}'" : '';
        $markQuery          = "UPDATE {$tableName} 
            SET status='{$newStatus}', claimed_at=NULL{$updatedAtQuery}
            WHERE claimed_at IS NOT NULL
            AND claimed_at < '{$danglingBreakpoint}'";
        $markResult = $this->database->query($markQuery);

        if ($markResult === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to mark dangling Actions.',
                'class' => get_class($this),
                'query' => $markQuery,
                'error' => $this->database->error(),
            ]));

            return 0;
        }

        if (isset($this->database->link->affected_rows)) {
            $marked = $this->database->link->affected_rows;
        } else {
            $marked = 0;
        }

        debug_log("Marked $marked actions as dangling.", 'debug', false);

        return (int)$marked;
    }









    public function maybeFireAjaxAction()
    {
 
        if (!$this->count(self::STATUS_READY)) {
            return false;
        }

 
        return $this->fireAjaxAction();
    }









    private function escapeInterval(array $values)
    {
        return implode(',', array_map(function ($value) {
            return "'" . $this->database->escape($value) . "'";
        }, (array)$values));
    }











    public function cancelJob($jobId)
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Queue cancelJob: The table does not exist so there is nothing to cancel.', 'info', false);
            return 0;
        }

        $this->unlockQueueTable();

        $tableName      = self::getTableName();
        $newStatus      = self::STATUS_CANCELED;
        $jobIds         = (array)$jobId;
        $jobIdsInterval = $this->escapeInterval($jobIds);
        $now            = current_time('mysql');
        $cancelQuery    = "UPDATE {$tableName} 
            SET status='{$newStatus}', updated_at='{$now}'
            WHERE jobId in ({$jobIdsInterval})
            AND status NOT IN ('" . self::STATUS_COMPLETED . "', '" . self::STATUS_CANCELED . "', '" . self::STATUS_FAILED . "')";
        $cancelResult = $this->database->query($cancelQuery);

        if ($cancelResult === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to cancel Actions.',
                'class' => get_class($this),
                'query' => $cancelQuery,
                'error' => $this->database->error(),
            ]));

            return 0;
        }

        if (isset($this->database->link->affected_rows)) {
            $canceled = $this->database->link->affected_rows;
        } else {
            $canceled = 0;
        }

        $this->invalidateActionCachesByJobId($jobIds);

        return (int)$canceled;
    }









    private function invalidateActionCachesByJobId(array $jobIds)
    {
        array_walk($this->actionCaches, static function (&$cachedAction) use ($jobIds) {
            if (!empty($cachedAction['jobId']) && in_array($cachedAction['jobId'], $jobIds, true)) {
                $cachedAction = null;
            }
        });
    }




















    public function updateActionFields($action, array $updates, $unsafely = false)
    {
        if (!$unsafely) {
 
            throw new QueueException(
                'Updating Action fields has the potential of disrupting the Queue functions.'
            );
        }

        if (isset($updates['id'])) {
 
            throw new QueueException(
                'Updating an Action ID is never allowed.'
            );
        }

        $actionId  = absint($action instanceof Action ? $action->id : (int)$action);
        $tableName = self::getTableName();

        $assignmentsList   = $this->buildAssignmentsList($updates);
        $statusUpdateQuery = "UPDATE {$tableName} SET {$assignmentsList} WHERE id={$actionId}";

        $this->unlockQueueTable();

        $updated = $this->database->query($statusUpdateQuery);

        if (!$updated && $this->reconnectDatabase()) {
            $updated = $this->database->query($statusUpdateQuery);
        }

        if ($updated === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to update Action field.',
                'class' => get_class($this),
                'query' => $statusUpdateQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

 
        unset($this->actionCaches[$actionId]);

        return $actionId;
    }









    private function buildAssignmentsList(array $assignments)
    {
        $assignmentList = [];

        array_walk($assignments, function ($value, $key) use (&$assignmentList) {
            if ($value === '') {
 
                return;
            }

            $escapedKey = $this->database->escape($key);

            if ($key === 'priority') {
 
                $escapedValue     = (int)$value;
                $assignmentList[] = "{$escapedKey}={$escapedValue}";
            } elseif ($key === 'args' || $key === 'custom') {
                global $wpdb;
                $assignmentList[] = $wpdb->prepare("{$escapedKey}=%s", maybe_serialize($value));
            } else {
                $escapedValue     = $this->database->escape($value);
                $assignmentList[] = "{$escapedKey}='{$escapedValue}'";
            }
        });

        return implode(', ', $assignmentList);
    }








    public function getCleanupBreakpointDate(): DateTimeImmutable
    {
        return $this->getBreakpointDate(WEEK_IN_SECONDS);
    }








    public function getStalledBreakpointDate(): DateTimeImmutable
    {
        return $this->getBreakpointDate(self::STALLED_ACTIONS_BREAKPOINT_IN_MINS * MINUTE_IN_SECONDS);
    }







    public function cleanup()
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Queue Cleanup: The table does not exist so there is nothing to update.', 'info', false);
            return 0;
        }

        $tableName         = self::getTableName();
        $cleanupBreakpoint = $this->getCleanupBreakpointDate()->format('Y-m-d H:i:s');
        $cleanableStati    = $this->escapeInterval([
            self::STATUS_READY,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELED,
        ]);
        $cleanupQuery = "DELETE FROM {$tableName} 
            WHERE updated_at < '{$cleanupBreakpoint}'
            AND status in ({$cleanableStati})";
        $cleanupResult = $this->database->query($cleanupQuery);

        if ($cleanupResult === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to cleanup Actions.',
                'class' => get_class($this),
                'query' => $cleanupQuery,
                'error' => $this->database->error(),
            ]));

            return 0;
        }

        if (isset($this->database->link->affected_rows)) {
            $removed = $this->database->link->affected_rows;
        } else {
            $removed = 0;
        }

        debug_log("Removed $removed actions that were last updated before $cleanupBreakpoint.", 'info', false);

        return $removed;
    }









    public function countActionsByScheduleId($scheduleId, $statuses = [])
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Count actions by ScheduleId: The table does not exist so there is nothing to do.', 'info', false);
            return 0;
        }

        $tableName = self::getTableName();

        $countQuery = "SELECT COUNT(*) as actions_count FROM {$tableName} 
            WHERE {$this->getWhereConditionByScheduleIdAndStatus($scheduleId, $statuses)};";

        $countResult = $this->database->query($countQuery);

        if ($countResult === false) {
            debug_log(json_encode([
                'root'  => 'Error while trying to count Actions for the scheduleId: "' . $scheduleId . '".',
                'class' => get_class($this),
                'query' => $countQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        if ($this->database->numRows($countResult) === 0) {
            return 0;
        }

        $count = $this->database->fetchAssoc($countResult);

        return (int)$count['actions_count'];
    }






    public function cleanupActionsByScheduleId($scheduleId, $statuses = [])
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Actions Cleanup by ScheduleId: The table does not exist so there is nothing to update.', 'info', false);
            return 0;
        }

        $tableName = self::getTableName();

        $this->startBenchmark();
        $cleanupQuery  = "DELETE FROM {$tableName} WHERE {$this->getWhereConditionByScheduleIdAndStatus($scheduleId, $statuses)};";
        $cleanupResult = $this->database->query($cleanupQuery);
        $this->finishBenchmark('cleanupActionsByScheduleId clean up query . ' . $cleanupQuery);

        if ($cleanupResult === false) {
            debug_log(json_encode([
                'root'  => 'Error while trying to cleanup Actions for the scheduleId: "' . $scheduleId . '".',
                'class' => get_class($this),
                'query' => $cleanupQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        if (isset($this->database->link->affected_rows)) {
            $removed = $this->database->link->affected_rows;
        } else {
            $removed = 0;
        }

        debug_log("Removed $removed actions for the scheduleId: '$scheduleId'.", 'info', false);

        return $removed;
    }





    public function purgeQueueTable()
    {
        if (static::TABLE_NOT_EXIST === $this->checkTable()) {
            debug_log('Queue Cleanup: The table does not exist so there is nothing to update.', 'info', false);
            return false;
        }

        $tableName = self::getTableName();

        $cleanupQuery  = "TRUNCATE {$tableName}";
        $cleanupResult = $this->database->query($cleanupQuery);

        if ($cleanupResult === false) {
            \WPStaging\functions\debug_log(json_encode([
                'root'  => 'Error while trying to cleanup Actions.',
                'class' => get_class($this),
                'query' => $cleanupQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        if (isset($this->database->link->affected_rows)) {
            $removed = $this->database->link->affected_rows;
        } else {
            $removed = 0;
        }

        debug_log("Removed $removed actions from the queue during cleanup.", 'info', false);

        return $removed;
    }







    public function getLatestUpdatedAction($jobId)
    {
        if (!is_string($jobId) || $this->tableState === self::TABLE_NOT_EXIST) {
            return null;
        }

        $tableName    = self::getTableName();
        $escapedJobId = $this->database->escape(trim($jobId));
        $query        = "SELECT id FROM $tableName WHERE jobId = '$escapedJobId' ORDER BY updated_at DESC, id DESC LIMIT 1";

        $result = $this->database->query($query);

        if ($result === false) {
            error_log(json_encode([
                'root'  => 'Error while trying to fetch latest updated Action.',
                'class' => get_class($this),
                'query' => $query,
                'error' => $this->database->error(),
                'jobId' => $jobId,
            ]));

 
            return null;
        }

        $row = $this->database->fetchAssoc($result);

        if (!isset($row['id'])) {
 
            return null;
        }

        return $this->getAction($row['id']);
    }










    public function setUnlocker($unlocker)
    {
        $this->unlocker = $unlocker;

        return $this;
    }




    public function maybeAddResponseColumnToTable(): bool
    {
        $tablename = self::getTableName();
 
        $query  = "SHOW COLUMNS FROM {$tablename} LIKE 'response'";
        $result = $this->database->query($query);

 
        if ($result === true) {
            return true;
        }

        return $this->database->query($this->getQueryToAddResponseColumnToTable($tablename));
    }





    protected function getQueryToAddResponseColumnToTable(string $tablename): string
    {
        return "ALTER TABLE `{$tablename}` ADD COLUMN `response` LONGTEXT DEFAULT NULL AFTER `args`";
    }




    protected function addUpgradeQueries(&$dbdeltaQueries)
    {
        $tablename           = self::getTableName();
        $currentTableVersion = $this->getCurrentTableVersion();

        $this->maybeAddUpgradeTableQueryForResponseField($tablename, $currentTableVersion, $dbdeltaQueries);
    }







    protected function maybeAddUpgradeTableQueryForResponseField(string $tablename, string $version, array &$dbdeltaQueries)
    {
 
        $deprecatedTableVersionOption = get_option(self::QUEUE_TABLE_VERSION_KEY, false);

 
        if (version_compare($version, '1.0.0', '<') && $deprecatedTableVersionOption === false) {
            $dbdeltaQueries[] = $this->getQueryToAddResponseColumnToTable($tablename);
            return;
        }

 
        delete_option(self::QUEUE_TABLE_VERSION_KEY);
        if (version_compare($deprecatedTableVersionOption, '4.9.1', '<')) {
            $dbdeltaQueries[] = $this->getQueryToAddResponseColumnToTable($tablename);
        }
    }







    private function unlockQueueTable()
    {
        if (!$this->phpAdapter->isCallable($this->unlocker)) {
            return;
        }

        call_user_func($this->unlocker);
    }







    private function getWhereConditionByScheduleIdAndStatus($scheduleId, $statuses = [])
    {
        $scheduleIdSerializedRow = 's:10:"scheduleId";s:' . strlen((string)$scheduleId) . ':"' . $scheduleId . '";';
        $whereCondition          = "args LIKE '%$scheduleIdSerializedRow%'";
        if (empty($statuses)) {
            return $whereCondition;
        }

        $statuses = array_map(function ($status) {
            return "'" . $this->database->escape($status) . "'";
        }, $statuses);

        $whereCondition .= " AND status IN (" . implode(',', $statuses) . ")";

        return $whereCondition;
    }




    private function reconnectDatabase(): bool
    {
        if (stripos($this->database->error(), 'MySQL server has gone away') !== false) {
            $this->database = WPStaging::make(DatabaseAdapter::class)->getClient();
            return true;
        }

        return false;
    }









    private function getBreakpointDate($interval): DateTimeImmutable
    {
        try {
            $breakpointDate = new DateTimeImmutable(date('Y-m-d H:i:s'));
            $breakpointDate = $breakpointDate->setTimestamp($breakpointDate->getTimestamp() - $interval);
        } catch (Exception $e) {




            $breakpointDate = new DateTimeImmutable('2020-01-01 00:00:00');
        }

        return $breakpointDate;
    }
}
