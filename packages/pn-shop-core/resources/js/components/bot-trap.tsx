import InputError from '@/components/input-error';
import { useTranslations } from '@/hooks/use-translations';

/** Fields issued by the server (PnShop\Security\BotTrap) for a protected form. */
export type BotTrapData = { contact_website: string; form_started: string };

/**
 * The honeypot input (hidden from people and assistive technology, filled by bots) and
 * the error shown when a submission is refused as automated.
 */
export function BotTrapFields({ value, onChange, error }: { value: string; onChange: (value: string) => void; error?: string }) {
    const t = useTranslations();

    return (
        <>
            <div aria-hidden="true" className="absolute -left-[9999px] h-px w-px overflow-hidden">
                <label htmlFor="contact_website">{t('Leave this field empty')}</label>
                <input
                    id="contact_website"
                    name="contact_website"
                    type="text"
                    tabIndex={-1}
                    autoComplete="off"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                />
            </div>
            <InputError message={error} />
        </>
    );
}
