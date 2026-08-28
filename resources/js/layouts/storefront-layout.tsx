import { StorefrontHeader } from '@/components/storefront-header';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function StorefrontLayout({ children }: PropsWithChildren) {
    const { flash } = usePage<SharedData>().props;

    return (
        <div className="bg-background min-h-svh">
            <StorefrontHeader />
            {(flash.success || flash.error) && (
                <div className="mx-auto w-full px-4 pt-4 md:max-w-7xl">
                    {flash.success && (
                        <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                            {flash.error}
                        </div>
                    )}
                </div>
            )}
            <main className="mx-auto w-full px-4 py-8 md:max-w-7xl">{children}</main>
        </div>
    );
}
