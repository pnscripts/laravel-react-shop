import { PaginationLinks } from '@/components/pagination-links';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

type AdminOrder = {
    id: number;
    name: string;
    email: string;
    status_id: number | null;
    status: string | null;
    payment_method: string | null;
    created_at: string | null;
};

type Status = { id: number; name: string };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Orders', href: '/admin/orders' },
];

export default function AdminOrders({ orders, statuses }: { orders: Paginated<AdminOrder>; statuses: Status[] }) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin orders" />
            <div className="flex flex-col gap-4 p-4">
                {flash.error && <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</div>}
                {flash.success && (
                    <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{flash.success}</div>
                )}
                <h1 className="text-xl font-semibold">Orders</h1>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">ID</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Payment</th>
                                <th className="px-4 py-3 font-medium">Placed</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((order) => (
                                <tr key={order.id} className="border-t">
                                    <td className="px-4 py-3">#{order.id}</td>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{order.name}</div>
                                        <div className="text-muted-foreground">{order.email}</div>
                                    </td>
                                    <td className="px-4 py-3">{order.payment_method ?? '—'}</td>
                                    <td className="px-4 py-3">{order.created_at ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <select
                                                className="border-input h-9 rounded-md border bg-transparent px-2 text-sm"
                                                defaultValue={order.status_id ?? ''}
                                                onChange={(event) => {
                                                    router.patch(route('admin.orders.update', order.id), {
                                                        order_status_id: Number(event.target.value),
                                                    });
                                                }}
                                            >
                                                {statuses.map((status) => (
                                                    <option key={status.id} value={status.id}>
                                                        {status.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <Button variant="ghost" size="sm" asChild>
                                                <a href={route('orders.show', order.id)}>View</a>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks paginator={orders} />
            </div>
        </AppLayout>
    );
}
