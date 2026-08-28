import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    cartCount: number;
    flash: {
        success: string | null;
        error: string | null;
    };
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_admin?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface ProductCard {
    id: number;
    title: string;
    slug: string;
    price: string | number;
    discount_price: string | number | null;
    image: string | null;
    stock: number;
    category: {
        id: number;
        title: string;
        slug: string;
    } | null;
}

export interface CartItem {
    product_id: number;
    title: string;
    price: number;
    discount_price: number | null;
    image: string | null;
    stock: number;
    quantity: number;
    line_total: number;
}

export interface CartSummary {
    items: CartItem[];
    total_quantity: number;
    total_price: number;
    final_price: number;
}

export interface Paginated<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}
