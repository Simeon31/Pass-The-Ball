<script setup lang="ts">
import type { ReactionType } from '@/types';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';
import { computed } from 'vue';

const props = defineProps<{
    currentReaction: ReactionType | null;
    totalReactions?: number;
}>();

const emit = defineEmits<{
    react: [type: ReactionType];
}>();

interface ReactionOption {
    type: ReactionType;
    emoji: string;
    label: string;
    color: string;
}

const reactions: ReactionOption[] = [
    { type: 'like', emoji: '👍', label: 'Like', color: 'text-blue-500' },
    { type: 'love', emoji: '❤️', label: 'Love', color: 'text-red-500' },
    { type: 'haha', emoji: '😂', label: 'Haha', color: 'text-yellow-500' },
    { type: 'wow', emoji: '😮', label: 'Wow', color: 'text-purple-500' },
    { type: 'sad', emoji: '😢', label: 'Sad', color: 'text-blue-400' },
    { type: 'angry', emoji: '😠', label: 'Angry', color: 'text-orange-500' },
];

const currentReactionData = computed(() => {
    if (!props.currentReaction) return null;
    return reactions.find(r => r.type === props.currentReaction);
});

const handleReaction = (type: ReactionType) => {
    emit('react', type);
};
</script>

<template>
    <Popover class="relative">
        <PopoverButton
            class="group flex flex-1 cursor-pointer items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-800 transition-all hover:bg-gray-200 focus:outline-none"
            :class="{ [currentReactionData?.color || '']: currentReaction }">
            <span v-if="currentReaction && currentReactionData" class="text-xl">
                {{ currentReactionData.emoji }}
            </span>
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
            </svg>
            <span class="hidden group-hover:inline" v-if="currentReaction && currentReactionData">{{ currentReactionData.label }}</span>
            <span class="hidden group-hover:inline" v-else>Like</span>
            <span v-if="totalReactions && totalReactions > 0" class="ml-1 text-sm font-semibold">
                {{ totalReactions }}
            </span>
        </PopoverButton>

        <PopoverPanel
            class="absolute bottom-full left-0 z-10 mb-2 flex gap-1 rounded-full bg-white p-2 shadow-lg ring-1 ring-black ring-opacity-5">
            <button v-for="reaction in reactions" :key="reaction.type" @click="handleReaction(reaction.type)"
                class="group relative flex h-12 w-12 items-center justify-center rounded-full text-3xl transition-all hover:scale-125 hover:bg-gray-100"
                :title="reaction.label">
                <span class="transition-transform group-hover:scale-110">{{ reaction.emoji }}</span>
                <!-- Tooltip -->
                <span
                    class="absolute -top-8 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-xs text-white group-hover:block">
                    {{ reaction.label }}
                </span>
            </button>
        </PopoverPanel>
    </Popover>
</template>
