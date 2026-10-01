# Tax

Tax lives in `core/Tax`. Staff set it up in Admin → Store → *Tax classes* and *Tax zones*, and in Admin → Settings → Tax.

## Classes, zones and rates

| Concept | Meaning |
|---|---|
| **Tax class** | What kind of goods: *Standard*, *Reduced*, *Zero rate*, … One class is the default. Products and shipping methods without a class use it. |
| **Tax zone** | Where rates apply: countries (none means everywhere) and optional postcode patterns (`*` matches anything). Zones are checked by position; the first match is used. |
| **Tax rate** | A percentage for one class in one zone, with a name shown to customers ("VAT 20%"). |

- **Combining rates:** rates are applied by priority. Rates of the same priority add up; a **compound** rate is charged on the amount including the earlier taxes (e.g. Québec QST on top of GST).
- **Rounding:** tax is rounded per line and rate, half up.
- **No rates, no tax:** a store without rates charges no tax, so stores that upgrade behave as before.

## Settings

| Setting | Effect |
|---|---|
| Prices include tax (default on) | **On:** catalog prices are what customers pay; the tax is extracted and shown as *included* (usual for VAT). **Off:** prices are net and the tax is added at checkout. |
| Calculate tax for | the shipping address (default), the billing address, or the store country |
| Store country | used before the customer has entered an address, so the cart already shows the included VAT, and for "store country" |

## How it is applied

- **Pipeline stage:** the `cart.totals` stage `ApplyTax` (priority 400) taxes every cart line by its product's class and the shipping by the method's class. It adds one total line per rate.
- **Included vs added:** with tax-inclusive prices the lines are marked *included* and don't change the total. Otherwise they are added.
- **Orders:** each order keeps the tax lines in its totals and the tax on each line (`order_items.tax_amount`), so invoices can show them later.
- **Discounts:** the discount stage (Phase 11) runs before tax and is spread over the lines it applies to.

## Replacing the calculation

The rate tables are the default `PnShop\Tax\Contracts\TaxProvider`. An extension that uses a tax service binds its own:

```php
$this->app->bind(TaxProvider::class, AcmeTaxProvider::class);

final class AcmeTaxProvider implements TaxProvider
{
    public function calculate(TaxRequest $request): TaxResult
    {
        $result = new TaxResult($request->currency);

        foreach ($request->lines as $line) {           // key "item:<variant id>" or "shipping"
            $result->add($line->key, 'Sales tax', /* Money from the service */);
        }

        return $result;
    }
}
```
