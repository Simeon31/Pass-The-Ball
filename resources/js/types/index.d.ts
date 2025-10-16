import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    cover_url?: string | null;
    profile_picture_url?: string | null;
}

export interface Group {
    id: number;
    name: string;
}

export interface PostAttachment {
    id: number;
    name: string;
    mime_type: string;
    size: number;
    url: string;
    created_at: string;
}

export interface Post {
    id: number;
    body: string | null;
    user: User;
    created_at: string;
    updated_at: string;
    group?: Group;
    attachments: PostAttachment[];
}

export type BreadcrumbItemType = BreadcrumbItem;
