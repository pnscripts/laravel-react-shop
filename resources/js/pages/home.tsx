import { Blocks } from '@/components/blocks';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type ContentBlock, type ProductCard as ProductCardType } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Home({ products, blocks, title }: { products: ProductCardType[]; blocks: ContentBlock[] | null; title: string | null }) {
    const t = useTranslations();

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
            <Head title={t('Shop')} />
            <section className="mb-10 rounded-xl border px-6 py-12 md:px-10">
                <h1 className="mb-3 text-3xl font-semibold tracking-tight">{t('A simple Laravel + React shop')}</h1>
                <p className="text-muted-foreground mb-6 max-w-2xl">
                    {t(
                        'Browse active products, add them to your cart, and check out as a guest or a signed-in customer. Payment is cash on delivery or bank transfer.',
                    )}
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
