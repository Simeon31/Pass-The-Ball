<script setup lang="ts">
import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Group } from '@/types';
import { Users } from 'lucide-vue-next';

interface Props {
    groups: Group[];
}

const props = defineProps<Props>();

const searchKeyword = ref('');

// Filter groups based on search keyword
const filteredGroups = computed(() => {
    if (!searchKeyword.value.trim()) {
        return props.groups;
    }
    
    const keyword = searchKeyword.value.toLowerCase();
    return props.groups.filter(group => 
        group.name.toLowerCase().includes(keyword) ||
        group.about?.toLowerCase().includes(keyword)
    );
});
</script>

<template>
    <TextInput v-model="searchKeyword" placeholder="Type to search groups" class="w-full" />
    <div class="mt-3 py-3">
        <div v-if="groups.length === 0" class="flex flex-col items-center p-6 text-center text-gray-400">
            <Users class="mb-2 h-12 w-12 text-gray-300" />
            <p class="text-sm">You haven't joined any groups yet</p>
            <Link href="/groups" class="mt-2 text-sm text-blue-600 hover:underline">
            Discover groups
            </Link>
        </div>
        <div v-else-if="filteredGroups.length === 0" class="flex p-3 text-center text-sm text-gray-400">
            No groups match your search
        </div>
        <div v-else>
            <GroupItem v-for="group in filteredGroups" :key="group.id" :group="group" />
        </div>
    </div>
</template>
