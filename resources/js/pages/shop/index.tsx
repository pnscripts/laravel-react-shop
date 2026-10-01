import { PaginationLinks } from '@/components/pagination-links';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type Paginated, type ProductCard as ProductCardType } from '@/types';
import { Head, Link } from '@inertiajs/react';

type CategoryLink = { id: number; title: string; slug: string };
type Category = CategoryLink & { children: CategoryLink[] };
type Brand = { id: number; name: string; slug: string };

export default function ShopIndex({
    products,
    categories,
    brands,
    filters,
}: {
    products: Paginated<ProductCardType>;
    categories: Category[];
    brands: Brand[];
    filters: { category: string | null; category_path: string[]; brand: string | null };
}) {
    const t = useTranslations();
    const activeRoot = categories.find((category) => filters.category_path.includes(category.slug));
    const withBrand = (params: Record<string, string>) => (filters.brand ? { ...params, brand: filters.brand } : params);

    return (
        <StorefrontLayout>
            <Head title={t('Shop')} />
            <div className="mb-8">
                <h1 className="mb-2 text-3xl font-semibold tracking-tight">{t('Shop')}</h1>
                <p className="text-muted-foreground">{t('Active products only. Filter by category if you like.')}</p>
            </div>

            <div className="mb-3 flex flex-wrap gap-2">
                <Button variant={filters.category ? 'outline' : 'default'} size="sm" asChild>
                    <Link href={route('shop.index', withBrand({}))}>{t('All')}</Link>
                </Button>
                {categories.map((category) => (
                    <Button key={category.id} variant={activeRoot?.id === category.id ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={route('shop.index', withBrand({ category: category.slug }))}>{category.title}</Link>
                    </Button>
                ))}
            </div>

            {activeRoot && activeRoot.children.length > 0 && (
                <div className="mb-3 flex flex-wrap gap-2 pl-4">
                    {activeRoot.children.map((child) => (
                        <Button key={child.id} variant={filters.category === child.slug ? 'secondary' : 'ghost'} size="sm" asChild>
                            <Link href={route('shop.index', withBrand({ category: child.slug }))}>{child.title}</Link>
                        </Button>
                    ))}
                </div>
            )}

            {brands.length > 0 && (
                <div className="mb-8 flex flex-wrap items-center gap-2">
                    <span className="text-muted-foreground text-sm">{t('Brand')}:</span>
                    {brands.map((brand) => (
                        <Button key={brand.id} variant={filters.brand === brand.slug ? 'default' : 'outline'} size="sm" asChild>
                            <Link
                                href={route(
                                    'shop.index',
                                    filters.brand === brand.slug
                                        ? filters.category
                                            ? { category: filters.category }
                                            : {}
                                        : { ...(filters.category ? { category: filters.category } : {}), brand: brand.slug },
                                )}
                            >
                                {brand.name}
                            </Link>
                        </Button>
                    ))}
                </div>
            )}

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
