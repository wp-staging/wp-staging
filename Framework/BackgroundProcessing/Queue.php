<?php

/** @noinspection ForgottenDebugOutputInspection */

/**
 * Models the Queue of Steps to execute in background.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use DateTimeImmutable;
use Exception;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient as Database;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;

/**
 * Class Queue
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
class Queue
{
    use WithQueueAwareness;

    /**
     * A set of constants that are used internally to detect the
     * state of the custom table used by the class to persist and
     * manage its information.
     */
    const TABLE_NOT_EXIST = -1;
    const TABLE_EXISTS = 0;
    const TABLE_CREATED = 1;

    /**
     * A set of constants that are used to normalize the possible status
     * of an action in the context of the queue.
     */
    const STATUS_READY = 'ready';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_ANY = 'any';
    const STATUS_CANCELED = 'canceled';

    /**
     * Option name where we store queue table version
     * @var string
     */
    const QUEUE_TABLE_VERSION_KEY = 'wpstg_queue_table_version';

    /**
     * A reference to te current Background Processing Feature detection service.
     *
     * @var FeatureDetection
     */
    protected $featureDetection;

    /**
     * The current table state, or `null` if the current table state has never been
     * assessed before.
     *
     * @var string|null
     */
    private $tableState;

    /**
     * A reference to the current Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * A set of action stati that will be autoloaded in cache by the queue by default.
     *
     * @var array<string>
     */
    private $defaultHydrateStati = [self::STATUS_READY];

    /**
     * A map from cached actions IDs to their action fields.
     *
     * @var array<int,array<string,mixed>>
     */
    private $actionCaches = [];

    /**
     * A reference to the database adapter instance the class should use to interact
     * with the database.
     *
     * @var Database
     */
    private $database;

    /**
     * Queue constructor.
     *
     * @param Database|null $database          A reference to the database adapter instance the class
     *                                         should use to interact wit the database, or `null`  to use
     *                                         the one globally provided by the Service Locator.
     */
    public function __construct(Database $database = null)
    {
        $services = WPStaging::getInstance()->getContainer();
        $this->database = $database ?: $services->make(DatabaseAdapter::class)->getClient();
        $this->logger = $services->make('logger');
        $this->featureDetection = $services->make(FeatureDetection::class);
    }

    /**
     * Enqueue an action to run one time, as soon as possible
     *
     * This is like saying, in plain English, "do_action later".
     *
     * @param string                       $action   The hook to trigger, this will be the name of the
     *                                               action triggered, in the following request, by the
     *                                               WordPress `do_action` function.
     * @param array<string|int,mixed>|null $args     Optional arguments to pass when the hook triggers.
     * @param string                       $jobId    The ID of the Job that is enqueueing the step.
     * @param int                          $priority The priority to enqueue the action at; this works
     *                                               exactly like WordPres filter priority: lower values, negative
     *                                               values are supported, will be executed first.
     *
     * @return int|false The action ID, or `false` if the action could not be queued in the
     *                   table.
     *
     * @throws QueueException If there's any issue scheduling the action in the queue.
     */
    public function enqueueAction($action, array $args = [], $jobId = 'default', $priority = 0)
    {
        // We're enqueuing an Action and this is a good moment to let the user know whether AJAX works or not.
        $this->featureDetection->isAjaxAvailable(true);

        // Create the Action with an id of 0 until it's actually persisted.
        $actionObject = new Action(0, $action, $args, $jobId, $priority);

        if ($this->checkTable() === static::TABLE_NOT_EXIST) {
            // The table does not exist and cannot be created, bail.
            return false;
        }

        $assignments = [
            'action' => $actionObject->action,
            'jobId' => (string)$actionObject->jobId,
            'status' => self::STATUS_READY,
            'priority' => (int)$actionObject->priority,
            'args' => $actionObject->args,
        ];

        $assignmentsList = $this->buildAssignmentsList($assignments);

        $tableName = self::getTableName();
        $query = "INSERT INTO {$tableName} SET {$assignmentsList}";

        $result = $this->database->query($query, true);

        if (empty($result)) {
            error_log(json_encode([
                'root' => 'Error while trying insert Action information.',
                'class' => get_class($this),
                'query' => $query,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        $id = $this->database->insertId();

        if (empty($id)) {
            error_log(json_encode([
                'root' => 'Error while trying to fetch last inserted Action ID.',
                'class' => get_class($this),
                'query' => $query,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        $actionObject = $actionObject->alter(['id' => $id, 'status' => self::STATUS_READY]);
        $this->actionCaches[$id] = $actionObject->toArray();

        if (!has_action('shutdown', [$this, 'maybeFireAjaxAction'])) {
            add_action('shutdown', [$this, 'maybeFireAjaxAction']);
        }

        return (int)$id;
    }

    /**
     * Checks and reports the state of the table.
     *
     * If the table does not exist, then the method will try to create or update it.
     *
     * @param false $force Whether to force the check on the table or trust the state
     *                     cached from a previous check.
     *
     * @return int The value of one of the `TABLE` class constants to indicate the
     *             table status.
     */
    private function checkTable($force = false)
    {
        if (!$force && $this->tableState !== null) {
            return $this->tableState;
        }

        $this->tableState = self::TABLE_NOT_EXIST;

        $currentTableVersion = get_option(self::QUEUE_TABLE_VERSION_KEY, '0.0.0');

        // Trigger an update or creation if either the table should be update, or it does not exist.
        if (version_compare($currentTableVersion, $this->getLatestTableVersion(), '<') || !$this->tableExists()) {
            $tableState = $this->updateTable();

            if ($tableState === self::TABLE_EXISTS) {
                // The table now exists.
                $this->tableState = self::TABLE_EXISTS;
                // Just created.
                return self::TABLE_CREATED;
            }
        }

        $this->tableState = $this->tableExists() ? self::TABLE_EXISTS : self::TABLE_NOT_EXIST;

        return $this->tableState;
    }

    /**
     * Returns the latest table version.
     *
     * @return string The latest table version, in semantic format.
     */
    private function getLatestTableVersion()
    {
        if (defined('WPSTGPRO_VERSION')) {
            return WPSTGPRO_VERSION;
        }

        return '1.0.0';
    }

    /**
     * Updates the Queue table schema to the latest version, non destructively.
     *
     * The use of the `dbDelta` method will ensure the table is updated non-destructively
     * and only if required.
     *
     * @return int The value of one of the `TABLE` constants to indicate the result of the
     *             update.
     */
    private function updateTable()
    {
        global $wpdb;
        $tableSql = $this->getCreateTableSql();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $lastError = $wpdb->last_error;

        // If the table creation fails, then a db error will be thrown by WordPress.
        dbDelta($tableSql);

        if ($lastError !== $wpdb->last_error) {
            return self::TABLE_NOT_EXIST;
        }

        $this->updateTableVersionOption($this->getLatestTableVersion());

        return self::TABLE_EXISTS;
    }

    /**
     * Returns the name of the table used by the Queue to store the actions and their state.
     *
     * @return string The prefixed name of the table used by the Queue to store the actions
     *                and their state.
     */
    public static function getTableName()
    {
        global $wpdb;

        return $wpdb->prefix . 'wpstg_queue';
    }

    /**
     * Updates the version of the table in the plugin options to make sure
     * it will not be updated on next check.
     *
     * @param string $tableVersion A semantic version format representing the
     *                             table version to write to the plugin options.
     *
     * @return void The method does not return any value and will have the
     *              side-effect of updating the plugin options.
     */
    private function updateTableVersionOption($tableVersion)
    {
        update_option(self::QUEUE_TABLE_VERSION_KEY, $tableVersion);
    }

    /**
     * Checks whether the table exists or not.
     *
     * @return bool Whether the table exists or not.
     */
    private function tableExists()
    {
        $tableName = self::getTableName();
        $this->database->query("SELECT 1 from {$tableName} LIMIT 1");

        return $this->database->foundRows() === 1;
    }

    /**
     * Returns a specific field of an action data.
     *
     * @param int    $actionId The action ID.
     * @param string $field    The action field to return.
     *
     * @return mixed Either the action field from the specified action, or `null` if the
     *               action or the field cannot be found.
     */
    public function getActionField($actionId, $field)
    {
        if (empty($this->actionCaches[$actionId])) {
            // Maybe this is an action that is not autoloaded due to its status.
            $this->hydrateActionCaches([$actionId]);
        }

        return isset($this->actionCaches[$actionId][$field]) ?
            $this->actionCaches[$actionId][$field]
            : null;
    }

    /**
     * Hydrates the action cache using a set of action IDs as models for the status of the
     * actions to load into cache.
     *
     * By default, the class will hydrate the caches for actions that are running or to-run,
     * skipping actions that have failed or completed.
     * If a request comes in to fetch an action with a non-default status, then the cache
     * for all actions with that status will be hydrated.
     *
     * @param array<int> $actionIds An optional set of actions to use as "mode" to
     *                              hydrate the actions cache for all actions with that
     *                              same type.
     *
     * @return void The method does not return any value and will just hydrate the class caches.
     */
    private function hydrateActionCaches(array $actionIds = [])
    {
        $tableState = $this->checkTable();

        if ($tableState === self::TABLE_CREATED || $tableState === false) {
            // No point in trying to fetch anything.
            return;
        }

        $queueTable = self::getTableName();

        /**
         * Action arguments have the potential to be pretty huge in size.
         * To avoid over-loading the database, we fetch actions from it in
         * batches until we find either all the actions we're looking for, or
         * all the actions of a specific type.
         */

        $offset = 0;
        $limit = 100;
        $inputActionIdsCount = count($actionIds);
        $totalResultsCount = 0;

        do {
            $offsetAndLimit = sprintf('%d, %d', $offset, $limit);

            if ($inputActionIdsCount > 0) {
                $ids = implode(',', array_filter(array_map('absint', $actionIds)));
                $query = "SELECT * FROM {$queueTable} q JOIN {$queueTable} q1 ON q.status = q1.status WHERE q1.id IN ({$ids}) LIMIT {$offsetAndLimit}";
            } else {
                $stati = implode(',', array_map(function ($status) {
                    return "'{$this->database->escape($status)}'";
                }, $this->defaultHydrateStati));
                $query = "SELECT * FROM {$queueTable} WHERE status IN ({$stati}) LIMIT {$offsetAndLimit}";
            }

            $result = $this->database->query($query);

            if (false === $result) {
                error_log(json_encode([
                    'root' => 'Error while trying to fetch Actions information.',
                    'class' => get_class($this),
                    'query' => $query,
                    'error' => $this->database->error(),
                ]));

                // There has been an error fetching the results, bail.
                return;
            }

            $preparedActions = [];
            while ($actionRow = $this->database->fetchAssoc($result)) {
                $totalResultsCount++;
                $preparedActions[$actionRow['id']] = $this->convertDbRowToData($actionRow);
            }

            $found = count(array_diff_key($preparedActions, array_flip($actionIds))) === $inputActionIdsCount;

            if (!isset($foundRows)) {
                $foundRows = max(0, (int)$this->database->foundRows());
            }

            $offset += $limit;
        } while (!$found && $totalResultsCount < $foundRows);

        $this->actionCaches = array_replace($this->actionCaches, $preparedActions);
    }

    /**
     * Converts the data from the format it's stored with in the database to
     * the typed format used to retrieve the action data.
     *
     * @param array<string,mixed> $actionRow The data, as fetched from the database.
     *
     * @return array<string,mixed> The typed and prepared action data.
     */
    private function convertDbRowToData(array $actionRow)
    {
        return Action::fromDbRow($actionRow)->toArray();
    }

    /**
     * Tries to lock and get the next available action in the queue, in ascending
     * priority order.
     *
     * @return Action|null Either a reference to an object representing the locked
     *                     action, or `null` if there are no actions to process or
     *                     no lock could be acquired on the available ones.
     */
    public function getNextAvailable()
    {
        if ($this->checkTable() !== self::TABLE_EXISTS) {
            // No actions if the table either does nto exist or has just been created.
            return null;
        }

        $processing = self::STATUS_PROCESSING;
        $ready = self::STATUS_READY;
        $tableName = self::getTableName();
        $now = date('Y-m-d H:i:s');

        /*
         * Find the first available row that is ready, update its status to processing.
         * Do this with an "atomic" query that will either fully accomplish its goal
         * or will completely fail.
         */
        $claimQuery = "UPDATE {$tableName}
            SET status='{$processing}', claimed_at='{$now}'
            WHERE id=(
                SELECT id FROM {$tableName}
                WHERE status='{$ready}'
                ORDER BY priority, action, jobId ASC
                LIMIT 1   
            )
            AND LAST_INSERT_ID(id);";
        $claimed = $this->database->query($claimQuery, true);

        if (!$claimed) {
            // This is NOT a failure: it just means the process could not lock the row.
            return null;
        }

        /*
         * This we get from the `LAST_INSERT_ID(id)` statement in the atomic query.
         * LAST_INSERT_ID will NOT only apply to INSERTions, but to UPDATEs too.
         */
        $claimedActionId = $this->database->insertId();

        if (empty($claimedActionId)) {
            /*
             * The previous query might succeed while NOT acquiring the lock, depending
             * on the db. If we have not acquired a lock, let's bail.
             */
            return null;
        }

        // Invalidate this Action cache and re-fetch the information.
        unset($this->actionCaches[$claimedActionId]);
        $actionObject = $this->getAction($claimedActionId);

        if ($actionObject instanceof Action) {
            $this->actionCaches[$claimedActionId] = $actionObject->toArray();
        }

        return $actionObject;
    }

    /**
     * Counts, with a query, and returns the number of Actions currently in the Queue.
     *
     * @param string|array<string>|null $status An optional status, or list of stati,
     *                                          to count Actions by. If not specified, then
     *                                          the returned value will be that of all Actions
     *                                          in any status.
     * @param string|array<string> $jobId       An optional Job Identifier, or a list of job identifiers,
     *                                          to narrow the count by; if not provided, then
     *                                          Actions from all Jobs will be counted.
     *
     * @return int The number of Actions currently in the Queue, or the number of Actions
     *             in the Queue with a specified status.
     */
    public function count($status = null, $jobId = null)
    {
        $tableName = self::getTableName();

        $jobClause = '';
        if (isset($jobId)) {
            $jobIdsInterval = $this->escapeInterval((array)$jobId);
            $jobClause = "AND jobId IN ({$jobIdsInterval})";
        }

        if (empty($status) || $status === Queue::STATUS_ANY) {
            $countQuery = "SELECT COUNT(id) FROM {$tableName} WHERE 1=1 {$jobClause}";
        } else {
            $stati = $this->escapeInterval((array)$status);
            $countQuery = "SELECT COUNT(id) FROM {$tableName} WHERE status IN ({$stati}) {$jobClause}";
        }

        $countResult = $this->database->query($countQuery);

        if ($countResult === false) {
            $error = $this->database->error();

            if (!empty($error)) {
                error_log(json_encode([
                    'root' => 'Error while trying to count Actions.',
                    'class' => get_class($this),
                    'query' => $countQuery,
                    'error' => $error,
                ]));
            }

            // For all intent and purposes, the Queue cannot be counted now.
            return 0;
        }

        $count = $this->database->fetchRow($countResult);

        return (array_sum((array)$count));
    }

    /**
     * Updates an Action status in the Queue table.
     *
     * Updating the Action status to Processing will mark the Action as claimed the very
     * moment the operation is performed.
     *
     * @param int|Action $action    Either an action id, or a reference to an Action object.
     * @param string     $newStatus The status to update the Action status to.
     * @param bool       $unsafely  If the status update is to the Processing one, then we require
     *                              the developer to do it with full understanding that it's NOT
     *                              the correct way to do it.
     *
     * @return false|int Either the updated action id, or `false` to indicate the status
     *                   update failed.
     *
     * @throws QueueException If the status to update the Action to is the Processing one and
     *                        the client code is not owning the unsafety of it.
     */
    public function updateActionStatus($action, $newStatus, $unsafely = false)
    {
        $actionId = absint($action instanceof Action ? $action->id : (int)$action);
        $tableName = self::getTableName();
        $status = $this->database->escape($newStatus);
        $now = date('Y-m-d H:m:s');

        if ($status !== self::STATUS_PROCESSING) {
            // Any status update that is not to the processing status, will clean the `claimed_at` column.
            $statusUpdateQuery = "UPDATE {$tableName} SET status='{$status}', claimed_at=NULL, updated_at='{$now}' WHERE id={$actionId}";
        } else {
            if (!$unsafely) {
                // This is a developer mistake: it should be immediately signaled as such.
                throw new QueueException('Marking actions as Processing should only be done using the getNextAvailable method!');
            }

            // If the status update is to the Processing status, then the Action should be marked as claimed.
            $statusUpdateQuery = "UPDATE {$tableName} SET status='{$status}', claimed_at='{$now}', updated_at='{$now}' WHERE id={$actionId} ";
        }

        $updated = $this->database->query($statusUpdateQuery, true);

        if (!$updated) {
            error_log(json_encode([
                'root' => 'Error while trying to update Action status.',
                'class' => get_class($this),
                'query' => $statusUpdateQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        // Force a refresh on next action fetch.
        unset($this->actionCaches[$actionId]);

        return $actionId;
    }

    /**
     * Return the Queue table creation SQL code.
     *
     * @return string The Queue table creation SQL code.
     */
    private function getCreateTableSql()
    {
        global $wpdb;
        $collate = $wpdb->collate;
        $queueTable = self::getTableName();
        $tableSql = "CREATE TABLE {$queueTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            action VARCHAR(1000) NOT NULL,
            jobId VARCHAR(1000) DEFAULT NULL,
            status CHAR(20) NOT NULL DEFAULT 'ready',
            priority BIGINT(20) NOT NULL DEFAULT 0,
            args LONGTEXT DEFAULT NULL,
            claimed_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id)
            ) COLLATE {$collate}";
        return $tableSql;
    }

    /**
     * Drops the custom table used by the Queue to store actions.
     *
     * Dropping the table means, implicitly, also losing all the Actions
     * stored there.
     *
     * @return bool Whether the table dropping was successful or not.
     */
    public function dropTable()
    {
        $tableName = self::getTableName();
        $query = "DROP TABLE {$tableName}";
        $this->database->query($query, true);

        return !$this->tableExists();
    }

    /**
     * Returns the last logged error provided by the underlying database adapter.
     *
     * @return string The last error as logged by the database adapter, or an empty
     *                string if there are no errors.
     */
    public function lastError()
    {
        if ($this->database === null) {
            return '';
        }
        return (string)$this->database->error();
    }

    /**
     * Fetches an Action row from the database.
     *
     * @param int $actionId The id of the Action to fetch.
     *
     * @return array<string,mixed>|null Either a map from the action columns to
     *                                  their respective values, or `null` to indicate
     *                                  the Action row could not be fetched.
     */
    private function fetchActionRow($actionId)
    {
        $actionId = absint($actionId);

        if (empty($actionId)) {
            return null;
        }

        $tableName = self::getTableName();
        $fetchQuery = "SELECT * FROM {$tableName} WHERE id={$actionId}";
        $fetchResult = $this->database->query($fetchQuery);

        if ($fetchResult === false) {
            // This is NOT an error, the query might be for a no-more existing Action.
            return null;
        }

        $row = $this->database->fetchAssoc($fetchResult);

        return is_array($row) ? $row : null;
    }

    /**
     * Fetches an action information from the database and returns a reference to
     * its object representation.
     *
     * @param int  $actionId The id of the Action to fetch.
     * @param bool $force Whether to force the refetch of the Action data from the database
     *                    or not.
     *
     * @return Action|null A reference to the Action object built from the input action id,
     *                     or `null` to indicate the Action data could not be fetched or does
     *                     not exist.
     *
     * @throws QueueException If there's any issue while building and validating the Action.
     */
    public function getAction($actionId, $force = false)
    {
        if ($force || empty($this->actionCaches[$actionId])) {
            $row = $this->fetchActionRow($actionId);

            if ($row !== null) {
                $this->actionCaches[$actionId] = $row;
            }
        }

        return isset($this->actionCaches[$actionId]) ?
            Action::fromDbRow($this->actionCaches[$actionId])
            : null;
    }

    /**
     * Returns a list of stati in which an Action could be.
     *
     * While nothing is preventing other code from assigning different
     * stati to the Actions, these are the ones the Queue is actually
     * equipped to handle.
     *
     * @return array<string> A list of the possible stati an Action could
     *                       be in.
     */
    public function getSupportedActionStati()
    {
        return [
            self::STATUS_PROCESSING,
            self::STATUS_READY,
            self::STATUS_COMPLETED,
        ];
    }

    /**
     * Returns an immutable Date Object representing the breakpoint date and time
     * that should be used to mark an Action that has been claimed before that point
     * in date and time as dangling if still processing.
     *
     * @return DateTimeImmutable A reference to an immutable date time object representing
     *                            the dangling breakpoint.
     */
    public function getDanglingBreakpointDate()
    {
        return $this->getBreakpointDate(HOUR_IN_SECONDS);
    }

    /**
     * Assigns a new status to any dangling Action.
     *
     * A dangling Action is one that was claimed for processing too long ago.
     * Where "too long ago" is defined by the Dangling Action breakpoint date.
     * Actions updated using this method will have their `claimed_at` column entry
     * cleared.
     *
     * @param string $newStatus The new status that should be assigned to the Actions.
     *                          The method will NOT check the status to make sure it's
     *                          one of the supported ones, this is by design to allow
     *                          the queue to be used in a more flexible way.
     *
     * @return int The number of updated Actions.
     */
    public function markDanglingAs($newStatus)
    {
        if ($this->checkTable() === static::TABLE_NOT_EXIST) {
            // The table does not exist so there is nothing to update.
            return 0;
        }

        $tableName = self::getTableName();
        $newStatus = $this->database->escape($newStatus);
        $danglingBreakpoint = $this->getDanglingBreakpointDate()->format('Y-m-d H:i:s');
        $markQuery = "UPDATE {$tableName} 
            SET status='{$newStatus}', claimed_at=NULL
            WHERE claimed_at IS NOT NULL
            AND claimed_at < '{$danglingBreakpoint}'";
        $markResult = $this->database->query($markQuery, true);

        if ($markResult === false) {
            error_log(json_encode([
                'root' => 'Error while trying to mark dangling Actions.',
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

        return (int)$marked;
    }

    /**
     * Hooked on the `shutdown` action, this method will make sure the Queue
     * will keep processing if there are ready Actions.
     *
     * @return bool Whether the AJAX action was correctly fired or not. Note that
     *              a `false` return value might indicate both that there was no
     *              need to fire the AJAX action or that the firing failed.
     */
    public function maybeFireAjaxAction()
    {
        if (!$this->count(self::STATUS_READY)) {
            return false;
        }

        return $this->fireAjaxAction();
    }

    /**
     * Escapes a set of values to be used in a IN clause.
     *
     * @param array<string> $values The set of values to escape.
     *
     * @return string The values escaped and concatenated in a string suitable
     *                to be used in a IN clause.
     */
    private function escapeInterval(array $values)
    {
        return implode(',', array_map(function ($value) {
            return "'" . $this->database->escape($value) . "'";
        }, (array)$values));
    }

    /**
     * Cancels all the Actions for a Job or a list of Jobs.
     *
     * @param string|array<string> $jobId A Job ID, or a list of Job IDs to cancel
     *                                    all Actions for.
     *
     * @return int
     * @since TBD
     *
     */
    public function cancelJob($jobId)
    {
        if ($this->checkTable() === static::TABLE_NOT_EXIST) {
            // The table does not exist so there is nothing to cancel.
            return 0;
        }

        $tableName = self::getTableName();
        $newStatus = self::STATUS_CANCELED;
        $jobIds = (array)$jobId;
        $jobIdsInterval = $this->escapeInterval($jobIds);
        $now = date('Y-m-d H:m:s');
        $cancelQuery = "UPDATE {$tableName} 
            SET status='{$newStatus}', claimed_at=NULL, updated_at='{$now}'
            WHERE jobId in (${jobIdsInterval})";
        $cancelResult = $this->database->query($cancelQuery, true);

        if ($cancelResult === false) {
            error_log(json_encode([
                'root' => 'Error while trying to cancel Actions.',
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

    /**
     * Invalidates cached Action values by Job Id.
     *
     * @param array<string> $jobIds A list of Job IDs to invalidate the Actions by.
     *
     * @return void The method does not return any value and will invalidate the Actions
     *              caches by Job ID as a side effect.
     */
    private function invalidateActionCachesByJobId(array $jobIds)
    {
        array_walk($this->actionCaches, static function (&$cachedAction) use ($jobIds) {
            if (!empty($cachedAction['jobId']) && in_array($cachedAction['jobId'], $jobIds, true)) {
                $cachedAction = null;
            }
        });
    }

    /**
     * Allows updating an Action field directly.
     *
     * This method has the potential of disrupting the Queue functions: with great power comes great
     * responsibility.
     *
     * @param Action|int          $action            The Action ID or a reference to the Action instance.
     * @param array<string,mixed> $updates           A map from the updates keys to the updates value.
     * @param bool                $unsafely          We require the developer to do it with full understanding that it's
     *                                               NOT the correct way to do it.
     *
     * @return false|int Either the ID of the updated action, or `false` to indicate the update failed.
     *
     * @throws QueueException If the `$unsafely` parameter is not set to `true` or the field
     *                        to be updated is the Action ID one.
     *
     * @internal This method has the potential of disrupting the Queue system and should not
     *           be used without full knowledge of what is being done.
     */
    public function updateActionFields($action, array $updates, $unsafely = false)
    {
        if (!$unsafely) {
            // This is a developer mistake: it should be immediately signaled as such.
            throw new QueueException(
                'Updating Action fields has the potential of disrupting the Queue functions.'
            );
        }

        if (isset($updates['id'])) {
            // This is a developer mistake: it should be immediately signaled as such.
            throw new QueueException(
                'Updating an Action ID is never allowed.'
            );
        }

        $actionId = absint($action instanceof Action ? $action->id : (int)$action);
        $tableName = self::getTableName();

        $assignmentsList = $this->buildAssignmentsList($updates);
        $statusUpdateQuery = "UPDATE {$tableName} SET {$assignmentsList} WHERE id={$actionId}";

        $updated = $this->database->query($statusUpdateQuery, true);

        if (!$updated) {
            error_log(json_encode([
                'root' => 'Error while trying to update Action field.',
                'class' => get_class($this),
                'query' => $statusUpdateQuery,
                'error' => $this->database->error(),
            ]));

            return false;
        }

        // Force a refresh on next action fetch.
        unset($this->actionCaches[$actionId]);

        return $actionId;
    }

    /**
     * Compiles a map of values to a SET assignment list suitable to use in INSERT and UPDATE
     * statements.
     *
     * @param array<string,mixed> $assignments A map from the assignments keys to the values.
     *
     * @return string The compiled assignments list.
     */
    private function buildAssignmentsList(array $assignments)
    {
        $assignmentList = [];

        array_walk($assignments, function ($value, $key) use (&$assignmentList) {
            if ($value === '') {
                // Empty string values should be skipped to let the database default value kick in.
                return;
            }

            $escapedKey = $this->database->escape($key);

            if ($key === 'priority') {
                // Keep the numeric value.
                $escapedValue = (int)$value;
                $assignmentList[] = "{$escapedKey}={$escapedValue}";
            } elseif ($key === 'args') {
                $escapedValue = serialize($value);
                $assignmentList[] = "{$escapedKey}='{$escapedValue}'";
            } else {
                $escapedValue = $this->database->escape($value);
                $assignmentList[] = "{$escapedKey}='{$escapedValue}'";
            }
        });

        return implode(', ', $assignmentList);
    }

    /**
     * Returns an immutable Date Object representing the breakpoint date and time
     * that should be used to cleanup Actions.
     *
     * @return DateTimeImmutable A reference to an immutable date time object representing
     *                            the dangling breakpoint.
     */
    public function getCleanupBreakpointDate()
    {
        return $this->getBreakpointDate(WEEK_IN_SECONDS);
    }

    /**
     * Builds and returns a breakpoint date.
     *
     * @param int $interval  The amount, in seconds, to apply to the current date
     *                       and time to build the breakpoint date.
     *
     * @return DateTimeImmutable A reference to the breakpoint date immutable instance.
     */
    private function getBreakpointDate($interval)
    {
        try {
            $breakpointDate = new DateTimeImmutable(date('Y-m-d H:i:s'));
            $breakpointDate = $breakpointDate->setTimestamp($breakpointDate->getTimestamp() - $interval);
        } catch (Exception $e) {
            /*
             * On failure, return a date very far in the past, before this is written, to make sure no
             * Action will be modified.
             */
            $breakpointDate = new DateTimeImmutable('2020-01-01 00:00:00');
        }

        return $breakpointDate;
    }

    public function cleanup()
    {
        if ($this->checkTable() === static::TABLE_NOT_EXIST) {
            // The table does not exist so there is nothing to update.
            return 0;
        }

        $tableName = self::getTableName();
        $cleanupBreakpoint = $this->getCleanupBreakpointDate()->format('Y-m-d H:i:s');
        $cleanableStati = $this->escapeInterval([
            self::STATUS_READY,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELED
        ]);
        $clenupQuery = "DELETE FROM {$tableName} 
            WHERE updated_at < '{$cleanupBreakpoint}'
            AND status in ({$cleanableStati})";
        $cleanupResult = $this->database->query($clenupQuery, true);

        if ($cleanupResult === false) {
            error_log(json_encode([
                'root' => 'Error while trying to cleanup Actions.',
                'class' => get_class($this),
                'query' => $clenupQuery,
                'error' => $this->database->error(),
            ]));

            return 0;
        }
        if (isset($this->database->link->affected_rows)) {
            $removed = $this->database->link->affected_rows;
        } else {
            $removed = 0;
        }

        return $removed;
    }
}
