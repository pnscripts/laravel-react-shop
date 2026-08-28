import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/utils';
import { type CartSummary } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

export default function CartIndex({ cart }: { cart: CartSummary }) {
    return (
        <StorefrontLayout>
            <Head title="Cart" />
            <h1 className="mb-6 text-3xl font-semibold tracking-tight">Cart</h1>

            {cart.items.length === 0 ? (
                <div className="rounded-xl border px-6 py-10 text-center">
                    <p className="text-muted-foreground mb-4">Your cart is empty.</p>
                    <Button asChild>
                        <Link href={route('shop.index')}>Continue shopping</Link>
                    </Button>
                </div>
            ) : (
                <div className="grid gap-8 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <div className="overflow-hidden rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Product</th>
                                        <th className="px-4 py-3 font-medium">Qty</th>
                                        <th className="px-4 py-3 font-medium">Total</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {cart.items.map((item) => (
                                        <tr key={item.product_id} className="border-t">
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{item.title}</div>
                                                <div className="text-muted-foreground">{formatMoney(item.discount_price || item.price)}</div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    max={item.stock}
                                                    defaultValue={item.quantity}
                                                    className="w-20"
                                                    onBlur={(event) => {
                                                        const quantity = Number(event.target.value);
                                                        if (quantity !== item.quantity && quantity >= 1) {
                                                            router.patch(route('cart.update', item.product_id), { quantity });
                                                        }
                                                    }}
                                                />
                                            </td>
                                            <td className="px-4 py-3">{formatMoney(item.line_total)}</td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => router.delete(route('cart.destroy', item.product_id))}
                                                >
                                                    Remove
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <aside className="h-fit rounded-xl border p-6">
                        <h2 className="mb-4 font-semibold">Summary</h2>
                        <div className="mb-2 flex justify-between text-sm">
                            <span>Items</span>
                            <span>{cart.total_quantity}</span>
                        </div>
                        <div className="mb-6 flex justify-between font-semibold">
                            <span>Total</span>
                            <span>{formatMoney(cart.final_price)}</span>
                        </div>
                        <Button className="w-full" asChild>
                            <Link href={route('checkout.create')}>Checkout</Link>
                        </Button>
                    </aside>
                </div>
            )}
        </StorefrontLayout>
    );
}
