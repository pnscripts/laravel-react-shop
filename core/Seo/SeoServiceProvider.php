<?php

namespace PnShop\Seo;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Localization\Localization;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;

/**
 * Meta tags, structured data, sitemaps and robots.txt.
 */
class SeoServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Seo::class);

        // Each request starts with empty SEO data, also in long-running workers and tests.
        Event::listen(RequestHandled::class, fn () => $this->app->forgetInstance(Seo::class));
    }

    protected function bootModule(): void
    {
        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'seo',
            'Search engines',
            new SettingDefinition('allow_indexing', SettingType::Boolean, 'Let search engines index the shop', default: true, help: 'Turn off for a staging copy: every page is marked noindex and robots.txt disallows everything.'),
            new SettingDefinition('robots_extra', SettingType::Text, 'Extra robots.txt rules', help: 'Added to the generated robots.txt, e.g. "Disallow: /campaign".'),
        ));

        // The language switcher follows the page's own addresses (translated slugs).
        $this->app->make(Localization::class)->resolveAlternatesUsing(fn (string $locale) => app(Seo::class)->languageLinkFor($locale));

        // On full page loads the root template prints the page's meta tags.
        View::composer('app', function ($view): void {
            $view->with('seo', app(Seo::class)->resolve(request()));
        });
    }
}
