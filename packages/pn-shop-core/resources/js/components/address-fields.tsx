import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';

export type AddressData = {
    first_name: string;
    last_name: string;
    company: string;
    line1: string;
    line2: string;
    city: string;
    postcode: string;
    region: string;
    country_code: string;
    phone: string;
};

export type Country = { code: string; name: string };

export const emptyAddress = (countryCode = ''): AddressData => ({
    first_name: '',
    last_name: '',
    company: '',
    line1: '',
    line2: '',
    city: '',
    postcode: '',
    region: '',
    country_code: countryCode,
    phone: '',
});

/**
 * Postal address inputs. `errors` are keyed like the fields, optionally under `prefix` ("shipping.").
 */
export function AddressFields({
    value,
    onChange,
    countries,
    errors = {},
    prefix = '',
    idPrefix = 'address',
    phoneRequired = false,
}: {
    value: AddressData;
    onChange: (next: AddressData) => void;
    countries: Country[];
    errors?: Record<string, string | undefined>;
    prefix?: string;
    idPrefix?: string;
    phoneRequired?: boolean;
}) {
    const t = useTranslations();
    const field = (name: keyof AddressData, label: string, options: { required?: boolean; autoComplete?: string; className?: string } = {}) => (
        <div className={`grid gap-2 ${options.className ?? ''}`}>
            <Label htmlFor={`${idPrefix}-${name}`}>{t(label)}</Label>
            <Input
                id={`${idPrefix}-${name}`}
                value={value[name]}
                required={options.required}
                autoComplete={options.autoComplete}
                onChange={(event) => onChange({ ...value, [name]: event.target.value })}
            />
            <InputError message={errors[`${prefix}${name}`]} />
        </div>
    );

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {field('first_name', 'First name', { required: true, autoComplete: 'given-name' })}
            {field('last_name', 'Last name', { required: true, autoComplete: 'family-name' })}
            {field('company', 'Company (optional)', { autoComplete: 'organization', className: 'sm:col-span-2' })}
            {field('line1', 'Street address', { required: true, autoComplete: 'address-line1', className: 'sm:col-span-2' })}
            {field('line2', 'Apartment, floor, etc. (optional)', { autoComplete: 'address-line2', className: 'sm:col-span-2' })}
            {field('city', 'City', { required: true, autoComplete: 'address-level2' })}
            {field('postcode', 'Postcode', { autoComplete: 'postal-code' })}
            {field('region', 'Region (optional)', { autoComplete: 'address-level1' })}
            <div className="grid gap-2">
                <Label htmlFor={`${idPrefix}-country_code`}>{t('Country')}</Label>
                <select
                    id={`${idPrefix}-country_code`}
                    className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                    value={value.country_code}
                    required
                    autoComplete="country"
                    onChange={(event) => onChange({ ...value, country_code: event.target.value })}
                >
                    <option value="">{t('Choose a country')}</option>
                    {countries.map((country) => (
                        <option key={country.code} value={country.code}>
                            {country.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors[`${prefix}country_code`]} />
            </div>
            {field('phone', 'Phone', { required: phoneRequired, autoComplete: 'tel', className: 'sm:col-span-2' })}
        </div>
    );
}
