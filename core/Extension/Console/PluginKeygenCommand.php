<?php

namespace PnShop\Extension\Console;

use Illuminate\Console\Command;

/**
 * For plugin authors: create an Ed25519 key pair for signing plugins.
 */
class PluginKeygenCommand extends Command
{
    protected $signature = 'pnshop:plugin:keygen {file : Where to write the secret key (keep it out of version control)}';

    protected $description = 'Create a key pair for signing plugins';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (file_exists($file)) {
            $this->error("{$file} already exists.");

            return self::FAILURE;
        }

        $pair = sodium_crypto_sign_keypair();
        file_put_contents($file, base64_encode(sodium_crypto_sign_secretkey($pair)).PHP_EOL);
        chmod($file, 0600);

        $this->info("Secret key written to {$file}.");
        $this->line('Public key (add it to pnshop.extensions.trusted_keys of the shops that trust you):');
        $this->line(base64_encode(sodium_crypto_sign_publickey($pair)));
        sodium_memzero($pair);

        return self::SUCCESS;
    }
}
