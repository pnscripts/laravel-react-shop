import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Money, type OrderStates } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

type OrderSummary = OrderStates & { id: number; created_at: string; total: Money };

export default function Dashboard({ recentOrders, defaultAddress }: { recentOrders: OrderSummary[]; defaultAddress: string[] | null }) {
    const t = useTranslations();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Dashboard')} />
            <div className="grid gap-4 p-4 md:grid-cols-3">
                <Card className="md:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>{t('Recent orders')}</CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={route('account.orders')}>{t('View all')}</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentOrders.length === 0 ? (
                            <div className="text-muted-foreground flex flex-col items-start gap-3 text-sm">
                                <p>{t('You have not placed any orders yet.')}</p>
                                <Button asChild size="sm">
                                    <Link href={route('shop.index')}>{t('Go to shop')}</Link>
                                </Button>
                            </div>
                        ) : (
                            <ul className="divide-y text-sm">
                                {recentOrders.map((order) => (
                                    <li key={order.id} className="flex items-center justify-between gap-4 py-2">
                                        <Link href={route('orders.show', order.id)} className="font-medium hover:underline">
                                            {t('Order :number', { number: order.number })}
                                        </Link>
                                        <span className="text-muted-foreground">{order.created_at}</span>
                                        <span>{order.status}</span>
                                        <span className="font-medium">{order.total.formatted}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>{t('Default address')}</CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={route('account.addresses')}>{t('Manage')}</Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="text-sm">
                        {defaultAddress ? (
                            <address className="not-italic">
                                {defaultAddress.map((line) => (
                                    <div key={line}>{line}</div>
                                ))}
                            </address>
                        ) : (
                            <p className="text-muted-foreground">{t('No address saved yet.')}</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
