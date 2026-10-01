<?php

namespace PnShop\Sales;

use Illuminate\Routing\UrlGenerator;
use PnShop\Localization\Localization;
use PnShop\Sales\Models\Order;

/**
 * Links to an order's page for emails: signed (guests have no session in their mail
 * client) and in the language the order was placed in. Anyone with the link sees the
 * order, like a parcel tracking link.
 */
final class OrderLinks
{
    public static function signedShow(Order $order): string
    {
        $localization = app(Localization::class);
        $locale = $order->locale !== null && $localization->isSupported($order->locale) ? $order->locale : $localization->defaultLocale();

        /** @var UrlGenerator $urls */
        $urls = app('url');
        $urls->forceRootUrl(rtrim(config()->string('app.url'), '/').$localization->prefix($locale));

        try {
            return $urls->signedRoute('orders.show', ['order' => $order]);
        } finally {
            $urls->forceRootUrl(null);
        }
    }
}
