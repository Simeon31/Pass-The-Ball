<script setup lang="ts">
import CKEditor from '@/components/ui/CKEditor.vue';
import { update as updatePost } from '@/routes/post';
import type { Post, PostAttachment } from '@/types';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { PaperClipIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AttachmentPreview from './AttachmentPreview.vue';

const props = defineProps<{
    post: Post;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
}>();

const form = useForm({
    body: props.post.body || '',
    attachments: [] as File[],
    deleted_attachments: [] as number[],
});

const editorKey = ref(0);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Existing attachments from the post
const existingAttachments = ref<PostAttachment[]>([...props.post.attachments]);

// New attachments for preview
const newAttachments = ref<PostAttachment[]>([]);

// Combined attachments for display
const allAttachments = ref<PostAttachment[]>([]);

// Reset form when modal opens with new post data
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            form.body = props.post.body || '';
            form.attachments = [];
            form.deleted_attachments = [];

            existingAttachments.value = [...props.post.attachments];
            newAttachments.value = [];
            updateAllAttachments();

            form.clearErrors();
            editorKey.value++; // Increment key to force re-render

            // Reset file input
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        }
    },
);

// Update combined attachments list
function updateAllAttachments() {
    allAttachments.value = [
        ...existingAttachments.value,
        ...newAttachments.value,
    ];
}

const closeModal = () => {
    // Clean up new attachment URLs
    newAttachments.value.forEach((attachment) => {
        URL.revokeObjectURL(attachment.url);
    });

    emit('update:isOpen', false);
};

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = target.files;

    if (!files || files.length === 0) return;

    // Check total attachment count
    const totalFiles = allAttachments.value.length + files.length;
    if (totalFiles > 10) {
        alert('You can only have a maximum of 10 files per post.');
        return;
    }

    // Add files to form
    const newFiles = Array.from(files);
    form.attachments = [...form.attachments, ...newFiles];

    // Create preview attachments
    newFiles.forEach((file) => {
        const previewAttachment: PostAttachment = {
            id: Math.random(), // Temporary ID for preview
            name: file.name,
            mime_type: file.type,
            size: file.size,
            url: URL.createObjectURL(file),
            created_at: new Date().toISOString(),
        };
        newAttachments.value.push(previewAttachment);
    });

    updateAllAttachments();

    // Reset file input
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function removeAttachment(index: number) {
    const attachment = allAttachments.value[index];
    const existingIndex = existingAttachments.value.findIndex(
        (a) => a.id === attachment.id,
    );

    if (existingIndex !== -1) {
        // Existing attachment - mark for deletion
        const existingAttachment = existingAttachments.value[existingIndex];
        if (typeof existingAttachment.id === 'number') {
            form.deleted_attachments.push(existingAttachment.id);
        }
        existingAttachments.value.splice(existingIndex, 1);
    } else {
        // New attachment - remove from arrays
        const newIndex = newAttachments.value.findIndex(
            (a) => a.id === attachment.id,
        );
        if (newIndex !== -1) {
            URL.revokeObjectURL(newAttachments.value[newIndex].url);
            newAttachments.value.splice(newIndex, 1);

            // Remove from form.attachments
            form.attachments = form.attachments.filter((_, i) => i !== newIndex);
        }
    }

    updateAllAttachments();
}

const handleSubmit = () => {
    // Use POST with _method=PUT for file uploads
    // This is necessary because PUT requests don't support multipart/form-data properly
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(updatePost.url(props.post.id), {
        forceFormData: true,
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
                            class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-4">
                                <DialogTitle as="h3" class="text-lg leading-6 font-semibold text-gray-900">
                                    Edit Post
                                </DialogTitle>
                                <button type="button"
                                    class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- User Info -->
                            <div class="mb-4 flex items-center gap-3">
                                <img :src="post.user.profile_picture_url ||
                                    'https://avatar.iran.liara.run/public/'
                                    " :alt="post.user.name"
                                    class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover" />
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
                                    <CKEditor v-if="isOpen" :key="editorKey" v-model="form.body"
                                        placeholder="What's on your mind?" :disabled="form.processing" />
                                    <p v-if="form.errors.body" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.body }}
                                    </p>
                                </div>

                                <!-- Attachment Preview -->
                                <div v-if="allAttachments.length > 0" class="mb-4">
                                    <AttachmentPreview :attachments="allAttachments" :show-preview="true"
                                        @remove="removeAttachment" />
                                </div>

                                <div class="mb-4 flex items-center gap-2">
                                    <!-- Attach Files Button -->
                                    <label
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                        <PaperClipIcon class="h-5 w-5" />
                                        <span>Add Files</span>
                                        <input ref="fileInputRef" type="file" @change="handleFileSelect" class="hidden"
                                            multiple accept="image/*,video/*,application/pdf" />
                                    </label>
                                    <span v-if="allAttachments.length > 0" class="text-sm text-gray-500">
                                        {{ allAttachments.length }} / 10 files
                                    </span>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button type="button"
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                                        @click="closeModal">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="form.processing || !form.body
                                        "
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50">
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
