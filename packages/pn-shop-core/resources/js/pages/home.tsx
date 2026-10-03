import { Blocks } from '@/components/blocks';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type ContentBlock, type ProductCard as ProductCardType, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Home({ products, blocks, title }: { products: ProductCardType[]; blocks: ContentBlock[] | null; title: string | null }) {
    const t = useTranslations();
    const { name } = usePage<SharedData>().props;

    // A CMS page marked as the homepage replaces the default home.
    if (blocks) {
        return (
            <StorefrontLayout>
                <Head title={title ?? t('Shop')} />
                <Blocks blocks={blocks} />
            </StorefrontLayout>
        );
    }

    return (
        <StorefrontLayout>
            {/* The home page is titled with the store name alone, as the server renders it. */}
            <Head title="" />
            <section className="mb-10 rounded-xl border px-6 py-12 md:px-10">
                <h1 className="mb-3 text-3xl font-semibold tracking-tight">{t('Welcome to :name', { name })}</h1>
                <p className="text-muted-foreground mb-6 max-w-2xl">
                    {t('Browse the catalog, add products to your cart and check out as a guest or with your account.')}
                </p>
                <Button asChild>
                    <Link href={route('shop.index')}>{t('Browse the catalog')}</Link>
                </Button>
            </section>

            <section>
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-xl font-semibold">{t('Featured products')}</h2>
                    <Button variant="ghost" asChild>
                        <Link href={route('shop.index')}>{t('View all')}</Link>
                    </Button>
                </div>
                {products.length === 0 ? (
                    <p className="text-muted-foreground">{t('No products are available yet.')}</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {products.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </section>
        </StorefrontLayout>
    );
}
