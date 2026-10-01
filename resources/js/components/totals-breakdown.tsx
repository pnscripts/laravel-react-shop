import { useTranslations } from '@/hooks/use-translations';
import { type Totals } from '@/types';

/**
 * Subtotal, the lines added by the cart.totals pipeline (shipping, discounts, tax, ...) and the total.
 */
export function TotalsBreakdown({ totals }: { totals: Totals }) {
    const t = useTranslations();

    return (
        <dl className="mb-6 space-y-2 text-sm">
            <div className="flex justify-between">
                <dt>{t('Subtotal')}</dt>
                <dd>{totals.subtotal.formatted}</dd>
            </div>
            {totals.lines.map((line) => (
                <div key={line.code} className="text-muted-foreground flex justify-between">
                    <dt>{line.included ? t(':label (included)', { label: line.label }) : line.label}</dt>
                    <dd>{line.amount.formatted}</dd>
                </div>
            ))}
            <div className="flex justify-between border-t pt-2 text-base font-semibold">
                <dt>{t('Total')}</dt>
                <dd>{totals.total.formatted}</dd>
            </div>
        </dl>
    );
}
