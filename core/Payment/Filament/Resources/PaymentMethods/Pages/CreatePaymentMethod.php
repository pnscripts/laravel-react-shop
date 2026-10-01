<?php

namespace PnShop\Payment\Filament\Resources\PaymentMethods\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Localization\Filament\SavesTranslations;
use PnShop\Payment\Filament\Resources\PaymentMethods\PaymentMethodResource;

class CreatePaymentMethod extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = PaymentMethodResource::class;
}
