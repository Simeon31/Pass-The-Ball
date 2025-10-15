<script setup lang="ts">
import CreatePost from '@/components/app/CreatePost.vue';
import FollowingList from '@/components/app/FollowingList.vue';
import GroupItem from '@/components/app/GroupItem.vue';
import GroupList from '@/components/app/GroupList.vue';
import PostList from '@/components/app/PostList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useFlashMessage } from '@/composables/useFlashMessage';
import post from '@/routes/post';

defineProps<{ posts: object }>();

// Using flash message composable
const { showMessage: showSuccess, message: statusMessage, dismiss: dismissSuccess } = useFlashMessage('status', 5000);
</script>

<template>
    <AppLayout>

        <Head title="Pass the Ball">
            <link rel="preconnect" href="https://rsms.me/" />
            <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        </Head>

        <div class="grid lg:grid-cols-12 gap-3 p-4 h-screen">
            <div class="lg:col-span-3 lg:order-first h-full overflow-y-auto">
                <div class="h-[400px] overflow-y-auto">
                    <GroupList />
                </div>
            </div>
            <div class="lg:col-span-3 lg:order-last h-full overflow-y-auto">
                <div class="h-[400px] overflow-y-auto">
                    <FollowingList />
                </div>
            </div>
            <div class="lg:col-span-6 h-full overflow-y-auto flex flex-col">
                <!-- Success Message -->
                <Transition enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 transform translate-y-2"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
                    <div v-if="statusMessage() && showSuccess"
                        class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <CheckCircleIcon class="w-5 h-5 text-green-600 mr-2" />
                                <p class="text-sm font-medium text-green-800">
                                    {{ statusMessage() }}
                                </p>
                            </div>
                            <button @click="dismissSuccess" class="text-green-600 hover:text-green-800">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </Transition>
                <CreatePost />
                <PostList :posts="posts.data" class="flex-1" />
            </div>
        </div>
    </AppLayout>
</template>
