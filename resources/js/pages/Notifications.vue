<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import NotificationItem from '@/components/app/NotificationItem.vue';
import { useFlashMessage } from '@/composables/useFlashMessage';
import type {
    AppPageProps,
    Notification,
    NotificationCounts,
    PaginatedData,
} from '@/types';
import { CheckCheck, Trash2 } from 'lucide-vue-next';

interface Props {
    notifications: PaginatedData<Notification>;
    counts: NotificationCounts;
    currentFilter: 'all' | 'unread' | 'invitations' | 'posts' | 'groups';
}

const props = defineProps<Props>();

const { showMessage, message, dismiss } = useFlashMessage('status', 5000);

const hasNotifications = computed(
    () => props.notifications.data.length > 0,
);

const handleMarkAllAsRead = () => {
    router.post(
        '/notifications/mark-all-read',
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Notification will be shown via flash message
            },
        },
    );
};

const handleDeleteRead = () => {
    if (
        !confirm(
            'Are you sure you want to delete all read notifications? This action cannot be undone.',
        )
    ) {
        return;
    }

    router.delete('/notifications/delete-read', {
        preserveScroll: true,
        onSuccess: () => {
            // Notification will be shown via flash message
        },
    });
};

const handleFilterChange = (filter: string) => {
    router.get(
        '/notifications',
        { filter },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const loadMore = () => {
    if (props.notifications.links.next) {
        router.get(
            props.notifications.links.next,
            {},
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }
};

const isActiveFilter = (filter: string) => props.currentFilter === filter;
</script>

<template>
    <AppSidebarLayout>
        <div class="container mx-auto max-w-4xl px-4 py-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Notifications</h1>
                    <p class="text-muted-foreground mt-1">
                        Stay updated with your activity
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button v-if="counts.unread > 0" variant="outline" size="sm" @click="handleMarkAllAsRead">
                        <CheckCheck class="mr-2 h-4 w-4" />
                        Mark all as read
                    </Button>
                    <Button variant="outline" size="sm" @click="handleDeleteRead">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Clear read
                    </Button>
                </div>
            </div>

            <!-- Flash Message -->
            <div v-if="showMessage" class="bg-primary text-primary-foreground mb-4 rounded-lg p-4 shadow-md">
                <div class="flex items-center justify-between">
                    <p>{{ message }}</p>
                    <Button variant="ghost" size="sm" @click="dismiss"
                        class="text-primary-foreground hover:bg-primary/80">
                        ×
                    </Button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="mb-6 flex gap-2 overflow-x-auto border-b pb-2">
                <Button :variant="isActiveFilter('all') ? 'default' : 'ghost'" size="sm"
                    @click="handleFilterChange('all')" class="shrink-0">
                    All
                    <Badge :variant="isActiveFilter('all') ? 'secondary' : 'outline'
                        " class="ml-2">
                        {{ counts.all }}
                    </Badge>
                </Button>
                <Button :variant="isActiveFilter('unread') ? 'default' : 'ghost'" size="sm"
                    @click="handleFilterChange('unread')" class="shrink-0">
                    Unread
                    <Badge v-if="counts.unread > 0" :variant="isActiveFilter('unread')
                            ? 'secondary'
                            : 'destructive'
                        " class="ml-2">
                        {{ counts.unread }}
                    </Badge>
                </Button>
                <Button :variant="isActiveFilter('invitations') ? 'default' : 'ghost'
                    " size="sm" @click="handleFilterChange('invitations')" class="shrink-0">
                    Invitations
                    <Badge v-if="counts.invitations > 0" :variant="isActiveFilter('invitations')
                            ? 'secondary'
                            : 'outline'
                        " class="ml-2">
                        {{ counts.invitations }}
                    </Badge>
                </Button>
                <Button :variant="isActiveFilter('posts') ? 'default' : 'ghost'" size="sm"
                    @click="handleFilterChange('posts')" class="shrink-0">
                    Posts
                    <Badge v-if="counts.posts > 0" :variant="isActiveFilter('posts') ? 'secondary' : 'outline'
                        " class="ml-2">
                        {{ counts.posts }}
                    </Badge>
                </Button>
                <Button :variant="isActiveFilter('groups') ? 'default' : 'ghost'" size="sm"
                    @click="handleFilterChange('groups')" class="shrink-0">
                    Groups
                    <Badge v-if="counts.groups > 0" :variant="isActiveFilter('groups') ? 'secondary' : 'outline'
                        " class="ml-2">
                        {{ counts.groups }}
                    </Badge>
                </Button>
            </div>

            <!-- Notifications List -->
            <div v-if="hasNotifications" class="space-y-2">
                <NotificationItem v-for="notification in notifications.data" :key="notification.id"
                    :notification="notification" />
            </div>

            <!-- Empty State -->
            <Card v-else class="p-12 text-center">
                <div class="text-muted-foreground mx-auto max-w-md space-y-3">
                    <div class="text-6xl">🔔</div>
                    <h3 class="text-xl font-semibold">No notifications</h3>
                    <p>
                        {{
                            currentFilter === 'unread'
                                ? "You're all caught up! No unread notifications."
                                : currentFilter === 'invitations'
                                    ? 'No group invitations at the moment.'
                                    : currentFilter === 'posts'
                                        ? 'No notifications about posts yet.'
                                        : currentFilter === 'groups'
                                            ? 'No group-related notifications.'
                                            : 'When you get notifications, they will appear here.'
                        }}
                    </p>
                </div>
            </Card>

            <!-- Load More -->
            <div v-if="hasNotifications && notifications.links.next" class="mt-6 text-center">
                <Button variant="outline" @click="loadMore">
                    Load more
                </Button>
            </div>

            <!-- Pagination Info -->
            <div v-if="hasNotifications" class="text-muted-foreground mt-4 text-center text-sm">
                Showing {{ notifications.meta.from }} to
                {{ notifications.meta.to }} of
                {{ notifications.meta.total }} notifications
            </div>
        </div>
    </AppSidebarLayout>
</template>
