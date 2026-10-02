import { AddressFields, emptyAddress, type AddressData, type Country } from '@/components/address-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Addresses', href: '/account/addresses' }];

type SavedAddress = AddressData & { id: number; lines: string[]; is_default_shipping: boolean; is_default_billing: boolean };

export default function AccountAddresses({ addresses, countries }: { addresses: SavedAddress[]; countries: Country[] }) {
    const t = useTranslations();
    const [editing, setEditing] = useState<number | 'new' | null>(addresses.length === 0 ? 'new' : null);
    const form = useForm<AddressData>(emptyAddress());

    const startEditing = (address: SavedAddress | null) => {
        form.clearErrors();
        form.setData(
            address
                ? (Object.fromEntries(Object.keys(emptyAddress()).map((key) => [key, address[key as keyof AddressData] ?? ''])) as AddressData)
                : emptyAddress(),
        );
        setEditing(address ? address.id : 'new');
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setEditing(null) };

        if (editing === 'new') {
            form.post(route('account.addresses.store'), options);
        } else if (editing !== null) {
            form.put(route('account.addresses.update', editing), options);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Addresses')} />
            <div className="grid gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{t('Addresses')}</h1>
                    {editing === null && <Button onClick={() => startEditing(null)}>{t('Add address')}</Button>}
                </div>

                {editing !== null && (
                    <Card>
                        <form onSubmit={submit}>
                            <CardContent className="pt-6">
                                <AddressFields value={form.data} onChange={(next) => form.setData(next)} countries={countries} errors={form.errors} />
                            </CardContent>
                            <CardFooter className="mt-4 gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    {t('Save address')}
                                </Button>
                                {addresses.length > 0 && (
                                    <Button type="button" variant="ghost" onClick={() => setEditing(null)}>
                                        {t('Cancel')}
                                    </Button>
                                )}
                            </CardFooter>
                        </form>
                    </Card>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    {addresses.map((address) => (
                        <Card key={address.id}>
                            <CardContent className="pt-6 text-sm">
                                <div className="mb-3 flex flex-wrap gap-2">
                                    {address.is_default_shipping && <Badge>{t('Default shipping')}</Badge>}
                                    {address.is_default_billing && <Badge variant="secondary">{t('Default billing')}</Badge>}
                                </div>
                                <address className="not-italic">
                                    {address.lines.map((line) => (
                                        <div key={line}>{line}</div>
                                    ))}
                                </address>
                            </CardContent>
                            <CardFooter className="flex flex-wrap gap-2">
                                <Button size="sm" variant="outline" onClick={() => startEditing(address)}>
                                    {t('Edit')}
                                </Button>
                                {!(address.is_default_shipping && address.is_default_billing) && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            router.post(route('account.addresses.default', address.id), { for: 'both' }, { preserveScroll: true })
                                        }
                                    >
                                        {t('Make default')}
                                    </Button>
                                )}
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => router.delete(route('account.addresses.destroy', address.id), { preserveScroll: true })}
                                >
                                    {t('Delete')}
                                </Button>
                            </CardFooter>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
