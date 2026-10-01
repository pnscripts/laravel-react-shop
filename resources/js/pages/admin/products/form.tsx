import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type ProductForm = {
    id: number;
    product_category_id: number;
    title: string;
    description: string | null;
    price: string | number;
    discount_price: string | number | null;
    stock: number;
    sku: string | null;
    barcode: string | null;
    image: string | null;
    is_active: boolean;
};

type Category = { id: number; title: string };

export default function AdminProductForm({ product, categories }: { product: ProductForm | null; categories: Category[] }) {
    const isEdit = Boolean(product);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Products', href: '/admin/products' },
        { title: isEdit ? 'Edit' : 'Create', href: isEdit ? `/admin/products/${product?.id}/edit` : '/admin/products/create' },
    ];

    const { data, setData, post, put, processing, errors } = useForm({
        product_category_id: product?.product_category_id ? String(product.product_category_id) : '',
        title: product?.title ?? '',
        description: product?.description ?? '',
        price: product?.price ?? '',
        discount_price: product?.discount_price ?? '',
        stock: product?.stock ?? 0,
        sku: product?.sku ?? '',
        barcode: product?.barcode ?? '',
        image: product?.image ?? '',
        is_active: product?.is_active ?? true,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (isEdit && product) {
            put(route('admin.products.update', product.id));
        } else {
            post(route('admin.products.store'));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEdit ? 'Edit product' : 'Create product'} />
            <form onSubmit={submit} className="mx-auto grid max-w-2xl gap-4 p-4">
                <h1 className="text-xl font-semibold">{isEdit ? 'Edit product' : 'Create product'}</h1>
                <div className="grid gap-2">
                    <Label htmlFor="title">Title</Label>
                    <Input id="title" value={data.title} onChange={(event) => setData('title', event.target.value)} required />
                    <InputError message={errors.title} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="product_category_id">Category</Label>
                    <select
                        id="product_category_id"
                        className="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                        value={data.product_category_id}
                        onChange={(event) => setData('product_category_id', event.target.value)}
                        required
                    >
                        <option value="">Select a category</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.title}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.product_category_id} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="description">Description</Label>
                    <Textarea id="description" value={data.description} onChange={(event) => setData('description', event.target.value)} />
                    <InputError message={errors.description} />
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="price">Price</Label>
                        <Input
                            id="price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.price}
                            onChange={(event) => setData('price', event.target.value)}
                            required
                        />
                        <InputError message={errors.price} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="discount_price">Discount price</Label>
                        <Input
                            id="discount_price"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.discount_price}
                            onChange={(event) => setData('discount_price', event.target.value)}
                        />
                        <InputError message={errors.discount_price} />
                    </div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="stock">Stock</Label>
                        <Input
                            id="stock"
                            type="number"
                            min="0"
                            value={data.stock}
                            onChange={(event) => setData('stock', Number(event.target.value))}
                            required
                        />
                        <InputError message={errors.stock} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="sku">SKU</Label>
                        <Input id="sku" value={data.sku} onChange={(event) => setData('sku', event.target.value)} />
                        <InputError message={errors.sku} />
                    </div>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="barcode">Barcode</Label>
                    <Input id="barcode" value={data.barcode} onChange={(event) => setData('barcode', event.target.value)} />
                    <InputError message={errors.barcode} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="image">Image URL</Label>
                    <Input id="image" value={data.image} onChange={(event) => setData('image', event.target.value)} />
                    <InputError message={errors.image} />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox checked={data.is_active} onCheckedChange={(checked) => setData('is_active', Boolean(checked))} />
                    Active (visible on the storefront)
                </label>
                <div className="flex gap-2">
                    <Button type="submit" disabled={processing}>
                        {isEdit ? 'Save changes' : 'Create product'}
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={route('admin.products.index')}>Cancel</Link>
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
