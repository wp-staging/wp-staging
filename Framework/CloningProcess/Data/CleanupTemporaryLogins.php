<?php

namespace WPStaging\Framework\CloningProcess\Data;

class CleanupTemporaryLogins extends DBCloningService
{
    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        $usersTable    = $this->dto->getPrefix() . 'users';
        $usermetaTable = $this->dto->getPrefix() . 'usermeta';

        if (!$this->tableExists($usersTable) || !$this->tableExists($usermetaTable)) {
            $this->log("Table {$usersTable} or {$usermetaTable} does not exist. Skipping temporary users removal.");
            return true;
        }

        $loginLinkPrefix = 'wpstgtmpuser'; // see WPStaging/Pro/Auth/TemporaryLogins::LOGIN_LINK_PREFIX
        $prepare = $this->dto->getStagingDb()->prepare(
            "DELETE t1, t2 FROM {$usersTable} as t1 LEFT JOIN {$usermetaTable} as t2 ON t1.ID = t2.user_id WHERE t1.user_login LIKE %s",
            $loginLinkPrefix . '%'
        );

        $result = $this->dto->getStagingDb()->query($prepare);

        if ($result === false) {
            $this->log("SQL - " . $prepare);
            $this->log("Failed to delete temporary users {$this->dto->getStagingDb()->last_error}.");
            return false;
        }

        if ($result > 0) {
            $this->log("Successfully deleted temporary users and their metadata from staging site.");
        } else {
            $this->log("No temporary users found in staging site.");
        }

        return true;
    }
}
