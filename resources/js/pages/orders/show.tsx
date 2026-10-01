import { OrderReturns, type OrderReturn, type Returnable } from '@/components/order-returns';
import { Slot } from '@/components/slot';
import { TotalsBreakdown } from '@/components/totals-breakdown';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Money, type OrderStates, type Totals } from '@/types';
import { Head, Link } from '@inertiajs/react';

type OrderShow = OrderStates & {
    id: number;
    name: string;
    email: string;
    phone: string;
    shipping_address: string[];
    billing_address: string[] | null;
    payment_method: string | null;
    payment_instructions: string | null;
    shipping_method: string | null;
    invoice: { number: string; url: string } | null;
    refunds: { id: number; amount: Money; date: string | null }[];
    shipments: {
        id: number;
        carrier: string | null;
        tracking_number: string | null;
        tracking_url: string | null;
        shipped_at: string | null;
        items: number;
    }[];
    created_at: string | null;
    items: {
        id: number;
        title: string;
        variant_label: string | null;
        quantity: number;
        price: Money;
        sale_price: Money | null;
        unit_price: Money;
        line_total: Money;
    }[];
    totals: Totals;
};

const AddressLines = ({ lines }: { lines: string[] }) => (
    <address className="not-italic">
        {lines.map((line) => (
            <span key={line} className="block">
                {line}
            </span>
        ))}
    </address>
);

export default function OrderShow({ order, returns, returnable }: { order: OrderShow; returns: OrderReturn[]; returnable: Returnable }) {
    const t = useTranslations();

    return (
        <StorefrontLayout>
            <Head title={t('Order :number', { number: order.number })} />
            <h1 className="mb-2 text-3xl font-semibold tracking-tight">{t('Order :number', { number: order.number })}</h1>
            <p className="text-muted-foreground mb-8">{t('Thanks — we saved your order. Keep this page if you checked out as a guest.')}</p>

            <div className="mb-8 grid gap-6 md:grid-cols-3">
                <div className="rounded-xl border p-6">
                    <h2 className="mb-3 font-semibold">{t('Shipping address')}</h2>
                    <AddressLines lines={order.shipping_address} />
                    <p className="text-muted-foreground mt-2 text-sm">{order.email}</p>
                    <p className="text-muted-foreground text-sm">{order.phone}</p>
                </div>
                {order.billing_address && (
                    <div className="rounded-xl border p-6">
                        <h2 className="mb-3 font-semibold">{t('Billing address')}</h2>
                        <AddressLines lines={order.billing_address} />
                    </div>
                )}
                <div className="rounded-xl border p-6">
                    <h2 className="mb-3 font-semibold">{t('Details')}</h2>
                    <p>{t('Status: :status', { status: order.status })}</p>
                    <p>{t('Payment status: :status', { status: order.payment_status })}</p>
                    <p>{t('Shipping: :status', { status: order.fulfillment_status })}</p>
                    {order.shipping_method && <p>{t('Delivery: :method', { method: order.shipping_method })}</p>}
                    <p>{t('Payment: :method', { method: order.payment_method ?? '—' })}</p>
                    {order.created_at && <p>{t('Placed: :date', { date: order.created_at })}</p>}
                    {order.invoice && (
                        <p className="mt-2">
                            <a href={order.invoice.url} target="_blank" rel="noopener" className="font-medium underline">
                                {t('Invoice :number', { number: order.invoice.number })}
                            </a>
                        </p>
                    )}
                </div>
            </div>

            {order.shipments.length > 0 && (
                <section className="mb-8 rounded-xl border p-6">
                    <h2 className="mb-3 font-semibold">{t('Shipments')}</h2>
                    <ul className="space-y-2 text-sm">
                        {order.shipments.map((shipment) => (
                            <li key={shipment.id} className="flex flex-wrap justify-between gap-2">
                                <span>
                                    {t(':count items shipped :date', { count: shipment.items, date: shipment.shipped_at ?? '' })}
                                    {shipment.carrier && ` · ${shipment.carrier}`}
                                </span>
                                {shipment.tracking_number &&
                                    (shipment.tracking_url ? (
                                        <a href={shipment.tracking_url} target="_blank" rel="noopener noreferrer" className="font-medium underline">
                                            {t('Track :number', { number: shipment.tracking_number })}
                                        </a>
                                    ) : (
                                        <span>{t('Tracking number: :number', { number: shipment.tracking_number })}</span>
                                    ))}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {order.payment_instructions && (
                <section className="bg-muted/40 mb-8 rounded-xl border p-6">
                    <h2 className="mb-2 font-semibold">{t('How to pay')}</h2>
                    <p className="text-sm whitespace-pre-line">{order.payment_instructions}</p>
                </section>
            )}

            <div className="overflow-hidden rounded-xl border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium">{t('Item')}</th>
                            <th className="px-4 py-3 font-medium">{t('Qty')}</th>
                            <th className="px-4 py-3 font-medium">{t('Total')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {order.items.map((item) => (
                            <tr key={item.id} className="border-t">
                                <td className="px-4 py-3">
                                    {item.title}
                                    {item.variant_label && <div className="text-muted-foreground text-xs">{item.variant_label}</div>}
                                </td>
                                <td className="px-4 py-3">{item.quantity}</td>
                                <td className="px-4 py-3">{item.line_total.formatted}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="mt-4 grid gap-4 sm:grid-cols-[1fr_20rem]">
                <div className="sm:col-start-2">
                    <TotalsBreakdown totals={order.totals} />
                    <Slot name="order.after_totals" props={{ order }} />
                    {order.refunds.map((refund) => (
                        <p key={refund.id} className="text-muted-foreground -mt-4 mb-6 flex justify-between text-sm">
                            <span>{t('Refunded :date', { date: refund.date ?? '' })}</span>
                            <span>−{refund.amount.formatted}</span>
                        </p>
                    ))}
                </div>
            </div>
            <OrderReturns orderId={order.id} returns={returns} returnable={returnable} />
            <div className="mt-8 flex justify-end">
                <Button asChild>
                    <Link href={route('shop.index')}>{t('Continue shopping')}</Link>
                </Button>
            </div>
        </StorefrontLayout>
    );
}
