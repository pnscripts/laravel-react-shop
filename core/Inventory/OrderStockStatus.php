<?php

namespace PnShop\Inventory;

/**
 * Where an order's stock is: held for it, gone with the shipment, or given back.
 */
enum OrderStockStatus: string
{
    case Reserved = 'reserved';
    case Fulfilled = 'fulfilled';
    case Released = 'released';
}
