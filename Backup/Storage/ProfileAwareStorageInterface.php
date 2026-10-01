<?php

namespace WPStaging\Backup\Storage;





interface ProfileAwareStorageInterface
{




    public function useProfile(string $profileId): bool;
}
