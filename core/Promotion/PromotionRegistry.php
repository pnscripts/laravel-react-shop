<?php

namespace PnShop\Promotion;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use PnShop\Promotion\Contracts\ActionType;
use PnShop\Promotion\Contracts\ConditionType;

/**
 * Condition and action types promotions are built from. Plugins register theirs in
 * bootPlugin(): app(PromotionRegistry::class)->condition(MyCondition::class).
 */
final class PromotionRegistry
{
    /** @var array<string, ConditionType> */
    private array $conditions = [];

    /** @var array<string, ActionType> */
    private array $actions = [];

    public function __construct(private Container $container) {}

    /**
     * @param  ConditionType|class-string<ConditionType>  $type
     */
    public function condition(ConditionType|string $type): void
    {
        $type = is_string($type) ? $this->container->make($type) : $type;
        $this->conditions[$type->key()] = $type;
    }

    /**
     * @param  ActionType|class-string<ActionType>  $type
     */
    public function action(ActionType|string $type): void
    {
        $type = is_string($type) ? $this->container->make($type) : $type;
        $this->actions[$type->key()] = $type;
    }

    public function findCondition(string $key): ?ConditionType
    {
        return $this->conditions[$key] ?? null;
    }

    public function findAction(string $key): ?ActionType
    {
        return $this->actions[$key] ?? null;
    }

    /**
     * @return array<string, ConditionType>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return array<string, ActionType>
     */
    public function actions(): array
    {
        return $this->actions;
    }

    /**
     * Validation rules for a promotion's conditions and actions ([{type, data}] lists).
     *
     * @param  array<int, mixed>  $conditions
     * @param  array<int, mixed>  $actions
     * @return array<string, mixed>
     */
    public function rulesFor(array $conditions, array $actions): array
    {
        $rules = [
            'conditions' => ['array'],
            'conditions.*.type' => ['required', 'string', 'in:'.implode(',', array_keys($this->conditions))],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string', 'in:'.implode(',', array_keys($this->actions))],
        ];

        foreach (['conditions' => $conditions, 'actions' => $actions] as $list => $entries) {
            foreach (array_values($entries) as $index => $entry) {
                $key = is_array($entry) && is_string($entry['type'] ?? null) ? $entry['type'] : null;
                $type = $key === null ? null : ($list === 'conditions' ? $this->findCondition($key) : $this->findAction($key));

                foreach ($type?->rules() ?? [] as $field => $fieldRules) {
                    $rules["{$list}.{$index}.data.{$field}"] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    public function assertKnown(string $kind, string $key): void
    {
        if (($kind === 'condition' ? $this->findCondition($key) : $this->findAction($key)) === null) {
            throw new InvalidArgumentException("Unknown promotion {$kind} [{$key}].");
        }
    }
}
