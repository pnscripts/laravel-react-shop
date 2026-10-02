import { PaginationLinks } from '@/components/pagination-links';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Money, type OrderStates, type Paginated } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Orders', href: '/account/orders' }];

type OrderRow = OrderStates & { id: number; created_at: string; items_count: number; total: Money };

export default function AccountOrders({ orders }: { orders: Paginated<OrderRow> }) {
    const t = useTranslations();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Orders')} />
            <div className="p-4">
                <h1 className="mb-4 text-2xl font-semibold">{t('Orders')}</h1>
                {orders.data.length === 0 ? (
                    <p className="text-muted-foreground">{t('You have not placed any orders yet.')}</p>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">{t('Order')}</th>
                                    <th className="px-4 py-3 font-medium">{t('Date')}</th>
                                    <th className="px-4 py-3 font-medium">{t('Status')}</th>
                                    <th className="px-4 py-3 font-medium">{t('Items')}</th>
                                    <th className="px-4 py-3 text-right font-medium">{t('Total')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.data.map((order) => (
                                    <tr key={order.id} className="border-t">
                                        <td className="px-4 py-3">
                                            <Link href={route('orders.show', order.id)} className="font-medium hover:underline">
                                                {t('Order :number', { number: order.number })}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3">{order.created_at}</td>
                                        <td className="px-4 py-3">
                                            {order.status}
                                            <div className="text-muted-foreground text-xs">
                                                {order.payment_status} · {order.fulfillment_status}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">{order.items_count}</td>
                                        <td className="px-4 py-3 text-right">{order.total.formatted}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="mt-6">
                    <PaginationLinks paginator={orders} />
                </div>
            </div>
        </AppLayout>
    );
}
