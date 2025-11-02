<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, UserCheck, UserX, Clock } from 'lucide-vue-next';
import type { Group, GroupMember } from '@/types';
import { useFlashMessage } from '@/composables/useFlashMessage';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

interface Props {
    group: Group;
    pendingRequests: GroupMember[];
}

const props = defineProps<Props>();

// Flash message handling
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);

const handleApprove = (userId: number) => {
    if (confirm('Are you sure you want to approve this join request?')) {
        router.post(
            `/groups/${props.group.slug}/admin/approve`,
            {
                user_id: userId,
                action: 'approve',
                role: 'member',
            },
            {
                preserveScroll: true,
            }
        );
    }
};

const handleReject = (userId: number) => {
    if (confirm('Are you sure you want to reject this join request?')) {
        router.post(
            `/groups/${props.group.slug}/admin/approve`,
            {
                user_id: userId,
                action: 'reject',
            },
            {
                preserveScroll: true,
            }
        );
    }
};

const getUserInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now.getTime() - date.getTime();
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

    if (diffInDays === 0) {
        return 'Today';
    } else if (diffInDays === 1) {
        return 'Yesterday';
    } else if (diffInDays < 7) {
        return `${diffInDays} days ago`;
    } else {
        return date.toLocaleDateString();
    }
};
</script>

<template>
    <AppLayout>

        <Head :title="`Pending Requests - ${group.name}`" />

        <div class="container mx-auto h-full overflow-auto p-6">
            <!-- Header -->
            <div class="mb-6">
                <Link :href="`/groups/${group.slug}`"
                    class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to {{ group.name }}
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Pending Join Requests</h1>
                <p class="text-gray-600 mt-1">
                    Review and manage requests to join your group
                </p>
            </div>

            <!-- Success Flash Message -->
            <Transition enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="statusMessage() && showSuccess"
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <CheckCircleIcon class="mr-2 h-5 w-5 text-green-600" />
                            <p class="text-sm font-medium text-green-800">
                                {{ statusMessage() }}
                            </p>
                        </div>
                        <button @click="dismissSuccess" class="text-green-600 hover:text-green-800">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Pending Requests List -->
            <div class="space-y-4">
                <Card v-if="pendingRequests.length === 0">
                    <CardContent class="flex flex-col items-center justify-center py-12">
                        <Clock class="h-16 w-16 text-gray-300 mb-4" />
                        <p class="text-lg font-medium text-gray-900 mb-1">No pending requests</p>
                        <p class="text-sm text-gray-500">All join requests have been processed</p>
                    </CardContent>
                </Card>

                <Card v-for="request in pendingRequests" :key="request.id" class="hover:shadow-md transition-shadow">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <Avatar class="h-16 w-16">
                                    <AvatarImage v-if="request.user?.profile_picture_url"
                                        :src="request.user.profile_picture_url" :alt="request.user?.name || 'User'" />
                                    <AvatarFallback>
                                        {{ getUserInitials(request.user?.name || 'U') }}
                                    </AvatarFallback>
                                </Avatar>

                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ request.user?.name || 'Unknown User' }}
                                        </h3>
                                        <Badge variant="secondary">
                                            <Clock class="mr-1 h-3 w-3" />
                                            Pending
                                        </Badge>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        @{{ request.user?.username || 'unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Requested {{ formatDate(request.joined_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Button variant="default" size="sm" @click="handleApprove(request.user?.id || 0)">
                                    <UserCheck class="mr-2 h-4 w-4" />
                                    Approve
                                </Button>
                                <Button variant="destructive" size="sm" @click="handleReject(request.user?.id || 0)">
                                    <UserX class="mr-2 h-4 w-4" />
                                    Reject
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
