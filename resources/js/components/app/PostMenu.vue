<script setup lang="ts">
import type { Post } from '@/types';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { EllipsisVertical, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    post: Post;
}>();

const emit = defineEmits<{
    edit: [];
    delete: [];
}>();
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton
            class="flex items-center rounded-full p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
        >
            <span class="sr-only">Open post options</span>
            <EllipsisVertical class="h-5 w-5" />
        </MenuButton>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
            >
                <div class="py-1">
                    <MenuItem v-slot="{ active }">
                        <button
                            type="button"
                            :class="[
                                active
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-700',
                                'group flex w-full items-center gap-2 px-4 py-2 text-sm',
                            ]"
                            @click="emit('edit')"
                        >
                            <Pencil class="h-4 w-4 text-gray-500" />
                            Edit Post
                        </button>
                    </MenuItem>

                    <MenuItem v-slot="{ active }">
                        <button
                            type="button"
                            :class="[
                                active
                                    ? 'bg-red-50 text-red-900'
                                    : 'text-red-700',
                                'group flex w-full items-center gap-2 px-4 py-2 text-sm',
                            ]"
                            @click="emit('delete')"
                        >
                            <Trash2 class="h-4 w-4 text-red-500" />
                            Delete Post
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
