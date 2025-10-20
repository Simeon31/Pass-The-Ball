<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Mail, CheckCircle, XCircle, Clock, Users } from 'lucide-vue-next';
import type { GroupInvitation } from '@/types';
import { useFlashMessage } from '@/composables/useFlashMessage';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

interface Props {
    invitations: GroupInvitation[];
}

const props = defineProps<Props>();

// Flash message handling
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);

const handleAccept = (token: string) => {
    router.post(
        `/groups/invitations/${token}/respond`,
        { action: 'accept' },
        {
            preserveScroll: true,
        }
    );
};

const handleReject = (token: string) => {
    if (confirm('Are you sure you want to reject this invitation?')) {
        router.post(
            `/groups/invitations/${token}/respond`,
            { action: 'reject' },
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

const getExpiresIn = (expiresAt: string) => {
    const expiry = new Date(expiresAt);
    const now = new Date();
    const diffInMs = expiry.getTime() - now.getTime();
    const diffInDays = Math.ceil(diffInMs / (1000 * 60 * 60 * 24));

    if (diffInDays < 0) {
        return 'Expired';
    } else if (diffInDays === 0) {
        return 'Expires today';
    } else if (diffInDays === 1) {
        return 'Expires tomorrow';
    } else {
        return `Expires in ${diffInDays} days`;
    }
};
</script>

<template>
    <AppLayout>

        <Head title="Group Invitations" />

        <div class="container mx-auto h-full overflow-auto p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Group Invitations</h1>
                <p class="text-gray-600 mt-1">
                    You've been invited to join these groups
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

            <!-- Invitations List -->
            <div class="space-y-4">
                <Card v-if="invitations.length === 0">
                    <CardContent class="flex flex-col items-center justify-center py-12">
                        <Mail class="h-16 w-16 text-gray-300 mb-4" />
                        <p class="text-lg font-medium text-gray-900 mb-1">No pending invitations</p>
                        <p class="text-sm text-gray-500">You don't have any group invitations at the moment</p>
                    </CardContent>
                </Card>

                <Card v-for="invitation in invitations" :key="invitation.id" class="hover:shadow-md transition-shadow">
                    <CardContent class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1">
                                <!-- Group Thumbnail -->
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-20 h-20 rounded-lg overflow-hidden bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                                        <img v-if="invitation.group?.thumbnail_url"
                                            :src="invitation.group.thumbnail_url"
                                            :alt="invitation.group?.name || 'Group'"
                                            class="w-full h-full object-cover" />
                                        <Users v-else class="h-10 w-10 text-white" />
                                    </div>
                                </div>

                                <!-- Invitation Details -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Link :href="`/groups/${invitation.group?.slug}`"
                                            class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                                        {{ invitation.group?.name || 'Unknown Group' }}
                                        </Link>
                                        <Badge v-if="invitation.is_valid" variant="default">
                                            <Clock class="mr-1 h-3 w-3" />
                                            Pending
                                        </Badge>
                                        <Badge v-else variant="destructive">
                                            Expired
                                        </Badge>
                                    </div>

                                    <p class="text-sm text-gray-600 mb-2">
                                        <span class="font-medium">{{ invitation.inviter?.name || 'Someone' }}</span>
                                        invited you to join this group
                                    </p>

                                    <p v-if="invitation.group?.about" class="text-sm text-gray-500 line-clamp-2 mb-2">
                                        {{ invitation.group.about }}
                                    </p>

                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span>Invited {{ formatDate(invitation.created_at) }}</span>
                                        <span :class="{
                                            'text-red-600': !invitation.is_valid,
                                            'text-gray-500': invitation.is_valid,
                                        }">
                                            {{ getExpiresIn(invitation.expires_at) }}
                                        </span>
                                        <span v-if="invitation.group?.member_count">
                                            {{ invitation.group.member_count }} members
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div v-if="invitation.is_valid" class="flex gap-2 md:flex-col lg:flex-row">
                                <Button variant="default" size="sm" class="flex-1 md:flex-none"
                                    @click="handleAccept(invitation.token || '')">
                                    <CheckCircle class="mr-2 h-4 w-4" />
                                    Accept
                                </Button>
                                <Button variant="outline" size="sm" class="flex-1 md:flex-none"
                                    @click="handleReject(invitation.token || '')">
                                    <XCircle class="mr-2 h-4 w-4" />
                                    Decline
                                </Button>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic">
                                This invitation has expired
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
