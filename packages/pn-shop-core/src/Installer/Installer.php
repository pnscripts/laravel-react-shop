<?php

namespace PnShop\Installer;

use Closure;
use Faker\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PnShop\Acl\Models\AdminUser;
use PnShop\Acl\PermissionSynchronizer;
use PnShop\Database\Seeders\CatalogDemoSeeder;
use PnShop\Database\Seeders\PaymentMethodSeeder;
use PnShop\Database\Seeders\ProductAttributeSeeder;
use PnShop\Database\Seeders\ProductCategorySeeder;
use PnShop\Database\Seeders\ProductSeeder;
use PnShop\Database\Seeders\ShippingSeeder;
use PnShop\Database\Seeders\TaxSeeder;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Country;
use PnShop\Localization\Models\Currency;
use PnShop\Localization\Models\Language;
use PnShop\Settings\Settings;
use PnShop\Theme\ThemeManager;
use PnShop\Theme\ThemeManifest;
use RuntimeException;

/**
 * Sets up a fresh shop (used by `pnshop:install` and the web installer): database tables,
 * reference data, store basics, the first administrator, the default theme, optional demo
 * products, and finally the install lock. There are no default accounts.
 */
class Installer
{
    public function __construct(
        private Installation $installation,
        private Settings $settings,
        private Localization $localization,
        private PermissionSynchronizer $permissions,
    ) {}

    /**
     * @param  (Closure(string): void)|null  $progress  receives a line per step
     *
     * @throws RuntimeException when the shop is already installed
     */
    public function install(InstallOptions $options, ?Closure $progress = null): AdminUser
    {
        $step = $progress ?? fn (string $message) => null;

        if ($this->installation->isInstalled()) {
            throw new RuntimeException('PN Shop is already installed. Use `php artisan pnshop:update` to update it.');
        }

        @set_time_limit(300);

        $step('Creating the database tables');
        Artisan::call('migrate', ['--force' => true]);
        $this->permissions->sync();

        $step('Adding payment, shipping and tax defaults');
        foreach ([PaymentMethodSeeder::class, ShippingSeeder::class, TaxSeeder::class] as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        $step('Saving the store settings');
        $this->storeBasics($options);

        $step('Creating the administrator');
        $admin = $this->administrator($options);

        $step('Activating the default theme');
        $this->settings->set('appearance', ['theme' => ThemeManifest::DEFAULT]);

        if ($options->demo && ! class_exists(Factory::class)) {
            $step('Skipping demo products: they need the development dependencies (composer install without --no-dev)');
        } elseif ($options->demo) {
            $step('Adding demo products');
            foreach ([ProductCategorySeeder::class, ProductAttributeSeeder::class, ProductSeeder::class, CatalogDemoSeeder::class] as $seeder) {
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
            }
        }

        $step('Publishing the storefront');
        $themes = app(ThemeManager::class);
        $themes->publish($themes->builtin());

        $step('Linking public storage');
        if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        $this->installation->record('install', details: ['locale' => $options->locale, 'currency' => $options->currency, 'demo' => $options->demo]);
        activity('system')->causedBy($admin)->log('PN Shop installed');

        $step('Done');

        return $admin;
    }

    private function storeBasics(InstallOptions $options): void
    {
        $this->settings->set('store', array_filter(['name' => $options->storeName, 'email' => $options->storeEmail], fn (mixed $value) => $value !== null));
        $this->settings->set('localization', ['timezone' => $options->timezone]);
        $this->settings->set('tax', array_filter([
            'prices_include_tax' => $options->pricesIncludeTax,
            'store_country' => $options->country,
        ], fn (mixed $value) => $value !== null));

        DB::transaction(function () use ($options) {
            Language::query()->update(['is_default' => false]);
            Language::query()->where('code', $options->locale)->update(['is_default' => true, 'is_active' => true]);

            Currency::query()->firstOrCreate(['code' => $options->currency], ['name' => $options->currency, 'exchange_rate' => 1]);
            Currency::query()->update(['is_default' => false]);
            Currency::query()->where('code', $options->currency)->update(['is_default' => true, 'is_active' => true, 'exchange_rate' => 1]);

            if ($options->country !== null) {
                Country::query()->firstOrCreate(['code' => $options->country], ['is_active' => true])->update(['is_active' => true]);
            }
        });

        $this->localization->flush();
    }

    private function administrator(InstallOptions $options): AdminUser
    {
        $admin = AdminUser::query()->firstOrNew(['email' => $options->adminEmail]);
        $admin->name = $options->adminName;
        $admin->password = $options->adminPassword;
        $admin->is_active = true;
        $admin->save();
        $admin->assignRole(AdminUser::ADMINISTRATOR_ROLE);

        return $admin;
    }
}
