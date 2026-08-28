import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type ProductShow = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    price: string | number;
    discount_price: string | number | null;
    image: string | null;
    stock: number;
    sku: string | null;
    category: { id: number; title: string; slug: string } | null;
    attributes: { attribute: string | null; value: string | null }[];
};

export default function ShopShow({ product }: { product: ProductShow }) {
    const hasDiscount = product.discount_price !== null && Number(product.discount_price) > 0;
    const { data, setData, post, processing, errors } = useForm({
        product_id: product.id,
        quantity: 1,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('cart.store'));
    };

    return (
        <StorefrontLayout>
            <Head title={product.title} />
            <div className="mb-6 text-sm">
                <Link href={route('shop.index')} className="text-muted-foreground hover:text-foreground">
                    Shop
                </Link>
                {product.category && (
                    <>
                        <span className="text-muted-foreground"> / </span>
                        <Link href={route('shop.index', { category: product.category.slug })} className="text-muted-foreground hover:text-foreground">
                            {product.category.title}
                        </Link>
                    </>
                )}
            </div>

            <div className="grid gap-10 lg:grid-cols-2">
                <div className="bg-muted overflow-hidden rounded-xl border">
                    {product.image ? (
                        <img src={product.image} alt={product.title} className="aspect-square w-full object-cover" />
                    ) : (
                        <div className="text-muted-foreground flex aspect-square items-center justify-center">No image</div>
                    )}
                </div>

                <div>
                    {product.category && <Badge variant="secondary">{product.category.title}</Badge>}
                    <h1 className="mt-3 mb-4 text-3xl font-semibold tracking-tight">{product.title}</h1>
                    <div className="mb-6 flex items-baseline gap-3">
                        <span className="text-2xl font-semibold">{formatMoney(hasDiscount ? product.discount_price : product.price)}</span>
                        {hasDiscount && <span className="text-muted-foreground line-through">{formatMoney(product.price)}</span>}
                    </div>
                    {product.description && <p className="text-muted-foreground mb-6 whitespace-pre-line">{product.description}</p>}
                    {product.sku && <p className="text-muted-foreground mb-4 text-sm">SKU: {product.sku}</p>}

                    {product.attributes.length > 0 && (
                        <dl className="mb-6 grid gap-2 text-sm">
                            {product.attributes.map((item, index) => (
                                <div key={`${item.attribute}-${index}`} className="flex gap-2">
                                    <dt className="text-muted-foreground">{item.attribute}:</dt>
                                    <dd>{item.value ?? '—'}</dd>
                                </div>
                            ))}
                        </dl>
                    )}

                    <form onSubmit={submit} className="grid max-w-sm gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                type="number"
                                min={1}
                                max={product.stock}
                                value={data.quantity}
                                onChange={(event) => setData('quantity', Number(event.target.value))}
                                disabled={product.stock < 1}
                            />
                            <InputError message={errors.quantity ?? errors.product_id} />
                        </div>
                        <Button type="submit" disabled={processing || product.stock < 1}>
                            {product.stock < 1 ? 'Out of stock' : 'Add to cart'}
                        </Button>
                    </form>
                </div>
            </div>
        </StorefrontLayout>
    );
}
