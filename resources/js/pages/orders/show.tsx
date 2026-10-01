import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Money } from '@/types';
import { Head, Link } from '@inertiajs/react';

type OrderShow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    status: string | null;
    payment_method: string | null;
    created_at: string | null;
    items: {
        id: number;
        title: string;
        quantity: number;
        price: Money;
        discount_price: Money | null;
        unit_price: Money;
        line_total: Money;
    }[];
    total: Money;
};

export default function OrderShow({ order }: { order: OrderShow }) {
    return (
        <StorefrontLayout>
            <Head title={`Order #${order.id}`} />
            <h1 className="mb-2 text-3xl font-semibold tracking-tight">Order #{order.id}</h1>
            <p className="text-muted-foreground mb-8">Thanks — we saved your order. Keep this page if you checked out as a guest.</p>

            <div className="mb-8 grid gap-6 md:grid-cols-2">
                <div className="rounded-xl border p-6">
                    <h2 className="mb-3 font-semibold">Customer</h2>
                    <p>{order.name}</p>
                    <p className="text-muted-foreground text-sm">{order.email}</p>
                    <p className="text-muted-foreground text-sm">{order.phone}</p>
                    <p className="mt-2 whitespace-pre-line">{order.address}</p>
                </div>
                <div className="rounded-xl border p-6">
                    <h2 className="mb-3 font-semibold">Details</h2>
                    <p>Status: {order.status ?? 'pending'}</p>
                    <p>Payment: {order.payment_method ?? '—'}</p>
                    {order.created_at && <p>Placed: {order.created_at}</p>}
                </div>
            </div>

            <div className="overflow-hidden rounded-xl border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left">
                        <tr>
                            <th className="px-4 py-3 font-medium">Item</th>
                            <th className="px-4 py-3 font-medium">Qty</th>
                            <th className="px-4 py-3 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {order.items.map((item) => (
                            <tr key={item.id} className="border-t">
                                <td className="px-4 py-3">{item.title}</td>
                                <td className="px-4 py-3">{item.quantity}</td>
                                <td className="px-4 py-3">{item.line_total.formatted}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="mt-4 flex items-center justify-between">
                <p className="font-semibold">Total {order.total.formatted}</p>
                <Button asChild>
                    <Link href={route('shop.index')}>Continue shopping</Link>
                </Button>
            </div>
        </StorefrontLayout>
    );
}
