import { type MenuItem } from '@/types';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

/** A menu entry: client-side link inside the shop, a plain link elsewhere, text when it has no link. */
export function MenuLink({ item, className, children }: { item: MenuItem; className?: string; children?: ReactNode }) {
    const content = children ?? item.label;

    if (item.url === null) {
        return <span className={className}>{content}</span>;
    }

    if (item.new_tab || !item.url.startsWith('/')) {
        return (
            <a
                href={item.url}
                className={className}
                target={item.new_tab ? '_blank' : undefined}
                rel={item.new_tab ? 'noopener noreferrer' : undefined}
            >
                {content}
            </a>
        );
    }

    return (
        <Link href={item.url} className={className}>
            {content}
        </Link>
    );
}
