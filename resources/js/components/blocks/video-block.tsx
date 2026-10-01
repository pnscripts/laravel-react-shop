export function VideoBlock({ embed, title }: { embed: string; title: string }) {
    return (
        <div className="aspect-video w-full overflow-hidden rounded-xl border">
            <iframe
                src={embed}
                title={title}
                className="h-full w-full"
                loading="lazy"
                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                referrerPolicy="strict-origin-when-cross-origin"
                allowFullScreen
            />
        </div>
    );
}
