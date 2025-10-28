<script setup lang="ts">
import type { User } from '@/types';
import FollowingListItems from './FollowingListItems.vue';

defineProps<{
    following: User[];
}>();
</script>

<template>
    <div class="rounded border bg-white px-3 py-3">
        <h2 class="text-xl font-bold">Following</h2>
        <FollowingListItems :following="following" />
    </div>
</template>
