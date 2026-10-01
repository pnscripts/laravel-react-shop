import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/utils';
import { type CartSummary } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type PaymentMethod = {
    id: number;
    name: string;
    description: string | null;
    type: string;
};

export default function Checkout({
    cart,
    paymentMethods,
    defaults,
}: {
    cart: CartSummary;
    paymentMethods: PaymentMethod[];
    defaults: { name: string; email: string };
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: defaults.name,
        email: defaults.email,
        phone: '',
        address: '',
        payment_method_id: paymentMethods[0]?.id ? String(paymentMethods[0].id) : '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('checkout.store'));
    };

    return (
        <StorefrontLayout>
            <Head title="Checkout" />
            <h1 className="mb-6 text-3xl font-semibold tracking-tight">Checkout</h1>

            <form onSubmit={submit} className="grid gap-8 lg:grid-cols-3">
                <div className="grid gap-4 lg:col-span-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} required />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} required />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="phone">Phone</Label>
                        <Input id="phone" value={data.phone} onChange={(event) => setData('phone', event.target.value)} required />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="address">Address</Label>
                        <Textarea id="address" value={data.address} onChange={(event) => setData('address', event.target.value)} required />
                        <InputError message={errors.address} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment_method_id">Payment method</Label>
                        <select
                            id="payment_method_id"
                            className="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                            value={data.payment_method_id}
                            onChange={(event) => setData('payment_method_id', event.target.value)}
                            required
                        >
                            {paymentMethods.map((method) => (
                                <option key={method.id} value={method.id}>
                                    {method.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.payment_method_id} />
                        {paymentMethods.find((method) => String(method.id) === String(data.payment_method_id))?.description && (
                            <p className="text-muted-foreground text-sm">
                                {paymentMethods.find((method) => String(method.id) === String(data.payment_method_id))?.description}
                            </p>
                        )}
                    </div>
                </div>

                <aside className="h-fit rounded-xl border p-6">
                    <h2 className="mb-4 font-semibold">Order summary</h2>
                    <ul className="mb-4 space-y-2 text-sm">
                        {cart.items.map((item) => (
                            <li key={item.product_id} className="flex justify-between gap-4">
                                <span>
                                    {item.title} × {item.quantity}
                                </span>
                                <span>{formatMoney(item.line_total)}</span>
                            </li>
                        ))}
                    </ul>
                    <div className="mb-6 flex justify-between font-semibold">
                        <span>Total</span>
                        <span>{formatMoney(cart.final_price)}</span>
                    </div>
                    <Button type="submit" className="w-full" disabled={processing}>
                        Place order
                    </Button>
                    <Button variant="ghost" className="mt-2 w-full" asChild>
                        <Link href={route('cart.index')}>Back to cart</Link>
                    </Button>
                    <p className="text-muted-foreground mt-4 text-xs">
                        No card payments. This starter only records cash on delivery or bank transfer.
                    </p>
                </aside>
            </form>
        </StorefrontLayout>
    );
}
