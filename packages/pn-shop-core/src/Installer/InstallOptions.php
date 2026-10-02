<?php

namespace PnShop\Installer;

use Illuminate\Validation\Rules\Password;

/**
 * What the installer sets up: the store basics and the first administrator.
 */
final readonly class InstallOptions
{
    public function __construct(
        public string $storeName,
        public string $adminName,
        public string $adminEmail,
        #[\SensitiveParameter]
        public string $adminPassword,
        public ?string $storeEmail = null,
        public string $locale = 'en',
        public string $currency = 'EUR',
        public ?string $country = null,
        public string $timezone = 'UTC',
        public bool $pricesIncludeTax = true,
        public bool $demo = false,
    ) {}

    /**
     * Validation rules shared by the command and the web installer.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'locale' => ['required', 'string', 'in:en,bg'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'timezone' => ['required', 'timezone:all'],
            'prices_include_tax' => ['boolean'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', Password::defaults()],
            'demo' => ['boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data  validated with rules()
     */
    public static function fromArray(array $data): self
    {
        return new self(
            storeName: (string) $data['store_name'],
            adminName: (string) $data['admin_name'],
            adminEmail: mb_strtolower((string) $data['admin_email']),
            adminPassword: (string) $data['admin_password'],
            storeEmail: isset($data['store_email']) && $data['store_email'] !== '' ? (string) $data['store_email'] : null,
            locale: (string) $data['locale'],
            currency: strtoupper((string) $data['currency']),
            country: isset($data['country']) && $data['country'] !== '' ? strtoupper((string) $data['country']) : null,
            timezone: (string) $data['timezone'],
            pricesIncludeTax: (bool) ($data['prices_include_tax'] ?? true),
            demo: (bool) ($data['demo'] ?? false),
        );
    }
}
