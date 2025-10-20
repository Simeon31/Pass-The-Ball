<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
import { Users, Settings, UserPlus, Upload, CheckCircle, XCircle, Clock } from 'lucide-vue-next';
import type { Group, Post, GroupMember, PaginatedData } from '@/types';
import PostItem from '@/components/app/PostItem.vue';
import CreatePost from '@/components/app/CreatePost.vue';
import { useFlashMessage } from '@/composables/useFlashMessage';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

interface Props {
    group: Group;
    posts?: PaginatedData<Post>;
    members: GroupMember[];
    pendingRequestsCount?: number;
    hasPendingRequest?: boolean;
}

const props = defineProps<Props>();

const coverImageSrc = ref<string | null>(null);
const thumbnailImageSrc = ref<string | null>(null);
const coverFile = ref<File | null>(null);
const thumbnailFile = ref<File | null>(null);

const coverSrc = computed(() => coverImageSrc.value || props.group.cover_url || '');
const thumbnailSrc = computed(() => thumbnailImageSrc.value || props.group.thumbnail_url || '');

const hasUnsavedImages = computed(() => {
    return coverImageSrc.value !== null || thumbnailImageSrc.value !== null;
});

const canEditImages = computed(() => {
    return props.group.permissions?.includes('edit_group_images');
});

const canPost = computed(() => {
    return props.group.permissions?.includes('post_in_group');
});

const canInvite = computed(() => {
    return props.group.permissions?.includes('invite_members');
});

const canManageRequests = computed(() => {
    return props.group.permissions?.includes('approve_join_requests');
});

// Flash message handling
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);

const handleJoin = () => {
    router.post(`/groups/${props.group.slug}/join`, {}, {
        preserveScroll: true,
    });
};

const handleLeave = () => {
    if (confirm('Are you sure you want to leave this group?')) {
        router.post(`/groups/${props.group.slug}/leave`, {}, {
            preserveScroll: true,
        });
    }
};

const onCoverChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        coverFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImageSrc.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const onThumbnailChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        thumbnailFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            thumbnailImageSrc.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const submitImages = () => {
    const formData = new FormData();
    if (coverFile.value) {
        formData.append('cover', coverFile.value);
    }
    if (thumbnailFile.value) {
        formData.append('thumbnail', thumbnailFile.value);
    }

    router.post(`/groups/${props.group.slug}/images`, formData, {
        forceFormData: true,
        onSuccess: () => {
            coverImageSrc.value = null;
            thumbnailImageSrc.value = null;
            coverFile.value = null;
            thumbnailFile.value = null;
        },
    });
};

const cancelImages = () => {
    coverImageSrc.value = null;
    thumbnailImageSrc.value = null;
    coverFile.value = null;
    thumbnailFile.value = null;
};
</script>

<template>
    <AppLayout>

        <Head :title="group.name" />

        <div class="container mx-auto h-full overflow-auto">
            <!-- Group Header -->
            <div class="group relative bg-white">
                <!-- Cover Image -->
                <div class="relative h-64 bg-gradient-to-r from-blue-500 to-purple-600" :style="coverSrc
                    ? `background-image: url(${coverSrc}); background-size: cover; background-position: center;`
                    : ''
                    ">
                    <div v-if="canEditImages"
                        class="absolute right-4 top-4 rounded-lg bg-gray-900/70 p-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button v-if="!hasUnsavedImages"
                            class="flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm text-gray-900 hover:bg-gray-100">
                            <Upload class="h-4 w-4" />
                            Update Cover
                            <input type="file" class="absolute inset-0 cursor-pointer opacity-0" accept="image/*"
                                @change="onCoverChange" />
                        </button>
                        <div v-else class="flex gap-2">
                            <button @click="cancelImages"
                                class="flex items-center gap-1 rounded-md bg-white px-2 py-1 text-sm hover:bg-gray-100">
                                <XCircle class="h-4 w-4" />
                                Cancel
                            </button>
                            <button @click="submitImages"
                                class="flex items-center gap-1 rounded-md bg-green-600 px-2 py-1 text-sm text-white hover:bg-green-700">
                                <CheckCircle class="h-4 w-4" />
                                Save
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Group Info -->
                <div class="flex items-end gap-6 px-6 pb-4">
                    <!-- Thumbnail -->
                    <div class="group/thumbnail relative -mt-16 h-32 w-32">
                        <div class="h-full w-full rounded-xl border-4 border-white bg-white shadow-lg overflow-hidden">
                            <img v-if="thumbnailSrc" :src="thumbnailSrc" :alt="group.name"
                                class="h-full w-full object-cover" />
                            <div v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500">
                                <Users class="h-16 w-16 text-white" />
                            </div>
                        </div>
                        <div v-if="canEditImages"
                            class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/50 opacity-0 group-hover/thumbnail:opacity-100 transition-opacity">
                            <button v-if="!hasUnsavedImages"
                                class="relative rounded-md bg-white px-2 py-1 text-xs hover:bg-gray-100">
                                <Upload class="h-4 w-4" />
                                <input type="file" class="absolute inset-0 cursor-pointer opacity-0" accept="image/*"
                                    @change="onThumbnailChange" />
                            </button>
                            <div v-else class="flex flex-col gap-2">
                                <button @click="cancelImages"
                                    class="flex items-center justify-center gap-1 rounded-md bg-white px-2 py-1 text-xs hover:bg-gray-100">
                                    <XCircle class="h-3 w-3" />
                                    Cancel
                                </button>
                                <button @click="submitImages"
                                    class="flex items-center justify-center gap-1 rounded-md bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700">
                                    <CheckCircle class="h-3 w-3" />
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Name and Actions -->
                    <div class="flex flex-1 items-center justify-between pb-2">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ group.name }}</h1>
                            <p class="text-sm text-gray-600">
                                {{ group.member_count || 0 }} members
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <!-- Join/Leave Button -->
                            <Button v-if="!group.is_member && !hasPendingRequest" @click="handleJoin">
                                Join Group
                            </Button>
                            <Button v-else-if="hasPendingRequest" variant="outline" disabled>
                                <Clock class="mr-2 h-4 w-4" />
                                Request Pending
                            </Button>
                            <Button v-else-if="group.is_member && !group.is_owner" variant="outline"
                                @click="handleLeave">
                                Leave Group
                            </Button>

                            <!-- Invite Button -->
                            <Button v-if="canInvite" variant="outline">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Invite
                            </Button>

                            <!-- Settings Button -->
                            <Link v-if="group.is_owner" :href="`/groups/${group.slug}/edit`">
                            <Button variant="outline">
                                <Settings class="mr-2 h-4 w-4" />
                                Settings
                            </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-t">
                <TabGroup>
                    <TabList class="flex bg-white px-6">
                        <Tab v-slot="{ selected }" as="template">
                            <button :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                selected
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-600 hover:text-gray-900',
                            ]">
                                Posts
                            </button>
                        </Tab>
                        <Tab v-slot="{ selected }" as="template">
                            <button :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                selected
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-600 hover:text-gray-900',
                            ]">
                                About
                            </button>
                        </Tab>
                        <Tab v-slot="{ selected }" as="template">
                            <button :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                selected
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-600 hover:text-gray-900',
                            ]">
                                Members
                            </button>
                        </Tab>
                        <Tab v-if="canManageRequests && pendingRequestsCount && pendingRequestsCount > 0"
                            v-slot="{ selected }" as="template">
                            <button :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors relative',
                                selected
                                    ? 'border-blue-600 text-blue-600'
                                    : 'border-transparent text-gray-600 hover:text-gray-900',
                            ]">
                                Requests
                                <span
                                    class="ml-2 inline-flex items-center justify-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">
                                    {{ pendingRequestsCount }}
                                </span>
                            </button>
                        </Tab>
                    </TabList>

                    <TabPanels class="bg-gray-50 p-6">
                        <!-- Success Flash Message -->
                        <Transition enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 transform translate-y-2"
                            enter-to-class="opacity-100 transform translate-y-0"
                            leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                            leave-to-class="opacity-0">
                            <div v-if="statusMessage() && showSuccess"
                                class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
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

                        <!-- Posts Tab -->
                        <TabPanel>
                            <div class="w-full h-full relative size-32 ...">
                                <div class="absolute inset-y-0 right-0 w-[300px] ">
                                    <Card class="p-6">
                                        <h3 class="mb-4 text-lg font-semibold">About This Group</h3>
                                        <p v-if="group.about" class="whitespace-pre-wrap text-gray-700">
                                            {{ group.about }}
                                        </p>
                                        <p v-else class="italic text-gray-500">No description provided</p>
                                    </Card>
                                </div>
                            </div>



                            <div class="mx-auto max-w-3xl">
                                <div v-if="group.is_member" class="space-y-4">
                                    <CreatePost v-if="canPost" :groupId="group.id" />
                                    <div v-if="posts && posts.data.length > 0" class="space-y-4">
                                        <PostItem v-for="post in posts.data" :key="post.id" :post="post" />
                                    </div>
                                    <div v-else class="rounded-lg bg-white p-8 text-center">
                                        <p class="text-gray-600">No posts yet. Be the first to post!</p>
                                    </div>
                                </div>
                                <div v-else class="rounded-lg bg-white p-8 text-center">
                                    <Users class="mx-auto h-12 w-12 text-gray-400" />
                                    <p class="mt-4 text-gray-600">Join this group to see posts</p>
                                </div>
                            </div>
                        </TabPanel>

                        <!-- About Tab -->
                        <TabPanel>
                            <div class="mx-auto max-w-3xl">
                                <Card class="p-6">
                                    <h3 class="mb-4 text-lg font-semibold">About This Group</h3>
                                    <p v-if="group.about" class="whitespace-pre-wrap text-gray-700">
                                        {{ group.about }}
                                    </p>
                                    <p v-else class="italic text-gray-500">No description provided</p>
                                </Card>
                            </div>
                        </TabPanel>

                        <!-- Members Tab -->
                        <TabPanel>
                            <div class="mx-auto max-w-5xl">
                                <Card class="p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Members ({{ group.member_count || 0 }})</h3>
                                    <div v-if="members && members.length > 0"
                                        class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        <div v-for="member in members" :key="member.id"
                                            class="flex items-center gap-3 rounded-lg border p-3">
                                            <img v-if="member.user?.profile_picture_url"
                                                :src="member.user.profile_picture_url"
                                                :alt="member.user?.name || 'Member'"
                                                class="h-10 w-10 rounded-full object-cover" />
                                            <div v-else
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200">
                                                <Users class="h-5 w-5 text-gray-600" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium">{{ member.user?.name ||
                                                    'Unknown' }}
                                                </p>
                                                <p class="text-xs capitalize text-gray-500">{{ member.role }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 text-center py-4">
                                        No members to display
                                    </div>
                                </Card>
                            </div>
                        </TabPanel>

                        <!-- Requests Tab -->
                        <TabPanel v-if="canManageRequests">
                            <div class="mx-auto max-w-3xl">
                                <Card class="p-6">
                                    <h3 class="mb-4 text-lg font-semibold">Pending Join Requests</h3>
                                    <p class="text-gray-600">Manage join requests here</p>
                                </Card>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AppLayout>
</template>
