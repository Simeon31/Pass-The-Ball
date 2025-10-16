<script setup lang="ts">
import CKEditor from '@/components/ui/CKEditor.vue';
import { update as updatePost } from '@/routes/post';
import type { Post } from '@/types';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    post: Post;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const form = useForm({
    body: props.post.body || '',
});

const editorKey = ref(0);

// Reset form when modal opens with new post data
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            form.body = props.post.body || '';
            form.clearErrors();
            editorKey.value++; // Increment key to force re-render
        }
    },
);

const closeModal = () => {
    emit('update:isOpen', false);
};

const handleSubmit = () => {
    form.put(updatePost.url(props.post.id), {
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <TransitionRoot :show="isOpen" as="template">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div
                    class="fixed inset-0 bg-black/30 backdrop-blur-sm"
                    aria-hidden="true"
                />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div
                    class="flex min-h-full items-center justify-center p-4 text-center"
                >
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <div
                                class="mb-4 flex items-center justify-between border-b border-gray-200 pb-4"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg leading-6 font-semibold text-gray-900"
                                >
                                    Edit Post
                                </DialogTitle>
                                <button
                                    type="button"
                                    class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    @click="closeModal"
                                >
                                    <span class="sr-only">Close</span>
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- User Info -->
                            <div class="mb-4 flex items-center gap-3">
                                <img
                                    :src="
                                        post.user.profile_picture_url ||
                                        'https://avatar.iran.liara.run/public/'
                                    "
                                    :alt="post.user.name"
                                    class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover"
                                />
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-900">{{
                                        post.user.name
                                    }}</span>
                                </div>
                            </div>

                            <form @submit.prevent="handleSubmit">
                                <div class="mb-4">
                                    <label for="post-body" class="sr-only">
                                        Post content
                                    </label>
                                    <CKEditor
                                        v-if="isOpen"
                                        :key="editorKey"
                                        v-model="form.body"
                                        placeholder="What's on your mind?"
                                        :disabled="form.processing"
                                    />
                                    <p
                                        v-if="form.errors.body"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ form.errors.body }}
                                    </p>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                        @click="closeModal"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="
                                            form.processing || !form.body
                                        "
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{
                                            form.processing
                                                ? 'Updating...'
                                                : 'Update Post'
                                        }}
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
