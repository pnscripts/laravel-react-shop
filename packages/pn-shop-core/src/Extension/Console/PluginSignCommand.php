<?php

namespace PnShop\Extension\Console;

use Illuminate\Console\Command;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Extension\Manifest;
use PnShop\Extension\PackageIntegrity;

/**
 * For plugin authors: sign a plugin folder (writes pnshop.sig).
 */
class PluginSignCommand extends Command
{
    protected $signature = 'pnshop:plugin:sign {path : The plugin folder} {--key-id= : The id shops know your public key by} {--secret-file= : File with the base64 secret key}';

    protected $description = 'Sign a plugin folder';

    public function handle(): int
    {
        $path = rtrim((string) $this->argument('path'), '/');
        $keyId = (string) $this->option('key-id');
        $secretFile = (string) $this->option('secret-file');

        try {
            Manifest::fromDirectory($path);

            if ($keyId === '' || ! is_file($secretFile)) {
                throw new ExtensionException('Pass --key-id and --secret-file.');
            }

            PackageIntegrity::sign($path, $keyId, trim((string) file_get_contents($secretFile)));
        } catch (ExtensionException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Signed {$path} with key {$keyId}.");

        return self::SUCCESS;
    }
}
