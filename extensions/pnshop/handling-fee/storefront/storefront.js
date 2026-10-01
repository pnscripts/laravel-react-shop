// Storefront code of the handling fee plugin. A plain ES module that uses the shared
// React and Inertia from window.PnShop, so it needs no build step; bigger plugins build
// theirs with any bundler, keeping react and @inertiajs/react external.
const sdk = window.PnShop;

function HandlingFeeHint() {
    const { handlingFee } = sdk.inertia.usePage().props;
    const t = sdk.useTranslations();

    if (!handlingFee || !handlingFee.missing) {
        return null;
    }

    return sdk.React.createElement(
        'p',
        { className: 'text-muted-foreground -mt-3 mb-4 text-xs', 'data-plugin': 'pnshop/handling-fee' },
        t('Add :amount more to avoid the handling fee.', { amount: handlingFee.missing.formatted }),
    );
}

sdk.registerSlot('cart.after_totals', HandlingFeeHint);
