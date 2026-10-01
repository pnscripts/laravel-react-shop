import { Link } from '@inertiajs/react';

type Category = { id: number; title: string; url: string };

export function CategoryGridBlock({ heading, categories }: { heading: string | null; categories: Category[] }) {
    return (
        <section>
            {heading && <h2 className="mb-6 text-xl font-semibold">{heading}</h2>}
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {categories.map((category) => (
                    <Link
                        key={category.id}
                        href={category.url}
                        className="hover:bg-muted/50 rounded-xl border p-6 text-lg font-medium transition-colors"
                    >
                        {category.title}
                    </Link>
                ))}
            </div>
        </section>
    );
}
