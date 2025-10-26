<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import CKEditor from '@/components/ui/CKEditor.vue';
import { create as createPost } from '@/routes/post';
import type { PostAttachment } from '@/types';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { PaperClipIcon, XMarkIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import AttachmentPreview from './AttachmentPreview.vue';

const props = defineProps<{
    isOpen: boolean;
    groupId?: number;
}>();

const emit = defineEmits<{
    (e: 'update:isOpen', value: boolean): void;
}>();

const newPostForm = useForm({
    body: '',
    group_id: props.groupId,
    attachments: [] as File[],
});

// For preview purposes
const previewAttachments = ref<PostAttachment[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const editorKey = ref(0);

// AI Suggestion state
const selectedTone = ref<string>('professional');
const isLoadingSuggestion = ref(false);
const aiSuggestion = ref<string | null>(null);
const showPreviewModal = ref(false);
const suggestionError = ref<string | null>(null);
const hasGeneratedSuggestion = ref(false);

const toneOptions = [
    { value: 'professional', label: 'Professional' },
    { value: 'casual', label: 'Casual' },
    { value: 'enthusiastic', label: 'Enthusiastic' },
    { value: 'inspiring', label: 'Inspiring' },
    { value: 'humorous', label: 'Humorous' },
];

// Strip HTML tags from content for character count and AI processing
const stripHtml = (html: string): string => {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
};

const plainTextContent = computed(() => stripHtml(newPostForm.body));
const canGenerateSuggestion = computed(() => {
    return plainTextContent.value.trim().length >= 5 && !hasGeneratedSuggestion.value;
});

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            editorKey.value++; // Force re-render of CKEditor
        }
    },
);

function close() {
    emit('update:isOpen', false);
}

function resetComposer() {
    newPostForm.body = '';
    newPostForm.attachments = [];

    // Clean up object URLs
    previewAttachments.value.forEach((attachment) => {
        URL.revokeObjectURL(attachment.url);
    });
    previewAttachments.value = [];

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    // Reset AI suggestion state
    aiSuggestion.value = null;
    hasGeneratedSuggestion.value = false;
    suggestionError.value = null;
    selectedTone.value = 'professional';
}

async function generateAISuggestion() {
    if (!canGenerateSuggestion.value || isLoadingSuggestion.value) return;

    isLoadingSuggestion.value = true;
    suggestionError.value = null;

    try {
        const response = await axios.post('/api/post/suggest-content', {
            content: plainTextContent.value,
            tone: selectedTone.value,
        });

        if (response.data.success) {
            aiSuggestion.value = response.data.enhanced_content;
            showPreviewModal.value = true;
            hasGeneratedSuggestion.value = true;
        }
    } catch (error: any) {
        suggestionError.value =
            error.response?.data?.error ||
            'Failed to generate suggestion. Please try again.';
    } finally {
        isLoadingSuggestion.value = false;
    }
}

function confirmSuggestion() {
    if (aiSuggestion.value) {
        newPostForm.body = aiSuggestion.value;
        showPreviewModal.value = false;
    }
}

function rejectSuggestion() {
    showPreviewModal.value = false;
    aiSuggestion.value = null;
    hasGeneratedSuggestion.value = false;
}

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = target.files;

    if (!files || files.length === 0) return;

    // Check if adding these files would exceed the limit
    const totalFiles = newPostForm.attachments.length + files.length;
    if (totalFiles > 10) {
        alert('You can only upload a maximum of 10 files per post.');
        return;
    }

    // Add files to form
    const newFiles = Array.from(files);
    newPostForm.attachments = [...newPostForm.attachments, ...newFiles];

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
        previewAttachments.value.push(previewAttachment);
    });

    // Reset file input so the same file can be selected again if needed
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function removeAttachment(index: number) {
    // Remove from form
    newPostForm.attachments = newPostForm.attachments.filter(
        (_, i) => i !== index,
    );

    // Revoke object URL to free memory
    URL.revokeObjectURL(previewAttachments.value[index].url);

    // Remove from preview
    previewAttachments.value = previewAttachments.value.filter(
        (_, i) => i !== index,
    );
}

function submit() {
    newPostForm.post(createPost.url(), {
        onSuccess: () => {
            resetComposer();
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
                                    Create Post
                                </DialogTitle>
                                <button @click="close" type="button"
                                    class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-500">
                                    <XMarkIcon class="h-6 w-6" />
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="mt-4">
                                <CKEditor v-if="isOpen" :key="editorKey" v-model="newPostForm.body"
                                    placeholder="What's on your mind?" :disabled="newPostForm.processing" />

                                <!-- Attachment Preview -->
                                <div v-if="previewAttachments.length > 0" class="mt-4">
                                    <AttachmentPreview :attachments="previewAttachments" :show-preview="true"
                                        @remove="removeAttachment" />
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-6 flex flex-col gap-3">
                                <!-- AI Enhancement Section -->
                                <div
                                    class="flex items-center gap-3 rounded-lg border border-purple-200 bg-purple-50 p-3">
                                    <SparklesIcon class="h-5 w-5 text-purple-600" />
                                    <div class="flex flex-1 items-center gap-2">
                                        <label for="tone-select" class="text-sm font-medium text-gray-700">
                                            Tone:
                                        </label>
                                        <select id="tone-select" v-model="selectedTone"
                                            :disabled="isLoadingSuggestion || hasGeneratedSuggestion"
                                            class="rounded-md border border-gray-300 px-3 py-1 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 disabled:cursor-not-allowed disabled:bg-gray-100">
                                            <option v-for="option in toneOptions" :key="option.value"
                                                :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <Button type="button" @click="generateAISuggestion"
                                        :disabled="!canGenerateSuggestion || isLoadingSuggestion" variant="outline"
                                        class="border-purple-300 text-purple-700 hover:bg-purple-100">
                                        <SparklesIcon v-if="!isLoadingSuggestion" class="mr-2 h-4 w-4" />
                                        <span v-if="isLoadingSuggestion" class="mr-2 h-4 w-4 animate-spin">⏳</span>
                                        {{
                                            isLoadingSuggestion
                                                ? 'Generating...'
                                                : hasGeneratedSuggestion
                                                    ? 'Generated'
                                                    : 'AI Enhance'
                                        }}
                                    </Button>
                                </div>

                                <!-- Error Message -->
                                <div v-if="suggestionError"
                                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600">
                                    {{ suggestionError }}
                                </div>

                                <!-- Main Actions -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <!-- Attach Files Button -->
                                        <label
                                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                            <PaperClipIcon class="h-5 w-5" />
                                            <span>Attach Files</span>
                                            <input ref="fileInputRef" type="file" @change="handleFileSelect"
                                                class="hidden" multiple accept="image/*,video/*,application/pdf" />
                                        </label>
                                        <span v-if="previewAttachments.length > 0" class="text-sm text-gray-500">
                                            {{ previewAttachments.length }} /
                                            10 files
                                        </span>
                                    </div>

                                    <div class="flex gap-2">
                                        <Button type="button" @click="close" variant="outline"
                                            :disabled="newPostForm.processing">
                                            Cancel
                                        </Button>
                                        <Button type="submit" @click="submit" :disabled="newPostForm.processing">
                                            {{
                                                newPostForm.processing
                                                    ? 'Posting...'
                                                    : 'Post'
                                            }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- AI Suggestion Preview Modal -->
    <TransitionRoot :show="showPreviewModal" as="template">
        <Dialog as="div" class="relative z-[60]" @close="rejectSuggestion">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/40" aria-hidden="true" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                            <!-- Header -->
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-full bg-purple-100 p-2">
                                    <SparklesIcon class="h-6 w-6 text-purple-600" />
                                </div>
                                <div>
                                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                        AI-Enhanced Content
                                    </DialogTitle>
                                    <p class="text-sm text-gray-500">
                                        Review the suggested content below
                                    </p>
                                </div>
                            </div>

                            <!-- Content Preview -->
                            <div class="mt-4">
                                <div class="mb-3">
                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Original Content:
                                    </label>
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600">
                                        {{ plainTextContent }}
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">
                                        Enhanced Content ({{ selectedTone }}):
                                    </label>
                                    <div
                                        class="rounded-lg border-2 border-purple-200 bg-purple-50 p-3 text-sm text-gray-900">
                                        {{ aiSuggestion }}
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ aiSuggestion?.length || 0 }} / 200 characters
                                    </p>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="mt-6 flex justify-end gap-3">
                                <Button type="button" @click="rejectSuggestion" variant="outline">
                                    Reject
                                </Button>
                                <Button type="button" @click="confirmSuggestion"
                                    class="bg-purple-600 hover:bg-purple-700">
                                    Use This Content
                                </Button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
