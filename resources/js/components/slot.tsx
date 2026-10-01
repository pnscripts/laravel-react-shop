import { slotComponents, useExtensionRegistry } from '@/lib/extensions';
import { Component, type ReactNode } from 'react';

/** Keeps a broken plugin component from breaking the page. */
class SlotBoundary extends Component<{ name: string; children: ReactNode }, { failed: boolean }> {
    state = { failed: false };

    static getDerivedStateFromError() {
        return { failed: true };
    }

    componentDidCatch(error: unknown) {
        console.error(`A plugin component in the "${this.props.name}" slot failed.`, error);
    }

    render() {
        return this.state.failed ? null : this.props.children;
    }
}

/**
 * A named place where plugins add UI (window.PnShop.registerSlot). Renders nothing until
 * a plugin fills it. Props are the page data the slot documents.
 */
export function Slot({ name, props = {} }: { name: string; props?: Record<string, unknown> }) {
    useExtensionRegistry();

    const components = slotComponents(name);

    if (components.length === 0) {
        return null;
    }

    return (
        <div data-slot={name} className="contents">
            {components.map((SlotComponent, index) => (
                <SlotBoundary key={index} name={name}>
                    <SlotComponent {...props} />
                </SlotBoundary>
            ))}
        </div>
    );
}
