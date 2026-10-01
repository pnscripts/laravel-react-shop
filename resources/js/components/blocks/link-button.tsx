import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

/** A button to a shop page (client-side navigation) or an external site (new tab). */
export function LinkButton({ url, label, variant }: { url: string; label: string; variant?: 'default' | 'outline' | 'secondary' }) {
    const external = /^https?:\/\//i.test(url);

    return (
        <Button asChild variant={variant}>
            {external ? (
                <a href={url} target="_blank" rel="noopener noreferrer">
                    {label}
                </a>
            ) : (
                <Link href={url}>{label}</Link>
            )}
        </Button>
    );
}
