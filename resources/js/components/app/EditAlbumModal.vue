<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update as updateAlbum } from '@/routes/gallery/albums';
import type { Album, AlbumVisibility } from '@/types';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { Image } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
    album: Album;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const form = useForm({
    title: props.album.title,
    description: props.album.description || '',
    visibility: props.album.visibility,
    cover: null as File | null,
});

const coverPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            // Reset form with album data
            form.title = props.album.title;
            form.description = props.album.description || '';
            form.visibility = props.album.visibility;
            form.cover = null;
            if (coverPreview.value) {
                URL.revokeObjectURL(coverPreview.value);
                coverPreview.value = null;
            }
        }
    },
);

function close() {
    emit('update:isOpen', false);
}

function handleCoverSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Cover image must be less than 5MB');
        return;
    }

    form.cover = file;

    // Create preview
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
    }
    coverPreview.value = URL.createObjectURL(file);
}

function removeCover() {
    form.cover = null;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
        coverPreview.value = null;
    }
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function submit() {
    form.put(updateAlbum.url({ album: props.album.slug }), {
        onSuccess: () => {
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
                            class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <!-- Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                    Edit Album
                                </DialogTitle>
                                <button @click="close" type="button"
                                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-500">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Content -->
                            <form @submit.prevent="submit" class="space-y-4">
                                <!-- Title -->
                                <div>
                                    <Label for="title">Title *</Label>
                                    <Input id="title" v-model="form.title" type="text" placeholder="Enter album title"
                                        :disabled="form.processing" required />
                                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <Label for="description">Description (optional)</Label>
                                    <textarea id="description" v-model="form.description"
                                        placeholder="Describe your album..." rows="3" :disabled="form.processing"
                                        class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" />
                                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.description }}
                                    </p>
                                </div>

                                <!-- Visibility -->
                                <div>
                                    <Label for="visibility">Visibility</Label>
                                    <select id="visibility" v-model="form.visibility" :disabled="form.processing"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                        <option value="public">
                                            Public - Anyone can view
                                        </option>
                                        <option value="private">
                                            Private - Only you can view
                                        </option>
                                        <option value="followers_only">
                                            Followers Only
                                        </option>
                                        <option value="link_only">
                                            Link Only - Anyone with the link
                                        </option>
                                    </select>
                                    <p v-if="form.errors.visibility" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.visibility }}
                                    </p>
                                </div>

                                <!-- Cover Image -->
                                <div>
                                    <Label>Cover Image</Label>
                                    <div class="mt-2">
                                        <!-- Current or New Cover Preview -->
                                        <div v-if="coverPreview || album.cover_url"
                                            class="relative mb-3 aspect-video overflow-hidden rounded-lg">
                                            <img :src="coverPreview || album.cover_url || ''" alt="Cover preview"
                                                class="h-full w-full object-cover" />
                                            <button @click="removeCover" type="button"
                                                class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white transition-colors hover:bg-red-600">
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>

                                        <!-- Upload Button -->
                                        <label v-if="!coverPreview && !album.cover_url"
                                            class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 px-4 py-8 text-sm font-medium text-gray-700 transition-colors hover:border-gray-400 hover:bg-gray-50">
                                            <Image class="h-6 w-6" />
                                            <span>Choose Cover Image</span>
                                            <input ref="fileInputRef" type="file" @change="handleCoverSelect"
                                                class="hidden" accept="image/*" />
                                        </label>
                                        <label v-else-if="!coverPreview"
                                            class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                            <Image class="h-5 w-5" />
                                            <span>Change Cover Image</span>
                                            <input ref="fileInputRef" type="file" @change="handleCoverSelect"
                                                class="hidden" accept="image/*" />
                                        </label>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Recommended: 1200x675px, max 5MB
                                        </p>
                                    </div>
                                    <p v-if="form.errors.cover" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.cover }}
                                    </p>
                                </div>

                                <!-- Footer -->
                                <div class="flex justify-end gap-2 pt-4">
                                    <Button type="button" @click="close" variant="outline" :disabled="form.processing">
                                        Cancel
                                    </Button>
                                    <Button type="submit" :disabled="form.processing">
                                        {{
                                            form.processing
                                                ? 'Updating...'
                                                : 'Update Album'
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
