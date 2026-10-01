import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { type ProductCard as ProductCardType } from '@/types';
import { Link } from '@inertiajs/react';

export function ProductGridBlock({ heading, products, more_url }: { heading: string | null; products: ProductCardType[]; more_url: string | null }) {
    const t = useTranslations();

    return (
        <section>
            {(heading || more_url) && (
                <div className="mb-6 flex items-center justify-between">
                    {heading && <h2 className="text-xl font-semibold">{heading}</h2>}
                    {more_url && (
                        <Button variant="ghost" asChild>
                            <Link href={more_url}>{t('View all')}</Link>
                        </Button>
                    )}
                </div>
            )}
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>
        </section>
    );
}
