import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import { type CartCoupon } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

/**
 * Enter or remove the cart's coupon code. A saved code that does not apply yet shows why.
 */
export function CouponForm({ coupon }: { coupon: CartCoupon | null }) {
    const t = useTranslations();
    const form = useForm({ code: '' });

    if (coupon) {
        return (
            <div className="mb-4 rounded-lg border border-dashed px-3 py-2 text-sm" data-testid="coupon">
                <div className="flex items-center justify-between gap-2">
                    <span>
                        {t('Coupon')}: <span className="font-mono font-medium">{coupon.code}</span>
                    </span>
                    <Button variant="ghost" size="sm" onClick={() => router.delete(route('cart.coupon.destroy'), { preserveScroll: true })}>
                        {t('Remove')}
                    </Button>
                </div>
                {!coupon.applied && coupon.message && <p className="text-muted-foreground mt-1 text-xs">{coupon.message}</p>}
            </div>
        );
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('cart.coupon.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <form onSubmit={submit} className="mb-4">
            <div className="flex gap-2">
                <Input
                    value={form.data.code}
                    onChange={(event) => form.setData('code', event.target.value)}
                    placeholder={t('Coupon code')}
                    aria-label={t('Coupon code')}
                    autoComplete="off"
                    maxLength={64}
                />
                <Button type="submit" variant="outline" disabled={form.processing || form.data.code.trim() === ''}>
                    {t('Apply')}
                </Button>
            </div>
            <InputError message={form.errors.code} className="mt-1" />
        </form>
    );
}
