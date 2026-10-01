import { type ProductImage } from '@/types';
import { LinkButton } from './link-button';

type Props = {
    heading: string;
    text: string | null;
    image: ProductImage | null;
    button: { label: string; url: string } | null;
    align: 'left' | 'center';
};

export function HeroBlock({ heading, text, image, button, align }: Props) {
    return (
        <section className="relative overflow-hidden rounded-xl border">
            {image && (
                <img
                    src={image.url}
                    srcSet={image.srcset}
                    sizes="100vw"
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                    fetchPriority="high"
                />
            )}
            <div
                className={`relative px-6 py-16 md:px-12 md:py-24 ${image ? 'bg-black/45 text-white' : ''} ${align === 'center' ? 'text-center' : ''}`}
            >
                <h1 className={`mb-3 text-3xl font-semibold tracking-tight md:text-5xl ${align === 'center' ? 'mx-auto' : ''} max-w-3xl`}>
                    {heading}
                </h1>
                {text && (
                    <p
                        className={`mb-6 max-w-2xl text-lg ${align === 'center' ? 'mx-auto' : ''} ${image ? 'text-white/90' : 'text-muted-foreground'}`}
                    >
                        {text}
                    </p>
                )}
                {button && <LinkButton url={button.url} label={button.label} variant={image ? 'secondary' : 'default'} />}
            </div>
        </section>
    );
}
