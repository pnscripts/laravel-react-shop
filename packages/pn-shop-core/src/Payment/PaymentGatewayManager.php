<?php

namespace PnShop\Payment;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use PnShop\Payment\Contracts\PaymentGateway;

/**
 * Registry of the payment gateways core and extensions provide.
 */
final class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>|PaymentGateway> */
    private array $gateways = [];

    public function __construct(private Container $container) {}

    /**
     * @param  class-string<PaymentGateway>|PaymentGateway  $gateway
     */
    public function register(string|PaymentGateway $gateway): void
    {
        $instance = is_string($gateway) ? $this->container->make($gateway) : $gateway;

        $this->gateways[$instance->code()] = $instance;
    }

    public function has(string $code): bool
    {
        return isset($this->gateways[$code]);
    }

    public function get(string $code): PaymentGateway
    {
        $gateway = $this->gateways[$code] ?? throw new InvalidArgumentException("Payment gateway [{$code}] is not registered.");

        return is_string($gateway) ? $this->gateways[$code] = $this->container->make($gateway) : $gateway;
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return array_map(fn (string $code) => $this->get($code), array_combine(array_keys($this->gateways), array_keys($this->gateways)));
    }

    /**
     * @return array<string, string> code => label, for admin selects
     */
    public function options(): array
    {
        return array_map(fn (PaymentGateway $gateway) => $gateway->label(), $this->all());
    }
}
