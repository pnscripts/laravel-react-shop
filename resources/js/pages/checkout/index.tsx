import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/hooks/use-translations';
import StorefrontLayout from '@/layouts/storefront-layout';
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
    const t = useTranslations();
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
            <Head title={t('Checkout')} />
            <h1 className="mb-6 text-3xl font-semibold tracking-tight">{t('Checkout')}</h1>

            <form onSubmit={submit} className="grid gap-8 lg:grid-cols-3">
                <div className="grid gap-4 lg:col-span-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('Name')}</Label>
                        <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} required />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input id="email" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} required />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="phone">{t('Phone')}</Label>
                        <Input id="phone" value={data.phone} onChange={(event) => setData('phone', event.target.value)} required />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="address">{t('Address')}</Label>
                        <Textarea id="address" value={data.address} onChange={(event) => setData('address', event.target.value)} required />
                        <InputError message={errors.address} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment_method_id">{t('Payment method')}</Label>
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
                    <h2 className="mb-4 font-semibold">{t('Order summary')}</h2>
                    <ul className="mb-4 space-y-2 text-sm">
                        {cart.items.map((item) => (
                            <li key={item.product_id} className="flex justify-between gap-4">
                                <span>
                                    {item.title} × {item.quantity}
                                </span>
                                <span>{item.line_total.formatted}</span>
                            </li>
                        ))}
                    </ul>
                    <div className="mb-6 flex justify-between font-semibold">
                        <span>{t('Total')}</span>
                        <span>{cart.final_price.formatted}</span>
                    </div>
                    <Button type="submit" className="w-full" disabled={processing}>
                        {t('Place order')}
                    </Button>
                    <Button variant="ghost" className="mt-2 w-full" asChild>
                        <Link href={route('cart.index')}>{t('Back to cart')}</Link>
                    </Button>
                    <p className="text-muted-foreground mt-4 text-xs">
                        {t('No card payments. This starter only records cash on delivery or bank transfer.')}
                    </p>
                </aside>
            </form>
        </StorefrontLayout>
    );
}
