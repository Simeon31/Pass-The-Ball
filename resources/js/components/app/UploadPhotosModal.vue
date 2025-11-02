<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as uploadPhotos } from '@/routes/gallery/photos';
import type { Album } from '@/types';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { Image, Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    album: Album;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

interface PhotoPreview {
    file: File;
    preview: string;
    title: string;
    description: string;
    tags: string;
}

const form = useForm({
    photos: [] as File[],
    titles: [] as string[],
    descriptions: [] as string[],
    tags: [] as string[][],
});

const photoPreviews = ref<PhotoPreview[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const photoCount = computed(() => photoPreviews.value.length);
const canAddMore = computed(() => photoCount.value < 20);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (!isOpen) {
            resetForm();
        }
    },
);

function close() {
    emit('update:isOpen', false);
}

function resetForm() {
    form.reset();
    photoPreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.preview);
    });
    photoPreviews.value = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (files) {
        addFiles(Array.from(files));
    }
}

function handleDrop(event: DragEvent) {
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (files) {
        addFiles(Array.from(files));
    }
}

function handleDragOver(event: DragEvent) {
    event.preventDefault();
    isDragging.value = true;
}

function handleDragLeave() {
    isDragging.value = false;
}

function addFiles(files: File[]) {
    // Filter only images
    const imageFiles = files.filter((file) => file.type.startsWith('image/'));

    if (imageFiles.length === 0) {
        alert('Please select image files only');
        return;
    }

    // Check total count
    const totalCount = photoCount.value + imageFiles.length;
    if (totalCount > 20) {
        alert(`You can upload a maximum of 20 photos. You can add ${20 - photoCount.value} more.`);
        return;
    }

    // Check file sizes
    const oversizedFiles = imageFiles.filter(
        (file) => file.size > 10 * 1024 * 1024,
    );
    if (oversizedFiles.length > 0) {
        alert('Some files exceed the 10MB limit and will be skipped');
    }

    // Add valid files
    const validFiles = imageFiles.filter(
        (file) => file.size <= 10 * 1024 * 1024,
    );

    validFiles.forEach((file) => {
        const preview: PhotoPreview = {
            file,
            preview: URL.createObjectURL(file),
            title: '',
            description: '',
            tags: '',
        };
        photoPreviews.value.push(preview);
    });

    // Reset file input
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function removePhoto(index: number) {
    URL.revokeObjectURL(photoPreviews.value[index].preview);
    photoPreviews.value.splice(index, 1);
}

function submit() {
    // Prepare form data
    form.photos = photoPreviews.value.map((p) => p.file);
    form.titles = photoPreviews.value.map((p) => p.title);
    form.descriptions = photoPreviews.value.map((p) => p.description);
    form.tags = photoPreviews.value.map((p) =>
        p.tags
            .split(',')
            .map((t) => t.trim())
            .filter((t) => t.length > 0),
    );

    form.post(uploadPhotos.url({ album: props.album.slug }), {
        onSuccess: () => {
            resetForm();
            close();
        },
    });
}
</script>

<template>
    <TransitionRoot :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="close">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/25" aria-hidden="true" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <!-- Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                        Upload Photos to {{ album.title }}
                                    </DialogTitle>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Max 20 photos, 10MB each
                                    </p>
                                </div>
                                <button @click="close" type="button"
                                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-500">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Content -->
                            <form @submit.prevent="submit" class="space-y-4">
                                <!-- Drag & Drop Zone -->
                                <div v-if="canAddMore" @drop.prevent="handleDrop" @dragover.prevent="handleDragOver"
                                    @dragleave="handleDragLeave" :class="[
                                        'flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed px-4 py-8 transition-colors',
                                        isDragging
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50',
                                    ]" @click="fileInputRef?.click()">
                                    <Upload class="h-12 w-12 text-gray-400" />
                                    <div class="text-center">
                                        <p class="text-sm font-medium text-gray-700">
                                            Click to upload or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            PNG, JPG, WEBP up to 10MB
                                        </p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        {{ photoCount }} / 20 photos
                                    </p>
                                    <input ref="fileInputRef" type="file" @change="handleFileSelect" class="hidden"
                                        multiple accept="image/*" />
                                </div>

                                <!-- Photo Previews -->
                                <div v-if="photoCount > 0" class="max-h-96 space-y-4 overflow-y-auto">
                                    <div v-for="(photo, index) in photoPreviews" :key="index"
                                        class="rounded-lg border border-gray-200 p-4">
                                        <div class="flex gap-4">
                                            <!-- Thumbnail -->
                                            <div
                                                class="relative h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                                <img :src="photo.preview" :alt="`Photo ${index + 1}`"
                                                    class="h-full w-full object-cover" />
                                                <button @click="removePhoto(index)" type="button"
                                                    class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white shadow-lg transition-colors hover:bg-red-600">
                                                    <XMarkIcon class="h-4 w-4" />
                                                </button>
                                            </div>

                                            <!-- Metadata Inputs -->
                                            <div class="flex-1 space-y-2">
                                                <div>
                                                    <Label :for="`title-${index}`">Title (optional)</Label>
                                                    <Input :id="`title-${index}`" v-model="photo.title" type="text"
                                                        placeholder="Photo title" :disabled="form.processing
                                                            " />
                                                </div>
                                                <div>
                                                    <Label :for="`description-${index}`">Description
                                                        (optional)</Label>
                                                    <Input :id="`description-${index}`" v-model="photo.description
                                                        " type="text" placeholder="Photo description" :disabled="form.processing
                                                            " />
                                                </div>
                                                <div>
                                                    <Label :for="`tags-${index}`">Tags (optional)</Label>
                                                    <Input :id="`tags-${index}`" v-model="photo.tags" type="text"
                                                        placeholder="Comma-separated tags" :disabled="form.processing
                                                            " />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Messages -->
                                <div v-if="form.errors.photos" class="rounded-lg bg-red-50 p-3">
                                    <p class="text-sm text-red-600">
                                        {{ form.errors.photos }}
                                    </p>
                                </div>

                                <!-- Footer -->
                                <div class="flex justify-end gap-2 pt-4">
                                    <Button type="button" @click="close" variant="outline" :disabled="form.processing">
                                        Cancel
                                    </Button>
                                    <Button type="submit" :disabled="form.processing || photoCount === 0
                                        ">
                                        {{
                                            form.processing
                                                ? 'Uploading...'
                                                : `Upload ${photoCount} ${photoCount === 1 ? 'Photo' : 'Photos'}`
                                        }}
                                    </Button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
