<?php

namespace PnShop\Settings;

use Illuminate\Validation\Rule;

/**
 * One typed setting inside a schema, e.g. store.email.
 */
final readonly class SettingDefinition
{
    /**
     * @param  array<string, string>  $options  value => label, for SettingType::Select
     * @param  list<mixed>  $rules  extra validation rules
     */
    public function __construct(
        public string $key,
        public SettingType $type,
        public string $label,
        public mixed $default = null,
        public bool $required = false,
        public ?string $help = null,
        public array $options = [],
        public array $rules = [],
    ) {}

    /**
     * @return list<mixed>
     */
    public function validationRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable', ...$this->type->rules(), ...$this->rules];

        if ($this->type === SettingType::Select) {
            $rules[] = Rule::in(array_keys($this->options));
        }

        return $rules;
    }
}
