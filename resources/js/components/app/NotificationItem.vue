<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { Notification } from '@/types';
import {
    Bell,
    Users,
    MessageCircle,
    Heart,
    UserPlus,
    Trash2,
    Check,
} from 'lucide-vue-next';

interface Props {
    notification: Notification;
}

const props = defineProps<Props>();

const isUnread = computed(() => !props.notification.read_at);

const categoryIcon = computed(() => {
    switch (props.notification.category) {
        case 'invitation':
            return Users;
        case 'join_request':
            return UserPlus;
        case 'comment':
            return MessageCircle;
        case 'reaction':
            return Heart;
        case 'follow':
            return UserPlus;
        default:
            return Bell;
    }
});

const categoryColor = computed(() => {
    switch (props.notification.category) {
        case 'invitation':
            return 'text-blue-500';
        case 'join_request':
            return 'text-green-500';
        case 'comment':
            return 'text-purple-500';
        case 'reaction':
            return 'text-pink-500';
        case 'follow':
            return 'text-indigo-500';
        default:
            return 'text-gray-500';
    }
});

const handleMarkAsRead = () => {
    router.post(
        `/notifications/${props.notification.id}/read`,
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleDelete = () => {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }

    router.delete(`/notifications/${props.notification.id}`, {
        preserveScroll: true,
    });
};

const handleClick = () => {
    const actionUrl = props.notification.data.action_url;
    if (!actionUrl) return;

    // Mark as read if unread, then navigate
    if (isUnread.value) {
        router.post(
            `/notifications/${props.notification.id}/read`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Navigate after marking as read
                    router.visit(actionUrl);
                },
            },
        );
    } else {
        // Already read, just navigate
        router.visit(actionUrl);
    }
};
</script>

<template>
    <Card :class="[
        'p-4 transition-all hover:shadow-md',
        isUnread ? 'bg-primary/5 border-primary/20' : 'bg-background',
    ]">
        <div class="flex gap-4">
            <!-- Icon -->
            <div :class="[
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                isUnread ? 'bg-primary/10' : 'bg-muted',
            ]">
                <component :is="categoryIcon" :class="['h-5 w-5', categoryColor]" />
            </div>

            <!-- Content -->
            <div class="flex-1 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                        <p class="text-sm" :class="isUnread ? 'font-semibold' : 'font-normal'">
                            {{ notification.data.message }}
                        </p>
                        <!-- Description (if available) -->
                        <p v-if="notification.data.description" class="text-muted-foreground mt-2 text-sm">
                            {{ notification.data.description }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">
                            {{ notification.time_ago }}
                        </p>
                    </div>

                    <!-- Unread Badge -->
                    <Badge v-if="isUnread" variant="default" class="shrink-0">
                        New
                    </Badge>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <Button v-if="notification.data.action_url && notification.data.action_url !== '/notifications'"
                        size="sm" variant="default" @click="handleClick">
                        View
                    </Button>
                    <Button v-if="isUnread" size="sm" variant="outline" @click="handleMarkAsRead">
                        <Check class="mr-2 h-3 w-3" />
                        Mark as read
                    </Button>
                    <Button size="sm" variant="ghost" @click="handleDelete"
                        class="text-muted-foreground hover:text-destructive">
                        <Trash2 class="h-3 w-3" />
                    </Button>
                </div>
            </div>
        </div>
    </Card>
</template>
