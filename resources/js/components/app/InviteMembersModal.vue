<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Search, UserPlus, Loader2, CheckCircle } from 'lucide-vue-next';
import type { Group, User } from '@/types';
import { debounce } from 'lodash-es';

interface Props {
    group: Group;
}

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen', { default: false });

const searchQuery = ref('');
const searchResults = ref<User[]>([]);
const isSearching = ref(false);
const selectedUserId = ref<number | null>(null);
const invitedUserIds = ref<Set<number>>(new Set());

const form = useForm({
    user_id: null as number | null,
});

// Search users
const searchUsers = debounce(async () => {
    if (searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;

    try {
        // Use Inertia visit to search users
        const response = await fetch(`/api/users/search?q=${encodeURIComponent(searchQuery.value)}&limit=10`);
        const data = await response.json();
        searchResults.value = data.users || [];
    } catch (error) {
        console.error('Error searching users:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(searchQuery, () => {
    searchUsers();
});

const handleInvite = (userId: number) => {
    form.user_id = userId;

    form.post(`/groups/${props.group.slug}/invite`, {
        preserveScroll: true,
        onSuccess: () => {
            invitedUserIds.value.add(userId);
            form.reset();
            selectedUserId.value = null;

            // Clear invited status after 3 seconds
            setTimeout(() => {
                invitedUserIds.value.delete(userId);
            }, 3000);
        },
        onError: (errors) => {
            console.error('Invitation error:', errors);
        },
    });
};

const isUserInvited = (userId: number) => invitedUserIds.value.has(userId);

const getUserInitials = (user: User) => {
    return user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const handleClose = () => {
    isOpen.value = false;
    searchQuery.value = '';
    searchResults.value = [];
    selectedUserId.value = null;
    form.reset();
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Invite Members to {{ group.name }}</DialogTitle>
                <DialogDescription>
                    Search for users and send them an invitation to join this group.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Search Input -->
                <div class="space-y-2">
                    <Label for="search">Search Users</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <Input id="search" v-model="searchQuery" type="text" placeholder="Search by name or username..."
                            class="pl-10" />
                        <Loader2 v-if="isSearching" class="absolute right-3 top-3 h-4 w-4 animate-spin text-gray-400" />
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="searchResults.length > 0"
                    class="max-h-[300px] overflow-y-auto space-y-2 rounded-md border p-2">
                    <div v-for="user in searchResults" :key="user.id"
                        class="flex items-center justify-between rounded-md p-2 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <Avatar class="h-10 w-10">
                                <AvatarImage v-if="user.profile_picture_url" :src="user.profile_picture_url"
                                    :alt="user.name" />
                                <AvatarFallback>{{ getUserInitials(user) }}</AvatarFallback>
                            </Avatar>
                            <div>
                                <p class="text-sm font-medium">{{ user.name }}</p>
                                <p class="text-xs text-gray-500">@{{ user.username }}</p>
                            </div>
                        </div>

                        <Button v-if="!isUserInvited(user.id)" size="sm" variant="outline" :disabled="form.processing"
                            @click="handleInvite(user.id)">
                            <UserPlus class="mr-2 h-4 w-4" />
                            Invite
                        </Button>
                        <div v-else class="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle class="h-4 w-4" />
                            Invited
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="searchQuery.trim().length >= 2 && !isSearching" class="text-center py-8 text-gray-500">
                    <Search class="mx-auto h-12 w-12 text-gray-300 mb-2" />
                    <p class="text-sm">No users found matching "{{ searchQuery }}"</p>
                </div>

                <!-- Instruction -->
                <div v-else class="text-center py-8 text-gray-500">
                    <UserPlus class="mx-auto h-12 w-12 text-gray-300 mb-2" />
                    <p class="text-sm">Search for users to invite them to your group</p>
                    <p class="text-xs mt-1">Type at least 2 characters to start searching</p>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="handleClose">Close</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
