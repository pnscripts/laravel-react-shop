<?php

namespace PnShop\Customer;

use Illuminate\Validation\Rule;
use Locale;

/**
 * A postal address as entered at checkout or kept in an address book. Orders keep a copy.
 */
final readonly class PostalAddress
{
    public const FIELDS = ['first_name', 'last_name', 'company', 'line1', 'line2', 'city', 'postcode', 'region', 'country_code', 'phone'];

    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?string $company,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $postcode,
        public ?string $region,
        public string $country_code,
        public ?string $phone,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $value = fn (string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;

        return new self(
            (string) $value('first_name'),
            (string) $value('last_name'),
            $value('company'),
            (string) $value('line1'),
            $value('line2'),
            (string) $value('city'),
            $value('postcode'),
            $value('region'),
            strtoupper((string) $value('country_code')),
            $value('phone'),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Display lines, with the country name in the given (or current) language.
     *
     * @return list<string>
     */
    public function lines(?string $locale = null): array
    {
        return array_values(array_filter([
            $this->fullName(),
            $this->company,
            $this->line1,
            $this->line2,
            trim(implode(' ', array_filter([$this->postcode, $this->city]))),
            $this->region,
            Locale::getDisplayRegion('-'.$this->country_code, $locale ?? app()->getLocale()) ?: $this->country_code,
        ], fn (?string $line) => $line !== null && $line !== ''));
    }

    /**
     * Validation rules for an address submitted under the given key prefix ("shipping." etc.).
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(string $prefix = ''): array
    {
        return [
            "{$prefix}first_name" => ['required', 'string', 'max:100'],
            "{$prefix}last_name" => ['required', 'string', 'max:100'],
            "{$prefix}company" => ['nullable', 'string', 'max:150'],
            "{$prefix}line1" => ['required', 'string', 'max:255'],
            "{$prefix}line2" => ['nullable', 'string', 'max:255'],
            "{$prefix}city" => ['required', 'string', 'max:100'],
            "{$prefix}postcode" => ['nullable', 'string', 'max:32'],
            "{$prefix}region" => ['nullable', 'string', 'max:100'],
            "{$prefix}country_code" => ['required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            "{$prefix}phone" => ['nullable', 'string', 'max:50'],
        ];
    }
}
