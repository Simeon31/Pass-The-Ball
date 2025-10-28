<script setup lang="ts">
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
}>();

// Fallback image for users without profile picture
const profileImage = props.user.profile_picture_url || '/images/default-avatar.png';
</script>

<template>
    <Link :href="`/profile/${user.username}`" class="mb-3 block cursor-pointer rounded-lg p-2 hover:bg-gray-100">
    <div class="flex items-center gap-2 px-3 py-2">
        <img class="h-[68px] w-[68px] rounded-full object-cover" :src="profileImage" :alt="user.name" />
        <div>
            <h3 class="font-black">{{ user.name }}</h3>
            <p class="text-sm text-gray-600">@{{ user.username }}</p>
        </div>
    </div>
    </Link>
</template>
