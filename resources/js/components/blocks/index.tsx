import { type ContentBlock } from '@/types';
import { type ComponentType } from 'react';
import { CallToActionBlock } from './call-to-action-block';
import { CategoryGridBlock } from './category-grid-block';
import { HeroBlock } from './hero-block';
import { HtmlBlock } from './html-block';
import { ImageBlock } from './image-block';
import { ProductGridBlock } from './product-grid-block';
import { RichTextBlock } from './rich-text-block';
import { VideoBlock } from './video-block';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type BlockComponent = ComponentType<any>;

/**
 * React components for block types (PnShop\Cms\Blocks\BlockRegistry keys). Themes and
 * extensions add theirs with registerBlock(); unknown types render nothing.
 */
const components: Record<string, BlockComponent> = {
    hero: HeroBlock,
    rich_text: RichTextBlock,
    image: ImageBlock,
    product_grid: ProductGridBlock,
    category_grid: CategoryGridBlock,
    call_to_action: CallToActionBlock,
    video: VideoBlock,
    html: HtmlBlock,
};

export function registerBlock(type: string, component: BlockComponent): void {
    components[type] = component;
}

export function Blocks({ blocks }: { blocks: ContentBlock[] }) {
    return (
        <div className="space-y-12">
            {blocks.map((block, index) => {
                const Component = components[block.type];

                return Component ? <Component key={`${block.type}-${index}`} {...block.props} /> : null;
            })}
        </div>
    );
}
