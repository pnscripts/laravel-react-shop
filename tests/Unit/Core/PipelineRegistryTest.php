<?php

namespace Tests\Unit\Core;

use Closure;
use PnShop\Foundation\Extension\PipelineRegistry;
use Tests\TestCase;

class PipelineRegistryTest extends TestCase
{
    public function test_stages_run_by_priority_then_registration_order(): void
    {
        $pipelines = new PipelineRegistry($this->app);

        $append = fn (string $suffix) => fn (string $payload, Closure $next) => $next($payload.$suffix);

        $pipelines->stage('demo', $append('c'), priority: 300);
        $pipelines->stage('demo', $append('a'), priority: 100);
        $pipelines->stage('demo', $append('b1'));
        $pipelines->stage('demo', $append('b2'));

        $this->assertSame('-ab1b2c', $pipelines->run('demo', '-'));
    }

    public function test_an_unknown_pipeline_returns_the_payload(): void
    {
        $this->assertSame(42, (new PipelineRegistry($this->app))->run('nothing', 42));
    }
}
