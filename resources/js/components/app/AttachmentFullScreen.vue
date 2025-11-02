<script setup lang="ts">
import type { PostAttachment } from '@/types';
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    ArrowDownTrayIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DocumentIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    attachments: PostAttachment[];
    initialIndex: number;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const currentIndex = ref(props.initialIndex);

watch(
    () => props.initialIndex,
    (newIndex) => {
        currentIndex.value = newIndex;
    },
);

const currentAttachment = computed(
    () => props.attachments[currentIndex.value],
);

const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(
    () => currentIndex.value < props.attachments.length - 1,
);

function isImage(attachment: PostAttachment): boolean {
    return attachment.mime_type.startsWith('image/');
}

function isVideo(attachment: PostAttachment): boolean {
    return attachment.mime_type.startsWith('video/');
}

function isPDF(attachment: PostAttachment): boolean {
    return attachment.mime_type === 'application/pdf';
}

function goToPrevious() {
    if (hasPrevious.value) {
        currentIndex.value--;
    }
}

function goToNext() {
    if (hasNext.value) {
        currentIndex.value++;
    }
}

function close() {
    emit('update:isOpen', false);
}

function downloadAttachment() {
    const link = document.createElement('a');
    link.href = currentAttachment.value.url;
    link.download = currentAttachment.value.name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

// Keyboard navigation
function handleKeydown(event: KeyboardEvent) {
    if (!props.isOpen) return;

    switch (event.key) {
        case 'ArrowLeft':
            goToPrevious();
            break;
        case 'ArrowRight':
            goToNext();
            break;
        case 'Escape':
            close();
            break;
    }
}

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            document.addEventListener('keydown', handleKeydown);
        } else {
            document.removeEventListener('keydown', handleKeydown);
        }
    },
);
</script>

<template>
    <TransitionRoot :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/90 transition-opacity" aria-hidden="true" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel class="relative flex h-[90vh] w-full max-w-7xl flex-col">
                            <!-- Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-white">
                                        {{ currentAttachment.name }}
                                    </h3>
                                    <p class="text-sm text-gray-300">
                                        {{ currentIndex + 1 }} /
                                        {{ attachments.length }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="downloadAttachment"
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20"
                                        type="button">
                                        <ArrowDownTrayIcon class="h-5 w-5" />
                                    </button>
                                    <button @click="close"
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-white transition-colors hover:bg-white/20"
                                        type="button">
                                        <XMarkIcon class="h-6 w-6" />
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="relative flex flex-1 items-center justify-center">
                                <!-- Previous Button -->
                                <button v-if="hasPrevious" @click="goToPrevious"
                                    class="absolute left-4 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20"
                                    type="button">
                                    <ChevronLeftIcon class="h-6 w-6" />
                                </button>

                                <!-- Attachment Display -->
                                <div class="flex max-h-full max-w-full items-center justify-center">
                                    <!-- Image -->
                                    <img v-if="isImage(currentAttachment)" :src="currentAttachment.url"
                                        :alt="currentAttachment.name"
                                        class="max-h-full max-w-full rounded-lg object-contain" />

                                    <!-- Video -->
                                    <video v-else-if="isVideo(currentAttachment)" :src="currentAttachment.url" controls
                                        class="max-h-full max-w-full rounded-lg" />

                                    <!-- PDF or Other Files - Show Download Card -->
                                    <div v-else
                                        class="flex flex-col items-center justify-center rounded-lg bg-white/10 p-12 backdrop-blur-sm">
                                        <div class="flex flex-col items-center">
                                            <!-- PDF Icon -->
                                            <div v-if="isPDF(currentAttachment)"
                                                class="mb-6 rounded-full bg-red-500/20 p-8">
                                                <DocumentIcon class="h-20 w-20 text-red-400" />
                                            </div>
                                            <!-- Generic File Icon -->
                                            <div v-else class="mb-6 rounded-full bg-gray-500/20 p-8">
                                                <DocumentIcon class="h-20 w-20 text-gray-400" />
                                            </div>

                                            <h3 class="mb-2 text-center text-xl font-semibold text-white">
                                                {{ currentAttachment.name }}
                                            </h3>
                                            <p class="mb-6 text-sm text-gray-300">
                                                {{ formatFileSize(currentAttachment.size) }}
                                            </p>

                                            <button @click="downloadAttachment"
                                                class="flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-base font-medium text-white transition-colors hover:bg-indigo-700"
                                                type="button">
                                                <ArrowDownTrayIcon class="h-5 w-5" />
                                                Download File
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Next Button -->
                                <button v-if="hasNext" @click="goToNext"
                                    class="absolute right-4 z-10 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20"
                                    type="button">
                                    <ChevronRightIcon class="h-6 w-6" />
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
