import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { type ProductCard as ProductCardType } from '@/types';
import { Link } from '@inertiajs/react';

export function ProductCard({ product }: { product: ProductCardType }) {
    const hasDiscount = product.discount_price !== null && product.discount_price.minor > 0;

    return (
        <Card className="overflow-hidden py-0">
            <Link href={route('shop.show', product.slug)} className="block">
                <div className="bg-muted aspect-square overflow-hidden">
                    {product.image ? (
                        <img src={product.image} alt={product.title} className="size-full object-cover" />
                    ) : (
                        <div className="text-muted-foreground flex size-full items-center justify-center text-sm">No image</div>
                    )}
                </div>
                <CardHeader className="gap-2 px-4 pt-4">
                    {product.category && (
                        <Badge variant="secondary" className="w-fit">
                            {product.category.title}
                        </Badge>
                    )}
                    <CardTitle className="line-clamp-2 text-base">{product.title}</CardTitle>
                </CardHeader>
                <CardContent className="px-4">
                    <div className="flex items-baseline gap-2">
                        <span className="font-semibold">{(hasDiscount ? product.discount_price : product.price)?.formatted}</span>
                        {hasDiscount && <span className="text-muted-foreground text-sm line-through">{product.price.formatted}</span>}
                    </div>
                </CardContent>
                <CardFooter className="px-4 pb-4">
                    <span className="text-muted-foreground text-sm">{product.stock > 0 ? `${product.stock} in stock` : 'Out of stock'}</span>
                </CardFooter>
            </Link>
        </Card>
    );
}
