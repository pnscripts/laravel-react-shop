import { LinkButton } from './link-button';

export function CallToActionBlock({ heading, text, button }: { heading: string; text: string | null; button: { label: string; url: string } }) {
    return (
        <section className="bg-muted/40 flex flex-col items-start justify-between gap-4 rounded-xl border p-8 md:flex-row md:items-center">
            <div>
                <h2 className="text-2xl font-semibold">{heading}</h2>
                {text && <p className="text-muted-foreground mt-2 max-w-2xl">{text}</p>}
            </div>
            <LinkButton url={button.url} label={button.label} />
        </section>
    );
}
