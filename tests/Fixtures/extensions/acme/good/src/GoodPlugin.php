<?php

namespace Acme\Good;

use Illuminate\Support\Facades\DB;
use PnShop\Extension\Plugin;

class GoodPlugin extends Plugin
{
    public function install(): void
    {
        DB::table('acme_good_notes')->insert(['note' => 'installed']);
    }

    public function upgrade(string $from, string $to): void
    {
        DB::table('acme_good_notes')->insert(['note' => "upgraded {$from} to {$to}"]);
    }

    public function uninstall(bool $keepData): void
    {
        DB::table('acme_good_notes')->insert(['note' => $keepData ? 'uninstalled keeping data' : 'uninstalled']);
    }

    public function greeting(): string
    {
        return (string) $this->setting('greeting');
    }
}
