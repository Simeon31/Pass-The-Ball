<script setup lang="ts">
import type { PaginatedData, Post } from '@/types';
import { ref } from 'vue';
import axios from 'axios';
import { useIntersectionObserver } from '@/composables/useIntersectionObserver';
import PostItem from './PostItem.vue';

const props = defineProps<{ 
    initialPosts: PaginatedData<Post>;
}>();

// Local state for posts
const posts = ref<Post[]>([...props.initialPosts.data]);
const currentPage = ref(props.initialPosts.meta.current_page);
const lastPage = ref(props.initialPosts.meta.last_page);
const isLoading = ref(false);
const hasMorePosts = ref(currentPage.value < lastPage.value);

// Reference to the sentinel element (invisible div at the bottom)
const sentinelRef = ref<HTMLElement | null>(null);

// Load more posts function
const loadMorePosts = async () => {
    console.log('loadMorePosts triggered', { 
        isLoading: isLoading.value, 
        hasMorePosts: hasMorePosts.value,
        currentPage: currentPage.value 
    });
    
    // Prevent multiple simultaneous loads
    if (isLoading.value || !hasMorePosts.value) {
        console.log('Aborting: isLoading or no more posts');
        return;
    }

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    console.log('Fetching page:', nextPage);

    try {
        const response = await axios.get<PaginatedData<Post>>('/api/posts', {
            params: {
                page: nextPage,
                per_page: 10,
            },
        });

        const newPosts = response.data.data;
        
        console.log('Loaded posts:', newPosts.length);
        
        // Append new posts to the list
        posts.value.push(...newPosts);
        
        // Update pagination state
        currentPage.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
        hasMorePosts.value = currentPage.value < lastPage.value;
        
        console.log('Updated state:', {
            currentPage: currentPage.value,
            lastPage: lastPage.value,
            hasMorePosts: hasMorePosts.value
        });
    } catch (error) {
        console.error('Error loading more posts:', error);
    } finally {
        isLoading.value = false;
    }
};

// Setup IntersectionObserver on the sentinel element
useIntersectionObserver(sentinelRef, loadMorePosts, {
    rootMargin: '100px', // Start loading 100px before sentinel is visible
});
</script>

<template>
    <div class="space-y-0">
        <PostItem v-for="post of posts" :key="post.id" :post="post" />

        <!-- Sentinel element for infinite scroll -->
        <div v-if="hasMorePosts" ref="sentinelRef" class="flex justify-center py-4">
            <div v-if="isLoading" class="flex items-center gap-2 text-gray-500">
                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Loading more posts...</span>
            </div>
        </div>

        <!-- End of posts message -->
        <div v-else-if="posts.length > 0" class="py-8 text-center">
            <p class="text-sm text-gray-500">You've reached the end of the feed</p>
        </div>
    </div>
</template>
