import { blockComponent, registerBlock, useExtensionRegistry } from '@/lib/extensions';
import { type ContentBlock } from '@/types';
import { CallToActionBlock } from './call-to-action-block';
import { CategoryGridBlock } from './category-grid-block';
import { HeroBlock } from './hero-block';
import { HtmlBlock } from './html-block';
import { ImageBlock } from './image-block';
import { ProductGridBlock } from './product-grid-block';
import { RichTextBlock } from './rich-text-block';
import { VideoBlock } from './video-block';

// The core block renderers; themes and plugins add theirs with registerBlock().
const core = {
    hero: HeroBlock,
    rich_text: RichTextBlock,
    image: ImageBlock,
    product_grid: ProductGridBlock,
    category_grid: CategoryGridBlock,
    call_to_action: CallToActionBlock,
    video: VideoBlock,
    html: HtmlBlock,
};

Object.entries(core).forEach(([type, component]) => registerBlock(type, component));

export { registerBlock };

export function Blocks({ blocks }: { blocks: ContentBlock[] }) {
    // Plugin renderers may register after the first render.
    useExtensionRegistry();

    return (
        <div className="space-y-12">
            {blocks.map((block, index) => {
                const Component = blockComponent(block.type);

                return Component ? <Component key={`${block.type}-${index}`} {...block.props} /> : null;
            })}
        </div>
    );
}
