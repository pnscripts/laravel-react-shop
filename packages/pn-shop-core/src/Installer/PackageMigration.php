<?php

namespace PnShop\Installer;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use PnShop\Customer\Models\User;
use PnShop\Foundation\PnShop;
use PnShop\Foundation\PnShopServiceProvider;
use RuntimeException;

/**
 * Moves a shop onto the published pnscripts/pn-shop-core package (pnshop:migrate-to-package):
 *
 * - composer.json requires the package instead of the copy in packages/pn-shop-core (or the
 *   core/ folder of 1.0);
 * - files that PN Shop 1.0 shipped and 1.1 moved into the package are moved out of the way,
 *   into storage/app/pnshop-migration/<date>/, never deleted; files 1.0 did not ship stay;
 * - the shop's own wiring is checked (service provider, customer model, routes).
 *
 * The database is not touched.
 */
class PackageMigration
{
    /** The in-tree copy of the core in the development repository and in 1.1 archives. */
    public const LOCAL_PACKAGE = 'packages/pn-shop-core';

    /**
     * @param  string|null  $shippedFiles  JSON list of the files 1.0 shipped (path => sha256)
     */
    public function __construct(private string $root, private ?string $shippedFiles = null) {}

    /**
     * What the migration would change.
     *
     * @return array{composer: list<string>, leftovers: array<string, bool>, local_package: bool, problems: list<string>}
     */
    public function plan(): array
    {
        $composer = $this->composer();

        if (($composer['extra']['pnshop']['monorepo'] ?? false) === true) {
            throw new RuntimeException('This is the PN Shop development repository: its core stays in '.self::LOCAL_PACKAGE.'.');
        }

        return [
            'composer' => $this->composerChanges($composer)[1],
            'leftovers' => $this->leftovers(),
            'local_package' => $this->localPackageIsUnused(),
            'problems' => $this->problems(),
        ];
    }

    /**
     * Carry out the plan. Returns the folder the moved files went to (null when none moved).
     */
    public function run(string $stamp): ?string
    {
        $plan = $this->plan();
        $target = $this->path('storage/app/pnshop-migration/'.$stamp);
        $moved = false;

        $composer = $this->composer();
        [$updated, $changes] = $this->composerChanges($composer);

        if ($changes !== []) {
            $this->moveAside('composer.json', $target, copy: true);
            File::put($this->path('composer.json'), json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
            $moved = true;
        }

        foreach (array_keys($plan['leftovers']) as $file) {
            $this->moveAside($file, $target);
            $moved = true;
        }

        if ($plan['local_package']) {
            $this->moveAside(self::LOCAL_PACKAGE, $target);
            $moved = true;
        }

        $this->removeEmptyFolders(array_keys($plan['leftovers']));

        return $moved ? $target : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function composer(): array
    {
        $composer = json_decode((string) @file_get_contents($this->path('composer.json')), true);

        if (! is_array($composer)) {
            throw new RuntimeException('composer.json is missing or not valid JSON.');
        }

        return $composer;
    }

    /**
     * @param  array<string, mixed>  $composer
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function composerChanges(array $composer): array
    {
        $changes = [];
        [$major, $minor] = array_map('intval', explode('.', PnShop::VERSION)) + [0, 0];
        $constraint = "^{$major}.{$minor}";

        if (($composer['require'][PnShop::PACKAGE] ?? null) !== $constraint) {
            $composer['require'][PnShop::PACKAGE] = $constraint;
            $changes[] = 'require '.PnShop::PACKAGE." {$constraint}";
        }

        $repositories = [];
        foreach ((array) ($composer['repositories'] ?? []) as $key => $repository) {
            if (is_array($repository) && ($repository['type'] ?? null) === 'path' && str_contains((string) ($repository['url'] ?? ''), 'pn-shop-core')) {
                $changes[] = "remove the path repository {$repository['url']}";

                continue;
            }

            $repositories[$key] = $repository;
        }

        if (array_key_exists('repositories', $composer)) {
            if ($repositories === []) {
                unset($composer['repositories']);
            } else {
                $composer['repositories'] = array_is_list($composer['repositories']) ? array_values($repositories) : $repositories;
            }
        }

        // 1.0 autoloaded the core from core/ and the seeders from database/seeders.
        foreach (['PnShop\\' => 'core/', 'Database\\Seeders\\' => 'database/seeders/'] as $namespace => $folder) {
            if (rtrim((string) ($composer['autoload']['psr-4'][$namespace] ?? ''), '/').'/' === $folder) {
                unset($composer['autoload']['psr-4'][$namespace]);
                $changes[] = "stop autoloading {$namespace} from {$folder}";
            }
        }

        return [$composer, $changes];
    }

    /**
     * Files of PN Shop 1.0 that 1.1 moved into the package, with whether the merchant changed them.
     *
     * @return array<string, bool> path => changed since 1.0
     */
    public function leftovers(): array
    {
        /** @var array<string, string> $shipped */
        $shipped = json_decode((string) file_get_contents($this->shippedFiles ?? PnShop::path('resources/upgrade/1.0-files.json')), true);
        $found = [];

        foreach ($shipped as $file => $hash) {
            $path = $this->path($file);

            if (is_file($path) && ! is_link($path)) {
                $found[$file] = hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($path))) !== $hash;
            }
        }

        return $found;
    }

    /**
     * packages/pn-shop-core is still there, but Composer loads the core from elsewhere.
     */
    private function localPackageIsUnused(): bool
    {
        $local = realpath($this->path(self::LOCAL_PACKAGE));

        if ($local === false || ! InstalledVersions::isInstalled(PnShop::PACKAGE)) {
            return false;
        }

        $installed = realpath((string) InstalledVersions::getInstallPath(PnShop::PACKAGE));

        return $installed !== false && $installed !== $local && ! str_starts_with($installed, $local.DIRECTORY_SEPARATOR);
    }

    /**
     * Wiring in the shop's own files that 1.1 changed.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];
        $providers = (string) @file_get_contents($this->path('bootstrap/providers.php'));

        if (str_contains($providers, PnShopServiceProvider::class) || str_contains($providers, 'PnShopServiceProvider::class')) {
            $problems[] = 'bootstrap/providers.php still lists PnShopServiceProvider. Remove it: the package registers itself.';
        }

        $model = config('auth.providers.users.model');
        if (! is_string($model) || ! is_a($model, User::class, true)) {
            $problems[] = 'The customer model (auth.providers.users.model) must be '.User::class.' or extend it, as app/Models/User.php does in 1.1.';
        }

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $controller = $route->getAction('controller');
            $class = is_string($controller) ? explode('@', $controller)[0] : null;

            if ($class !== null && str_starts_with($class, 'App\\Http\\') && ! class_exists($class)) {
                $problems[] = "The route {$route->uri()} uses {$class}, which no longer exists. routes/web.php should only hold your own routes (the storefront's come from the package).";

                break;
            }
        }

        $config = (string) @file_get_contents($this->path('config/pnshop.php'));
        if (preg_match("/^\\s*'modules'\\s*=>/m", $config) === 1) {
            $problems[] = "config/pnshop.php still has a 'modules' list. It is ignored now; add your own modules to 'extra_modules'.";
        }

        return $problems;
    }

    private function moveAside(string $relative, string $target, bool $copy = false): void
    {
        $from = $this->path($relative);
        $to = $target.'/'.$relative;

        File::ensureDirectoryExists(dirname($to));

        $done = match (true) {
            is_dir($from) && ! is_link($from) => $copy ? File::copyDirectory($from, $to) : File::moveDirectory($from, $to),
            default => $copy ? File::copy($from, $to) : File::move($from, $to),
        };

        if (! $done) {
            throw new RuntimeException("Could not move {$relative} to {$to}.");
        }
    }

    /**
     * Folders the moved files leave empty (core/, app/Http, resources/js, …).
     *
     * @param  list<string>  $files
     */
    private function removeEmptyFolders(array $files): void
    {
        $folders = [];
        foreach ($files as $file) {
            for ($folder = dirname($file); $folder !== '.' && $folder !== ''; $folder = dirname($folder)) {
                $folders[$folder] = substr_count($folder, '/');
            }
        }

        arsort($folders);

        foreach (array_keys($folders) as $folder) {
            $path = $this->path($folder);

            if (is_dir($path) && (scandir($path) ?: []) === ['.', '..']) {
                @rmdir($path);
            }
        }
    }

    private function path(string $relative): string
    {
        return rtrim($this->root, '/').'/'.$relative;
    }
}
