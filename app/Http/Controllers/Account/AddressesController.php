<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\PostalAddress;
use PnShop\Localization\Models\Country;

class AddressesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('account/addresses', [
            'addresses' => $request->user()->addresses->map(fn (CustomerAddress $address) => [
                'id' => $address->id,
                ...$address->only(PostalAddress::FIELDS),
                'lines' => $address->toPostalAddress()->lines(),
                'is_default_shipping' => $address->is_default_shipping,
                'is_default_billing' => $address->is_default_billing,
            ]),
            'countries' => self::countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(PostalAddress::rules());
        $address = $request->user()->addresses()->create($data);

        if ($request->user()->addresses()->count() === 1) {
            $this->makeDefault($address, 'both');
        }

        return back()->with('success', __('Address saved.'));
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $address->update($request->validate(PostalAddress::rules()));

        return back()->with('success', __('Address saved.'));
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $address->delete();

        return back()->with('success', __('Address deleted.'));
    }

    public function makeDefaultFor(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwner($request, $address);

        $this->makeDefault($address, $request->validate(['for' => ['required', 'in:shipping,billing,both']])['for']);

        return back()->with('success', __('Default address updated.'));
    }

    /**
     * Active countries for address forms, named in the current language.
     *
     * @return list<array{code: string, name: string}>
     */
    public static function countryOptions(): array
    {
        return Country::query()->where('is_active', true)->get()
            ->map(fn (Country $country) => ['code' => $country->code, 'name' => $country->name()])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function makeDefault(CustomerAddress $address, string $for): void
    {
        DB::transaction(function () use ($address, $for) {
            foreach (['shipping', 'billing'] as $kind) {
                if ($for === $kind || $for === 'both') {
                    CustomerAddress::query()->where('user_id', $address->user_id)->update(["is_default_{$kind}" => false]);
                    $address->forceFill(["is_default_{$kind}" => true])->save();
                }
            }
        });
    }

    private function authorizeOwner(Request $request, CustomerAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }
}
