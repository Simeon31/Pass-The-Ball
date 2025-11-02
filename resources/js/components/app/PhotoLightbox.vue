<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { download as downloadPhoto, view as viewPhoto } from '@/routes/gallery/photos';
import type { Photo } from '@/types';
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { router } from '@inertiajs/vue3';
import {
    ArrowDownTrayIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ZoomIn, ZoomOut } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    photos: Photo[];
    initialIndex: number;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const currentIndex = ref(props.initialIndex);
const showMetadata = ref(false);
const zoomLevel = ref(1);

watch(
    () => props.initialIndex,
    (newIndex) => {
        currentIndex.value = newIndex;
        zoomLevel.value = 1; // Reset zoom when changing photo
    },
);

const currentPhoto = computed(() => props.photos[currentIndex.value]);

const hasPrevious = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < props.photos.length - 1);

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

function handleDownload() {
    if (!currentPhoto.value) return;

    // Track download
    router.visit(downloadPhoto.url({ photo: currentPhoto.value.slug }), {
        preserveState: true,
        preserveScroll: true,
    });
}

function toggleMetadata() {
    showMetadata.value = !showMetadata.value;
}

function zoomIn() {
    if (zoomLevel.value < 3) {
        zoomLevel.value += 0.25;
    }
}

function zoomOut() {
    if (zoomLevel.value > 0.5) {
        zoomLevel.value -= 0.25;
    }
}

function resetZoom() {
    zoomLevel.value = 1;
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
        case 'i':
        case 'I':
            toggleMetadata();
            break;
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
        case '_':
            zoomOut();
            break;
        case '0':
            resetZoom();
            break;
    }
}

// Track view count when opening
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            document.addEventListener('keydown', handleKeydown);
            // Track view
            if (currentPhoto.value) {
                router.post(
                    viewPhoto.url({ photo: currentPhoto.value.slug }),
                    {},
                    {
                        preserveState: true,
                        preserveScroll: true,
                    },
                );
            }
        } else {
            document.removeEventListener('keydown', handleKeydown);
        }
    },
);

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

function formatDate(dateString?: string): string {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <TransitionRoot :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/95 transition-opacity" aria-hidden="true" />
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
                                        {{
                                            currentPhoto?.title ||
                                            `Photo ${currentIndex + 1}`
                                        }}
                                    </h3>
                                    <p class="text-sm text-gray-300">
                                        {{
                                            currentPhoto?.formatted_size ||
                                            formatFileSize(
                                                currentPhoto?.size || 0,
                                            )
                                        }}
                                        •
                                        {{ currentPhoto?.width }} ×
                                        {{ currentPhoto?.height }}
                                        •
                                        {{
                                            currentIndex + 1
                                        }}
                                        / {{ photos.length }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Zoom Controls -->
                                    <Button variant="ghost" size="icon" @click="zoomOut" :disabled="zoomLevel <= 0.5">
                                        <ZoomOut class="h-5 w-5 text-white" />
                                    </Button>
                                    <span class="text-sm text-white min-w-12 text-center">
                                        {{ Math.round(zoomLevel * 100) }}%
                                    </span>
                                    <Button variant="ghost" size="icon" @click="zoomIn" :disabled="zoomLevel >= 3">
                                        <ZoomIn class="h-5 w-5 text-white" />
                                    </Button>

                                    <!-- Metadata Toggle -->
                                    <Button variant="ghost" size="icon" @click="toggleMetadata">
                                        <InformationCircleIcon class="h-5 w-5" :class="showMetadata
                                                ? 'text-blue-400'
                                                : 'text-white'
                                            " />
                                    </Button>

                                    <!-- Download Button -->
                                    <Button variant="ghost" size="icon" @click="handleDownload">
                                        <ArrowDownTrayIcon class="h-5 w-5 text-white" />
                                    </Button>

                                    <!-- Close Button -->
                                    <Button variant="ghost" size="icon" @click="close">
                                        <XMarkIcon class="h-5 w-5 text-white" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Main Content Area -->
                            <div class="relative flex flex-1 overflow-hidden">
                                <!-- Previous Button -->
                                <button v-if="hasPrevious" @click="goToPrevious"
                                    class="absolute left-4 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20"
                                    type="button">
                                    <ChevronLeftIcon class="h-6 w-6" />
                                </button>

                                <!-- Photo Display -->
                                <div class="flex flex-1 items-center justify-center overflow-auto">
                                    <img :src="currentPhoto?.url" :alt="currentPhoto?.title || 'Photo'
                                        " :style="{
                                            transform: `scale(${zoomLevel})`,
                                        }"
                                        class="max-h-full max-w-full rounded-lg object-contain transition-transform duration-200" />
                                </div>

                                <!-- Metadata Sidebar -->
                                <Transition enter-active-class="transition-transform duration-300"
                                    enter-from-class="translate-x-full" enter-to-class="translate-x-0"
                                    leave-active-class="transition-transform duration-300"
                                    leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                                    <div v-if="showMetadata"
                                        class="absolute right-0 top-0 h-full w-80 overflow-y-auto rounded-lg bg-black/80 p-6 backdrop-blur-md">
                                        <h4 class="mb-4 text-lg font-semibold text-white">
                                            Photo Details
                                        </h4>

                                        <!-- Basic Info -->
                                        <div class="mb-6 space-y-2">
                                            <div v-if="currentPhoto?.description">
                                                <p class="text-xs text-gray-400">
                                                    Description
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        currentPhoto.description
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-400">
                                                    Uploaded
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        formatDate(
                                                            currentPhoto?.created_at,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-400">
                                                    Views
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        currentPhoto?.views_count ||
                                                        0
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-400">
                                                    Downloads
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        currentPhoto?.downloads_count ||
                                                        0
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Tags -->
                                        <div v-if="
                                            currentPhoto?.tags &&
                                            currentPhoto.tags.length > 0
                                        " class="mb-6">
                                            <p class="mb-2 text-xs text-gray-400">
                                                Tags
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <span v-for="tag in currentPhoto.tags" :key="tag.id"
                                                    class="rounded-full bg-blue-500/20 px-3 py-1 text-xs text-blue-300">
                                                    {{ tag.name }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- EXIF Metadata -->
                                        <div v-if="currentPhoto?.metadata" class="space-y-4">
                                            <h5 class="text-sm font-semibold text-white">
                                                Camera Info
                                            </h5>

                                            <div v-if="
                                                currentPhoto.metadata
                                                    .camera_make
                                            " class="space-y-1">
                                                <p class="text-xs text-gray-400">
                                                    Camera
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        currentPhoto.metadata
                                                            .camera_make
                                                    }}
                                                    {{
                                                        currentPhoto.metadata
                                                            .camera_model
                                                    }}
                                                </p>
                                            </div>

                                            <div v-if="
                                                currentPhoto.metadata.lens
                                            " class="space-y-1">
                                                <p class="text-xs text-gray-400">
                                                    Lens
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        currentPhoto.metadata
                                                            .lens
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div v-if="
                                                    currentPhoto.metadata
                                                        .focal_length
                                                ">
                                                    <p class="text-xs text-gray-400">
                                                        Focal Length
                                                    </p>
                                                    <p class="text-sm text-white">
                                                        {{
                                                            currentPhoto.metadata
                                                                .focal_length
                                                        }}
                                                    </p>
                                                </div>
                                                <div v-if="
                                                    currentPhoto.metadata
                                                        .aperture
                                                ">
                                                    <p class="text-xs text-gray-400">
                                                        Aperture
                                                    </p>
                                                    <p class="text-sm text-white">
                                                        {{
                                                            currentPhoto.metadata
                                                                .aperture
                                                        }}
                                                    </p>
                                                </div>
                                                <div v-if="
                                                    currentPhoto.metadata
                                                        .shutter_speed
                                                ">
                                                    <p class="text-xs text-gray-400">
                                                        Shutter Speed
                                                    </p>
                                                    <p class="text-sm text-white">
                                                        {{
                                                            currentPhoto.metadata
                                                                .shutter_speed
                                                        }}
                                                    </p>
                                                </div>
                                                <div v-if="
                                                    currentPhoto.metadata
                                                        .iso
                                                ">
                                                    <p class="text-xs text-gray-400">
                                                        ISO
                                                    </p>
                                                    <p class="text-sm text-white">
                                                        {{
                                                            currentPhoto.metadata
                                                                .iso
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div v-if="
                                                currentPhoto.metadata
                                                    .taken_at
                                            ">
                                                <p class="text-xs text-gray-400">
                                                    Taken
                                                </p>
                                                <p class="text-sm text-white">
                                                    {{
                                                        formatDate(
                                                            currentPhoto.metadata
                                                                .taken_at,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Keyboard Shortcuts -->
                                        <div class="mt-6 border-t border-white/10 pt-4">
                                            <h5 class="mb-2 text-xs font-semibold text-gray-400">
                                                Keyboard Shortcuts
                                            </h5>
                                            <div class="space-y-1 text-xs text-gray-500">
                                                <p>← → : Navigate</p>
                                                <p>+ - : Zoom</p>
                                                <p>0 : Reset zoom</p>
                                                <p>I : Toggle info</p>
                                                <p>Esc : Close</p>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>

                                <!-- Next Button -->
                                <button v-if="hasNext" @click="goToNext"
                                    class="absolute right-4 top-1/2 z-10 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-sm transition-colors hover:bg-white/20"
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
