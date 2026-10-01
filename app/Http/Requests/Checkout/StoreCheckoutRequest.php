<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use PnShop\Sales\Checkout\CheckoutRules;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return CheckoutRules::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CheckoutRules::attributes();
    }
}
