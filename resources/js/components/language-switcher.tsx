import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';

/**
 * Links to the current page in every active language. Plain links (full page loads),
 * because the language changes the route prefix the page was rendered with.
 */
export default function LanguageSwitcher() {
    const { localization } = usePage<SharedData>().props;

    if (localization.languages.length < 2) {
        return null;
    }

    const current = localization.languages.find((language) => language.active);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-9 gap-1 px-2 uppercase">
                    <Languages className="h-4 w-4" />
                    {current?.code}
                    <span className="sr-only">{current?.name}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {localization.languages.map((language) => (
                    <DropdownMenuItem key={language.code} asChild>
                        <a href={language.url} hrefLang={language.code} lang={language.code} aria-current={language.active ? 'page' : undefined}>
                            {language.name}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
