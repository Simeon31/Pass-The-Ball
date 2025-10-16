<script setup lang="ts">
import type { PostAttachment } from '@/types';
import { DocumentIcon } from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        attachments: PostAttachment[];
        showPreview?: boolean;
        maxVisible?: number;
        enableLazyLoad?: boolean;
    }>(),
    {
        showPreview: false,
        maxVisible: 4,
        enableLazyLoad: true,
    },
);

const emit = defineEmits<{
    (e: 'click', attachment: PostAttachment, index: number): void;
    (e: 'remove', index: number): void;
    (e: 'seeAll'): void;
}>();

const showAll = ref(false);

const visibleAttachments = computed(() => {
    if (!props.enableLazyLoad || showAll.value || props.showPreview) {
        return props.attachments;
    }
    return props.attachments.slice(0, props.maxVisible);
});

const hasMore = computed(() => {
    return (
        props.enableLazyLoad &&
        !showAll.value &&
        !props.showPreview &&
        props.attachments.length > props.maxVisible
    );
});

const remainingCount = computed(() => {
    return props.attachments.length - props.maxVisible;
});

function handleSeeAll() {
    showAll.value = true;
    emit('seeAll');
}

function isImage(attachment: PostAttachment): boolean {
    return attachment.mime_type.startsWith('image/');
}

function isVideo(attachment: PostAttachment): boolean {
    return attachment.mime_type.startsWith('video/');
}

function isPDF(attachment: PostAttachment): boolean {
    return attachment.mime_type === 'application/pdf';
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

const gridCols = computed(() => {
    const count = visibleAttachments.value.length;
    if (count === 1) return 'grid-cols-1';
    if (count === 2) return 'grid-cols-2';
    return 'grid-cols-2 lg:grid-cols-3';
});

// Reset showAll when attachments change
watch(
    () => props.attachments,
    () => {
        showAll.value = false;
    },
);
</script>

<template>
    <div v-if="attachments.length > 0">
        <div :class="['grid gap-3', gridCols]">
            <div v-for="(attachment, index) in visibleAttachments" :key="attachment.id || index"
                class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg bg-gray-100"
                @click="emit('click', attachment, index)">
                <!-- Image Preview -->
                <img v-if="isImage(attachment)" :src="attachment.url" :alt="attachment.name"
                    class="h-full w-full object-cover transition-transform group-hover:scale-105" />

                <!-- Video Preview -->
                <div v-else-if="isVideo(attachment)" class="flex h-full w-full items-center justify-center bg-gray-900">
                    <video :src="attachment.url" class="h-full w-full object-cover" muted />
                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="h-16 w-16 text-white">
                            <path fill-rule="evenodd"
                                d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- PDF Preview -->
                <div v-else-if="isPDF(attachment)"
                    class="flex h-full w-full flex-col items-center justify-center bg-red-50 p-4 text-center">
                    <DocumentIcon class="h-16 w-16 text-red-500" />
                    <p class="mt-2 text-sm font-medium text-gray-700 line-clamp-2">
                        {{ attachment.name }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ formatFileSize(attachment.size) }}
                    </p>
                </div>

                <!-- Generic File Preview -->
                <div v-else class="flex h-full w-full flex-col items-center justify-center bg-gray-200 p-4 text-center">
                    <DocumentIcon class="h-16 w-16 text-gray-500" />
                    <p class="mt-2 text-sm font-medium text-gray-700 line-clamp-2">
                        {{ attachment.name }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ formatFileSize(attachment.size) }}
                    </p>
                </div>

                <!-- Remove button (only in preview mode before post creation) -->
                <button v-if="showPreview" @click.stop="emit('remove', index)"
                    class="absolute top-2 right-2 flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-white opacity-0 transition-all hover:bg-red-700 group-hover:opacity-100"
                    type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd"
                            d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- See More Button -->
        <button v-if="hasMore" @click="handleSeeAll"
            class="mt-3 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 py-3 text-sm font-medium text-gray-600 transition-colors hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-600"
            type="button">
            See {{ remainingCount }} More
            {{ remainingCount === 1 ? 'File' : 'Files' }}
        </button>
    </div>
</template>
