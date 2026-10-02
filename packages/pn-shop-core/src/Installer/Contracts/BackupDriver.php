<?php

namespace PnShop\Installer\Contracts;

/**
 * Makes a backup before an update. Bind another implementation (e.g. in a plugin) to send
 * backups off-site.
 */
interface BackupDriver
{
    /**
     * @param  bool  $withFiles  also copy storage/app (uploads)
     * @return string where the backup is (a path or URL), for the update log
     */
    public function backup(string $label, bool $withFiles = false): string;
}
