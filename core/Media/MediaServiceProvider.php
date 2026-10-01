<?php

namespace PnShop\Media;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Media\Models\Media;
use PnShop\Media\Policies\MediaPolicy;

class MediaServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MediaLibrary::class);

        Relation::morphMap(['media' => Media::class]);
    }

    protected function permissions(): array
    {
        return [
            new Permission('content.media.manage', 'Manage the media library', 'Content'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
