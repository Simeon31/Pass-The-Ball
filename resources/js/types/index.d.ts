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

export type GroupRole = 'admin' | 'moderator' | 'member';

export type GroupPermission =
    | 'post_in_group'
    | 'invite_members'
    | 'edit_group_settings'
    | 'edit_group_images'
    | 'approve_join_requests'
    | 'remove_members'
    | 'moderate_posts'
    | 'delete_group';

export interface Group {
    id: number;
    name: string;
    slug: string;
    about?: string | null;
    cover_url?: string | null;
    thumbnail_url?: string | null;
    auto_approval: boolean;
    owner?: User;
    member_count?: number;
    is_member: boolean;
    is_owner: boolean;
    user_role?: GroupRole;
    permissions?: GroupPermission[];
    created_at: string;
    updated_at: string;
}

export interface GroupMember {
    id: number;
    user: User;
    role: GroupRole;
    status: 'pending' | 'approved' | 'rejected';
    joined_at: string;
}

export interface GroupInvitation {
    id: number;
    group?: Group;
    user?: User;
    inviter?: User;
    token?: string;
    status: 'pending' | 'accepted' | 'rejected' | 'expired';
    is_valid: boolean;
    is_expired: boolean;
    expires_at: string;
    created_at: string;
}

export interface PostAttachment {
    id: number;
    name: string;
    mime_type: string;
    size: number;
    url: string;
    created_at: string;
}

export type ReactionType = 'like' | 'love' | 'haha' | 'wow' | 'sad' | 'angry';

export interface ReactionSummary {
    [key: string]: number;
}

export interface PostReactions {
    summary: ReactionSummary;
    total: number;
    current_user_reaction: ReactionType | null;
}

export interface Comment {
    id: number;
    post_id: number;
    parent_id: number | null;
    comment: string;
    user: User;
    created_at: string;
    updated_at: string;
    reactions: PostReactions;
    depth: number;
    replies?: Comment[];
    replies_count?: number;
    has_more_replies?: boolean;
}

export interface PostComments {
    data: Comment[];
    total: number;
}

export interface Post {
    id: number;
    body: string | null;
    user: User;
    created_at: string;
    updated_at: string;
    group?: Group;
    attachments: PostAttachment[];
    reactions: PostReactions;
    comments: PostComments;
}

export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

export type BreadcrumbItemType = BreadcrumbItem;
