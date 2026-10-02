<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\Manifest;
use PnShop\Extension\Models\Extension;
use PnShop\Foundation\PnShop;
use PnShop\Theme\ThemeManager;
use PnShop\Theme\ThemeManifest;

/**
 * Read-only view of the installation for monitoring. Installing or enabling extensions runs
 * third-party code and stays in the admin panel and the CLI.
 */
class SystemController extends AdminController
{
    /**
     * List extensions
     *
     * Plugins found in the extensions folder with their state.
     *
     * @return array<string, mixed>
     */
    public function extensions(ExtensionManager $extensions): array
    {
        Gate::authorize('system.extensions.manage');

        $installed = Extension::query()->get()->keyBy('id');

        return ['data' => $extensions->discover()->map(function (Manifest $manifest) use ($installed): array {
            $record = $installed->get($manifest->id);

            return [
                'id' => $manifest->id,
                'name' => $manifest->name,
                'description' => $manifest->description,
                'version' => $manifest->version,
                'installed_version' => $record?->version,
                'status' => $record->status->value ?? 'available',
                'error' => $record?->error,
                'author' => $manifest->author,
            ];
        })->values()->all(), 'meta' => ['pnshop_version' => PnShop::VERSION]];
    }

    /**
     * List themes
     *
     * @return array<string, mixed>
     */
    public function themes(ThemeManager $themes): array
    {
        Gate::authorize('appearance.themes.manage');

        $active = $themes->active()->id;

        return ['data' => $themes->discover()->map(fn (ThemeManifest $theme) => [
            'id' => $theme->id,
            'name' => $theme->name,
            'description' => $theme->description,
            'version' => $theme->version,
            'parent' => $theme->parent,
            'builtin' => $theme->builtin,
            'active' => $theme->id === $active,
            'problems' => $themes->problems($theme),
        ])->values()->all()];
    }
}
