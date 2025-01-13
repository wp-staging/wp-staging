<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
class TableViewsRenamer
{
    protected $database;
    protected $tableService;
    protected $client;

    public function __construct(Database $database, TableService $tableService)
    {
        $this->database = $database;
        $this->tableService = $tableService;
        $this->client = $database->getClient();
    }

    public function renameViewReferences($query)
    {
        try {
            $this->replaceViewOptions($query);
            $success = $this->client->query($query);
            if (!$success) {
                throw new \RuntimeException(sprintf(
                    'Could not rename view references.. \n Query: %s \n Error code: %s \n Error Message: %s \n',
                    $query,
                    $this->client->errno(),
                    $this->client->error()
                ));
            }
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log($e->getMessage());
        }
    }

    private function replaceViewOptions(&$input)
    {
        $input = preg_replace('/CREATE(.+?)VIEW/i', 'CREATE OR REPLACE VIEW', $input);
    }
}
