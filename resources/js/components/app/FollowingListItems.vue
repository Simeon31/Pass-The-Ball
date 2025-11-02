<script setup lang="ts">
import TextInput from '@/components/TextInput.vue';
import type { User } from '@/types';
import { computed, ref } from 'vue';
import FollowingItem from './FollowingItem.vue';

const props = defineProps<{
    following: User[];
}>();

const searchKeyword = ref('');

// Filter following list based on search keyword
const filteredFollowing = computed(() => {
    if (!searchKeyword.value.trim()) {
        return props.following;
    }
    
    const keyword = searchKeyword.value.toLowerCase();
    return props.following.filter(user => 
        user.name.toLowerCase().includes(keyword) ||
        user.username?.toLowerCase().includes(keyword)
    );
});
</script>

<template>
    <TextInput v-model="searchKeyword" placeholder="Type to search friends list" class="w-full" />
    <div class="mt-3 lg:flex-1">
        <div v-if="following.length === 0" class="flex p-3 text-center text-gray-400">
            You currently have no friends
        </div>
        <div v-else-if="filteredFollowing.length === 0" class="flex p-3 text-center text-gray-400">
            No friends match your search
        </div>
        <div v-else>
            <FollowingItem v-for="user in filteredFollowing" :key="user.id" :user="user" />
        </div>
    </div>
</template>
