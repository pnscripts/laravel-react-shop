<?php

namespace PnShop\Shipping;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use PnShop\Shipping\Contracts\ShippingCarrier;

/**
 * Registry of the shipping carriers core and extensions provide.
 */
final class ShippingCarrierManager
{
    /** @var array<string, ShippingCarrier> */
    private array $carriers = [];

    public function __construct(private Container $container) {}

    /**
     * @param  class-string<ShippingCarrier>|ShippingCarrier  $carrier
     */
    public function register(string|ShippingCarrier $carrier): void
    {
        $instance = is_string($carrier) ? $this->container->make($carrier) : $carrier;

        $this->carriers[$instance->code()] = $instance;
    }

    public function has(string $code): bool
    {
        return isset($this->carriers[$code]);
    }

    public function get(string $code): ShippingCarrier
    {
        return $this->carriers[$code] ?? throw new InvalidArgumentException("Shipping carrier [{$code}] is not registered.");
    }

    /**
     * @return array<string, ShippingCarrier>
     */
    public function all(): array
    {
        return $this->carriers;
    }

    /**
     * @return array<string, string> code => label, for admin selects
     */
    public function options(): array
    {
        return array_map(fn (ShippingCarrier $carrier) => $carrier->label(), $this->carriers);
    }
}
