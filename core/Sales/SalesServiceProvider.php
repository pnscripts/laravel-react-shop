<?php

namespace PnShop\Sales;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Invoices\HtmlInvoiceRenderer;
use PnShop\Sales\Invoices\InvoiceRenderer;
use PnShop\Sales\Invoices\IssueInvoiceAutomatically;
use PnShop\Sales\Models\Invoice;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Policies\OrderPolicy;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;

/**
 * Sales module: orders, checkout, payments.
 */
class SalesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderWorkflow::class);
        $this->app->bindIf(InvoiceRenderer::class, HtmlInvoiceRenderer::class);

        Relation::morphMap(['order' => Order::class, 'invoice' => Invoice::class]);
    }

    protected function permissions(): array
    {
        return [
            new Permission('sales.orders.view', 'View orders', 'Sales'),
            new Permission('sales.orders.update', 'Change order status', 'Sales'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'sales',
            'Orders',
            new SettingDefinition('order_number_prefix', SettingType::String, 'Order number prefix', default: 'ORD-', help: 'Applies to new orders, e.g. ORD-000042.', rules: ['max:12']),
            new SettingDefinition('order_number_digits', SettingType::Integer, 'Order number digits', default: 6, required: true, help: 'The order id is padded with zeros to this length.', rules: ['min:1', 'max:12']),
            new SettingDefinition('invoice_on', SettingType::Select, 'Issue invoices', default: 'paid', required: true, options: [
                'paid' => 'When the order is paid',
                'placed' => 'When the order is placed',
                'manual' => 'Only when staff issue them',
            ]),
            new SettingDefinition('invoice_prefix', SettingType::String, 'Invoice number prefix', default: 'INV-', rules: ['max:12']),
            new SettingDefinition('invoice_digits', SettingType::Integer, 'Invoice number digits', default: 6, required: true, rules: ['min:1', 'max:12']),
            new SettingDefinition('invoice_legal_name', SettingType::String, 'Legal name on invoices', help: 'Defaults to the store name.', rules: ['max:255']),
            new SettingDefinition('invoice_tax_number', SettingType::String, 'Tax / VAT number', rules: ['max:64']),
            new SettingDefinition('invoice_footer', SettingType::Text, 'Invoice footer', help: 'e.g. bank details or legal notes.'),
        ));

        Event::listen([OrderPlaced::class, OrderStateChanged::class], IssueInvoiceAutomatically::class);
    }
}
