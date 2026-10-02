<?php

namespace PnShop\Tax;

use Brick\Money\Money;

/**
 * The tax per line and per rate.
 */
final class TaxResult
{
    /** @var array<string, Money> line key => tax */
    private array $byLine = [];

    /** @var array<string, Money> rate name => tax */
    private array $byRate = [];

    public function __construct(private string $currency) {}

    public function add(string $lineKey, string $rateName, Money $tax): void
    {
        $this->byLine[$lineKey] = ($this->byLine[$lineKey] ?? Money::zero($this->currency))->plus($tax);
        $this->byRate[$rateName] = ($this->byRate[$rateName] ?? Money::zero($this->currency))->plus($tax);
    }

    public function forLine(string $key): Money
    {
        return $this->byLine[$key] ?? Money::zero($this->currency);
    }

    /**
     * @return array<string, Money> rate name => tax, in the order rates were applied
     */
    public function byRate(): array
    {
        return array_filter($this->byRate, fn (Money $tax) => ! $tax->isZero());
    }

    public function total(): Money
    {
        return array_reduce($this->byRate, fn (Money $total, Money $tax) => $total->plus($tax), Money::zero($this->currency));
    }
}
