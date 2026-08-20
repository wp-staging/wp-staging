<?php

namespace WPStaging\Backup\Service;













class UpdateProtectionHealth
{
 
    const OPTION_NAME = 'wpstg_update_protection_health';

 
    const REASON_DISK_SPACE = 'disk_space';
    const REASON_PERMISSIONS = 'permissions';

 
    const REASON_STALLED = 'stalled';

    const REASON_UNKNOWN = 'unknown';

 
    const PERSISTENT_REASONS = [self::REASON_DISK_SPACE, self::REASON_PERMISSIONS];





    const FAILURES_BEFORE_PAUSE = 2;





    const PAUSE_DURATION_IN_SECONDS = DAY_IN_SECONDS;




    public function isPaused(): bool
    {
        $state = $this->read();

        return !empty($state['paused_until']) && (int)$state['paused_until'] > time();
    }




    public function getReason(): string
    {
        $state = $this->read();

        return isset($state['reason']) ? (string)$state['reason'] : '';
    }




    public function getMessage(): string
    {
        $state = $this->read();

        return isset($state['message']) ? (string)$state['message'] : '';
    }









    public function recordFailure(string $message, string $reason = ''): string
    {
        $reason   = $reason !== '' ? $reason : $this->classify($message);
        $state    = $this->read();
        $failures = (int)(isset($state['failures']) ? $state['failures'] : 0) + 1;

        $shouldPause = in_array($reason, self::PERSISTENT_REASONS, true) || $failures >= self::FAILURES_BEFORE_PAUSE;

        $this->write([
            'failures'     => $failures,
            'reason'       => $reason,
            'message'      => $message,
            'failed_at'    => time(),
            'paused_until' => $shouldPause ? time() + self::PAUSE_DURATION_IN_SECONDS : 0,
        ]);

        return $reason;
    }








    public function recordSuccess()
    {
        if ($this->read() === []) {
            return;
        }

        delete_option(self::OPTION_NAME);
    }







    public function resume()
    {
        delete_option(self::OPTION_NAME);
    }





    public function classify(string $message): string
    {
        if ($message === '') {
            return self::REASON_UNKNOWN;
        }

        $haystack = strtolower($message);

        if (strpos($haystack, 'disk space') !== false || strpos($haystack, 'no space left') !== false) {
            return self::REASON_DISK_SPACE;
        }

        $permissionMarkers = ['not writable', 'not writeable', 'write permission', 'could not write', 'cannot write', 'permission denied'];
        foreach ($permissionMarkers as $marker) {
            if (strpos($haystack, $marker) !== false) {
                return self::REASON_PERMISSIONS;
            }
        }

        return self::REASON_UNKNOWN;
    }




    private function read(): array
    {
        $state = get_option(self::OPTION_NAME, []);

        return is_array($state) ? $state : [];
    }





    private function write(array $state)
    {
        update_option(self::OPTION_NAME, $state, false);
    }
}
