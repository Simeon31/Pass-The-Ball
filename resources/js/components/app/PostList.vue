<script setup lang="ts">
import PostItem from './PostItem.vue';
import type { Post } from '@/types';

defineProps<{ posts: Post[] }>();

</script>

<template>
    <div>
        <div class="overflow-y-auto">
            <PostItem v-for="post of posts" :key="post.id" :post="post" />
        </div>
    </div>
</template>