<?php

namespace PnShop\Foundation\Extension;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

/**
 * Named, ordered pipelines that core and extensions can add stages to,
 * e.g. "cart.totals" or "catalog.price". Each stage receives the payload and
 * a $next closure, like Laravel middleware.
 */
final class PipelineRegistry
{
    /** @var array<string, list<array{stage: class-string|Closure, priority: int, order: int}>> */
    private array $stages = [];

    private int $order = 0;

    public function __construct(private Container $container) {}

    /**
     * Add a stage. Lower priorities run first; equal priorities run in registration order.
     *
     * @param  class-string|Closure  $stage
     */
    public function stage(string $pipeline, string|Closure $stage, int $priority = 100): void
    {
        $this->stages[$pipeline][] = ['stage' => $stage, 'priority' => $priority, 'order' => $this->order++];
    }

    /**
     * @return list<class-string|Closure>
     */
    public function stages(string $pipeline): array
    {
        $stages = $this->stages[$pipeline] ?? [];

        usort($stages, fn (array $a, array $b) => [$a['priority'], $a['order']] <=> [$b['priority'], $b['order']]);

        return array_map(fn (array $stage) => $stage['stage'], $stages);
    }

    public function run(string $pipeline, mixed $payload): mixed
    {
        return (new Pipeline($this->container))
            ->send($payload)
            ->through($this->stages($pipeline))
            ->thenReturn();
    }
}
