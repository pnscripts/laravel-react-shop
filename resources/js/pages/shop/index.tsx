import { PaginationLinks } from '@/components/pagination-links';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Paginated, type ProductCard as ProductCardType } from '@/types';
import { Head, Link } from '@inertiajs/react';

type Category = { id: number; title: string; slug: string };

export default function ShopIndex({
    products,
    categories,
    filters,
}: {
    products: Paginated<ProductCardType>;
    categories: Category[];
    filters: { category: string | null };
}) {
    const t = useTranslations();

    return (
        <StorefrontLayout>
            <Head title={t('Shop')} />
            <div className="mb-8">
                <h1 className="mb-2 text-3xl font-semibold tracking-tight">{t('Shop')}</h1>
                <p className="text-muted-foreground">{t('Active products only. Filter by category if you like.')}</p>
            </div>

            <div className="mb-8 flex flex-wrap gap-2">
                <Button variant={filters.category ? 'outline' : 'default'} size="sm" asChild>
                    <Link href={route('shop.index')}>{t('All')}</Link>
                </Button>
                {categories.map((category) => (
                    <Button key={category.id} variant={filters.category === category.slug ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={route('shop.index', { category: category.slug })}>{category.title}</Link>
                    </Button>
                ))}
            </div>

            {products.data.length === 0 ? (
                <p className="text-muted-foreground">{t('No products match this filter.')}</p>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {products.data.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            )}

            <div className="mt-8">
                <PaginationLinks paginator={products} />
            </div>
        </StorefrontLayout>
    );
}
