import { Button } from '@/components/ui/button';
import { type Paginated } from '@/types';
import { Link } from '@inertiajs/react';

export function PaginationLinks<T>({ paginator }: { paginator: Paginated<T> }) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1">
            {paginator.links.map((link, index) => {
                const label = link.label.replace('&laquo;', '«').replace('&raquo;', '»');

                if (!link.url) {
                    return (
                        <Button key={`${label}-${index}`} variant="ghost" size="sm" disabled>
                            {label}
                        </Button>
                    );
                }

                return (
                    <Button key={`${label}-${index}`} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                        <Link href={link.url} preserveScroll>
                            {label}
                        </Link>
                    </Button>
                );
            })}
        </nav>
    );
}
