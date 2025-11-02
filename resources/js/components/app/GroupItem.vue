<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Group } from '@/types';

interface Props {
    group: Group;
}

const props = defineProps<Props>();
</script>

<template>
    <Link :href="`/groups/${group.slug}`" class="mb-3 block rounded-lg p-2 hover:bg-gray-100">
    <div class="flex items-start gap-3 px-3 py-2">
        <img class="h-[68px] w-[68px] rounded-full object-cover"
            :src="group.thumbnail_url || '/images/default-no-image.png'" :alt="group.name" />
        <div class="min-w-0 flex-1">
            <h3 class="truncate text-lg font-black">{{ group.name }}</h3>
            <div class="line-clamp-2 text-sm text-gray-600">
                {{ `${group.member_count || 0} members` }}
            </div>
        </div>
    </div>
    </Link>
</template>
