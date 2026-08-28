import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { type ProductCard as ProductCardType } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Home({ products }: { products: ProductCardType[] }) {
    return (
        <StorefrontLayout>
            <Head title="Shop" />
            <section className="mb-10 rounded-xl border px-6 py-12 md:px-10">
                <h1 className="mb-3 text-3xl font-semibold tracking-tight">A simple Laravel + React shop</h1>
                <p className="text-muted-foreground mb-6 max-w-2xl">
                    Browse active products, add them to your cart, and check out as a guest or a signed-in customer. This starter ships without a
                    payment gateway — cash on delivery and bank transfer only.
                </p>
                <Button asChild>
                    <Link href={route('shop.index')}>Browse the catalog</Link>
                </Button>
            </section>

            <section>
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Featured products</h2>
                    <Button variant="ghost" asChild>
                        <Link href={route('shop.index')}>View all</Link>
                    </Button>
                </div>
                {products.length === 0 ? (
                    <p className="text-muted-foreground">No products are available yet. Seed the database or add some in admin.</p>
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
