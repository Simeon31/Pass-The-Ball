<script setup lang="ts">
import CreatePost from '@/components/app/CreatePost.vue';
import FollowingList from '@/components/app/FollowingList.vue';
import GroupList from '@/components/app/GroupList.vue';
import PostList from '@/components/app/PostList.vue';
import { useFlashMessage } from '@/composables/useFlashMessage';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PaginatedData, Post, Group, User } from '@/types';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';

defineProps<{
    posts: PaginatedData<Post>;
    groups: Group[];
    following: User[];
}>();

// Using flash message composable
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);
</script>

<template>
    <AppLayout>

        <Head title="Pass the Ball">
            <link rel="preconnect" href="https://rsms.me/" />
            <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        </Head>

        <div class="grid h-screen gap-3 p-4 lg:grid-cols-12">
            <div class="h-full overflow-y-auto lg:order-first lg:col-span-3">
                <div class="h-[400px] overflow-y-auto">
                    <GroupList :groups="groups" />
                </div>
            </div>
            <div class="h-full overflow-y-auto lg:order-last lg:col-span-3">
                <div class="h-[400px] overflow-y-auto">
                    <FollowingList :following="following" />
                </div>
            </div>
            <div class="flex h-full flex-col overflow-y-auto lg:col-span-6">
                <!-- Success Message -->
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
                <CreatePost />
                <PostList :initial-posts="posts" class="flex-1" />
            </div>
        </div>
    </AppLayout>
</template>
