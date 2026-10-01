import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Money, type ProductImage } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type ProductShow = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    price: Money;
    discount_price: Money | null;
    image: ProductImage | null;
    gallery: ProductImage[];
    stock: number;
    sku: string | null;
    category: { id: number; title: string; slug: string } | null;
    brand: { name: string; slug: string } | null;
    breadcrumbs: { title: string; slug: string }[];
    attributes: { attribute: string | null; value: string | null }[];
};

export default function ShopShow({ product }: { product: ProductShow }) {
    const t = useTranslations();
    const [shownIndex, setShownIndex] = useState(0);
    const shownImage = product.gallery[shownIndex] ?? product.image;
    const hasDiscount = product.discount_price !== null && product.discount_price.minor > 0;
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
                    {t('Shop')}
                </Link>
                {product.breadcrumbs.map((crumb) => (
                    <span key={crumb.slug}>
                        <span className="text-muted-foreground"> / </span>
                        <Link href={route('shop.index', { category: crumb.slug })} className="text-muted-foreground hover:text-foreground">
                            {crumb.title}
                        </Link>
                    </span>
                ))}
            </div>

            <div className="grid gap-10 lg:grid-cols-2">
                <div className="bg-muted overflow-hidden rounded-xl border">
                    {shownImage ? (
                        <img
                            src={shownImage.url}
                            srcSet={shownImage.srcset || undefined}
                            sizes="(min-width: 1024px) 50vw, 100vw"
                            alt={shownImage.alt}
                            className="aspect-square w-full object-cover"
                        />
                    ) : (
                        <div className="text-muted-foreground flex aspect-square items-center justify-center">{t('No image')}</div>
                    )}
                    {product.gallery.length > 1 && (
                        <div className="bg-background flex gap-2 overflow-x-auto p-2">
                            {product.gallery.map((image, index) => (
                                <button
                                    key={image.id ?? index}
                                    type="button"
                                    onClick={() => setShownIndex(index)}
                                    aria-label={t('Show image :number', { number: index + 1 })}
                                    aria-current={index === shownIndex}
                                    className={`size-16 shrink-0 overflow-hidden rounded-md border-2 ${index === shownIndex ? 'border-primary' : 'border-transparent'}`}
                                >
                                    <img src={image.thumb} alt="" className="size-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div>
                    <div className="flex flex-wrap gap-2">
                        {product.category && <Badge variant="secondary">{product.category.title}</Badge>}
                        {product.brand && (
                            <Badge variant="outline" asChild>
                                <Link href={route('shop.index', { brand: product.brand.slug })}>{product.brand.name}</Link>
                            </Badge>
                        )}
                    </div>
                    <h1 className="mt-3 mb-4 text-3xl font-semibold tracking-tight">{product.title}</h1>
                    <div className="mb-6 flex items-baseline gap-3">
                        <span className="text-2xl font-semibold">{(hasDiscount ? product.discount_price : product.price)?.formatted}</span>
                        {hasDiscount && <span className="text-muted-foreground line-through">{product.price.formatted}</span>}
                    </div>
                    {product.description && <p className="text-muted-foreground mb-6 whitespace-pre-line">{product.description}</p>}
                    {product.sku && <p className="text-muted-foreground mb-4 text-sm">{t('SKU: :sku', { sku: product.sku })}</p>}

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
                            <Label htmlFor="quantity">{t('Quantity')}</Label>
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
                            {product.stock < 1 ? t('Out of stock') : t('Add to cart')}
                        </Button>
                    </form>
                </div>
            </div>
        </StorefrontLayout>
    );
}
