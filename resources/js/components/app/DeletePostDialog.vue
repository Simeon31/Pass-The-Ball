<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import type { Post } from '@/types';
import { destroy as destroyPost } from '@/routes/post';

const props = defineProps<{
    post: Post;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const form = useForm({});

const closeModal = () => {
    emit('update:isOpen', false);
};

const handleDelete = () => {
    form.delete(destroyPost.url(props.post.id), {
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <TransitionRoot :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" aria-hidden="true" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                                    <AlertTriangle class="h-6 w-6 text-red-600" />
                                </div>
                                <div class="flex-1">
                                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                        Delete Post
                                    </DialogTitle>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Are you sure you want to delete this post? This action cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <!-- User Info and Post Preview -->
                            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="mb-3 flex items-center gap-3">
                                    <img :src="post.user.profile_picture_url || 'https://avatar.iran.liara.run/public/'"
                                        :alt="post.user.name"
                                        class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover" />
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-900">{{ post.user.name }}</span>
                                        <span class="text-xs text-gray-500">{{ post.created_at }}</span>
                                    </div>
                                </div>
                                <p v-if="post.body" class="text-sm text-gray-600 line-clamp-3">
                                    {{ post.body }}
                                </p>
                                <p v-else class="text-sm italic text-gray-400">
                                    No content
                                </p>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="closeModal">
                                    Cancel
                                </button>
                                <button type="button" :disabled="form.processing"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="handleDelete">
                                    {{
                                        form.processing
                                            ? 'Deleting...'
                                            : 'Delete Post'
                                    }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
