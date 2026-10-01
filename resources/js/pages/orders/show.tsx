import { TotalsBreakdown } from '@/components/totals-breakdown';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Money, type Totals } from '@/types';
import { Head, Link } from '@inertiajs/react';

type OrderShow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    shipping_address: string[];
    billing_address: string[] | null;
    status: string | null;
    payment_method: string | null;
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

export default function OrderShow({ order }: { order: OrderShow }) {
    const t = useTranslations();

    return (
        <StorefrontLayout>
            <Head title={t('Order #:id', { id: order.id })} />
            <h1 className="mb-2 text-3xl font-semibold tracking-tight">{t('Order #:id', { id: order.id })}</h1>
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
                    <p>{t('Status: :status', { status: order.status ?? t('pending') })}</p>
                    <p>{t('Payment: :method', { method: order.payment_method ?? '—' })}</p>
                    {order.created_at && <p>{t('Placed: :date', { date: order.created_at })}</p>}
                </div>
            </div>

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
                </div>
            </div>
            <div className="flex justify-end">
                <Button asChild>
                    <Link href={route('shop.index')}>{t('Continue shopping')}</Link>
                </Button>
            </div>
        </StorefrontLayout>
    );
}
