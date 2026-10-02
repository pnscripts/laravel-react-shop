<?php

namespace PnShop\Payment\Filament\Resources\PaymentMethods\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Localization\Filament\SavesTranslations;
use PnShop\Payment\Filament\Resources\PaymentMethods\PaymentMethodResource;

class EditPaymentMethod extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
