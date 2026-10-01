import { MenuLink } from '@/components/menu-link';
import { useTranslations } from '@/hooks/use-translations';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/** The footer menu: top-level items with children become columns. */
export function StorefrontFooter() {
    const { menus, name } = usePage<SharedData>().props;
    const t = useTranslations();
    const columns = menus.footer.filter((item) => item.children.length > 0);
    const links = menus.footer.filter((item) => item.children.length === 0);

    return (
        <footer className="border-sidebar-border/80 mt-16 border-t">
            <div className="mx-auto grid gap-8 px-4 py-10 text-sm md:max-w-7xl md:grid-cols-4">
                {columns.map((column) => (
                    <nav key={column.label} aria-label={column.label}>
                        <h2 className="mb-3 font-semibold">
                            <MenuLink item={column} />
                        </h2>
                        <ul className="text-muted-foreground space-y-2">
                            {column.children.map((item) => (
                                <li key={item.label}>
                                    <MenuLink item={item} className="hover:text-foreground" />
                                </li>
                            ))}
                        </ul>
                    </nav>
                ))}
                {links.length > 0 && (
                    <nav aria-label={t('Footer')}>
                        <ul className="text-muted-foreground space-y-2">
                            {links.map((item) => (
                                <li key={item.label}>
                                    <MenuLink item={item} className="hover:text-foreground" />
                                </li>
                            ))}
                        </ul>
                    </nav>
                )}
            </div>
            <p className="text-muted-foreground mx-auto px-4 pb-8 text-xs md:max-w-7xl">
                © {new Date().getFullYear()} {name}
            </p>
        </footer>
    );
}
