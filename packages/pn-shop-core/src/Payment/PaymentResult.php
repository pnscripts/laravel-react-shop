<?php

namespace PnShop\Payment;

/**
 * What a gateway reports back. `message` may be shown to the customer or staff, so it
 * must not contain secrets; `data` is kept on the transaction for staff.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        public PaymentOutcome $outcome,
        public ?string $reference = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
        public array $data = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function paid(?string $reference = null, array $data = []): self
    {
        return new self(PaymentOutcome::Paid, $reference, data: $data);
    }

    /** @param array<string, mixed> $data */
    public static function authorized(?string $reference = null, array $data = []): self
    {
        return new self(PaymentOutcome::Authorized, $reference, data: $data);
    }

    /** @param array<string, mixed> $data */
    public static function pending(?string $reference = null, array $data = []): self
    {
        return new self(PaymentOutcome::Pending, $reference, data: $data);
    }

    /** @param array<string, mixed> $data */
    public static function redirect(string $url, ?string $reference = null, array $data = []): self
    {
        return new self(PaymentOutcome::Redirect, $reference, $url, data: $data);
    }

    /** @param array<string, mixed> $data */
    public static function failed(string $message, array $data = []): self
    {
        return new self(PaymentOutcome::Failed, message: $message, data: $data);
    }

    /** @param array<string, mixed> $data */
    public static function refunded(?string $reference = null, array $data = []): self
    {
        return new self(PaymentOutcome::Refunded, $reference, data: $data);
    }
}
