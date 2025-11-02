<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CreatePostModal from './CreatePostModal.vue';

interface Props {
    groupId?: number;
}

const props = defineProps<Props>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const isModalOpen = ref(false);

function openModal() {
    isModalOpen.value = true;
}
</script>

<template>
    <div class="mb-3 rounded-lg border bg-white p-4">
        <div class="flex items-center gap-3">
            <img :src="user.profile_picture_url ||
                'https://avatar.iran.liara.run/public/'
                " alt="User avatar" class="h-10 w-10 rounded-full border-2 object-cover" />
            <button @click="openModal"
                class="flex-1 rounded-full border border-gray-300 bg-gray-100 px-4 py-2 text-left text-gray-500 transition-colors hover:bg-gray-200">
                What's on your mind?
            </button>
        </div>
    </div>

    <!-- Create Post Modal -->
    <CreatePostModal :is-open="isModalOpen" :group-id="groupId" @update:is-open="isModalOpen = $event" />
</template>
