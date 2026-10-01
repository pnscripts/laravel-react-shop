// The PN Shop storefront SDK (window.PnShop), installed by the storefront before plugin scripts run.
const sdk = window.PnShop;

if (!sdk) {
    throw new Error('@pnshop/storefront-sdk: window.PnShop is missing. Load plugin scripts on the PN Shop storefront.');
}

export const registerBlock = sdk.registerBlock;
export const registerSlot = sdk.registerSlot;
export const useTranslations = sdk.useTranslations;
export default sdk;
