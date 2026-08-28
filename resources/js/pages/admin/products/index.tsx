import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

type AdminProduct = {
    id: number;
    title: string;
    slug: string;
    price: string | number;
    stock: number;
    is_active: boolean;
    category: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/admin/products' },
];

export default function AdminProducts({ products }: { products: Paginated<AdminProduct> }) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin products" />
            <div className="flex flex-col gap-4 p-4">
                {flash.success && <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{flash.success}</div>}
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Products</h1>
                    <Button asChild>
                        <Link href={route('admin.products.create')}>Create product</Link>
                    </Button>
                </div>
                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Title</th>
                                <th className="px-4 py-3 font-medium">Category</th>
                                <th className="px-4 py-3 font-medium">Price</th>
                                <th className="px-4 py-3 font-medium">Stock</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <tr key={product.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">{product.title}</td>
                                    <td className="px-4 py-3">{product.category ?? '—'}</td>
                                    <td className="px-4 py-3">{product.price}</td>
                                    <td className="px-4 py-3">{product.stock}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant={product.is_active ? 'default' : 'secondary'}>{product.is_active ? 'Active' : 'Inactive'}</Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={route('admin.products.edit', product.id)}>Edit</Link>
                                        </Button>
                                        {product.is_active && (
                                            <Button variant="ghost" size="sm" onClick={() => router.patch(route('admin.products.deactivate', product.id))}>
                                                Deactivate
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <PaginationLinks paginator={products} />
            </div>
        </AppLayout>
    );
}
