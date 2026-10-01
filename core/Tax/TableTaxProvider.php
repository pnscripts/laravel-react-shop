<?php

namespace PnShop\Tax;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use PnShop\Tax\Contracts\TaxProvider;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxRate;
use PnShop\Tax\Models\TaxZone;

/**
 * Tax from the rate tables: the first tax zone matching the address, the rates for each
 * line's class (or the default class). Rates are applied by priority: rates of one
 * priority add up, compound rates are charged on the amount including earlier taxes.
 * Each rate is rounded per line, half up.
 */
final class TableTaxProvider implements TaxProvider
{
    public function calculate(TaxRequest $request): TaxResult
    {
        $result = new TaxResult($request->currency);

        $zone = TaxZone::query()->orderBy('position')->orderBy('id')->get()
            ->first(fn (TaxZone $zone) => $zone->matches($request->countryCode, $request->postcode));

        if ($zone === null) {
            return $result;
        }

        $rates = $zone->rates()->get()->groupBy('tax_class_id');
        $defaultClass = TaxClass::defaultId();

        foreach ($request->lines as $line) {
            /** @var Collection<int, TaxRate> $lineRates */
            $lineRates = $rates->get($line->taxClassId ?? $defaultClass, collect());

            if ($lineRates->isNotEmpty() && ! $line->amount->isZero()) {
                $this->taxLine($result, $line, $lineRates, $request->pricesIncludeTax);
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, TaxRate>  $rates
     */
    private function taxLine(TaxResult $result, TaxableLine $line, Collection $rates, bool $inclusive): void
    {
        $steps = $rates->groupBy(fn (TaxRate $rate) => $rate->priority.($rate->is_compound ? 'c' : ''))->values();

        // The share of the net amount each rate takes, applying compound rates on top.
        $factors = [];
        $multiplier = BigDecimal::one();

        foreach ($steps as $step) {
            $base = $step->first()?->is_compound ? $multiplier : BigDecimal::one();
            $stepTotal = BigDecimal::zero();

            foreach ($step as $rate) {
                $factor = $base->multipliedBy(BigDecimal::of($rate->rate)->dividedBy(100, 10, RoundingMode::HalfUp));
                $factors[] = [$rate->name, $factor];
                $stepTotal = $stepTotal->plus($factor);
            }

            $multiplier = $multiplier->plus($stepTotal);
        }

        $amount = $line->amount->getAmount();
        $net = $inclusive ? $amount->dividedBy($multiplier, 10, RoundingMode::HalfUp) : $amount;

        foreach ($factors as [$name, $factor]) {
            $result->add($line->key, $name, Money::of($net->multipliedBy($factor), $line->amount->getCurrency(), roundingMode: RoundingMode::HalfUp));
        }
    }
}
