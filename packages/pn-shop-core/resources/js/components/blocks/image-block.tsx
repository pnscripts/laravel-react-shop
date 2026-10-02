import { type ProductImage } from '@/types';
import { Link } from '@inertiajs/react';

export function ImageBlock({ image, caption, url }: { image: ProductImage; caption: string | null; url: string | null }) {
    const img = (
        <img
            src={image.url}
            srcSet={image.srcset}
            sizes="(min-width: 1024px) 1024px, 100vw"
            alt={image.alt}
            width={image.width ?? undefined}
            height={image.height ?? undefined}
            loading="lazy"
            className="h-auto w-full rounded-xl"
        />
    );

    return (
        <figure>
            {url ? (
                /^https?:\/\//i.test(url) ? (
                    <a href={url} target="_blank" rel="noopener noreferrer">
                        {img}
                    </a>
                ) : (
                    <Link href={url}>{img}</Link>
                )
            ) : (
                img
            )}
            {caption && <figcaption className="text-muted-foreground mt-2 text-center text-sm">{caption}</figcaption>}
        </figure>
    );
}
