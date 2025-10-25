<template>
    <AppSidebarLayout>
        <div class="mx-auto max-w-4xl px-4 py-6">
            <!-- Header -->
            <div class="mb-6">
                <Link :href="`/profile/${user.username}`"
                    class="mb-4 inline-flex items-center text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Profile
                </Link>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{
                        type === 'followers'
                            ? `${user.name}'s Followers`
                            : `${user.name} is Following`
                    }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ followers.length }}
                    {{ type === 'followers' ? 'followers' : 'following' }}
                </p>
            </div>

            <!-- Followers/Following List -->
            <div v-if="followers.length > 0"
                class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white dark:divide-gray-700 dark:border-gray-700 dark:bg-gray-800">
                <div v-for="follower in followers" :key="follower.id"
                    class="flex items-center justify-between p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <!-- User Info -->
                    <Link :href="`/profile/${follower.username}`"
                        class="flex items-center space-x-3 transition hover:opacity-80">
                    <!-- Avatar -->
                    <img :src="follower.profile_picture_url ||
                        'https://static.vecteezy.com/system/resources/previews/054/720/352/non_2x/student-3d-icon-for-education-projects-on-transparent-background-png.png'
                        " :alt="follower.name"
                        class="h-12 w-12 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700" />

                    <!-- Name & Username -->
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ follower.name }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @{{ follower.username }}
                        </p>
                    </div>
                    </Link>

                    <!-- Follow Button (only show if not viewing own profile) -->
                    <FollowButton v-if="follower.id !== page.props.auth.user.id" :userId="follower.id"
                        :isFollowing="follower.is_followed_by_auth || false" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else
                class="rounded-lg border border-gray-200 bg-white p-12 text-center dark:border-gray-700 dark:bg-gray-800">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                    {{
                        type === 'followers'
                            ? 'No followers yet'
                            : 'Not following anyone yet'
                    }}
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{
                        type === 'followers'
                            ? 'When people follow this user, they will appear here.'
                            : 'When this user follows someone, they will appear here.'
                    }}
                </p>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import FollowButton from '@/components/app/FollowButton.vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { User, AppPageProps } from '@/types';

interface Props {
    user: User;
    followers: User[];
    type: 'followers' | 'following';
}

defineProps<Props>();

const page = usePage<AppPageProps>();
</script>
