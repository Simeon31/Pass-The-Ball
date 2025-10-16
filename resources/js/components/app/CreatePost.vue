<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { create as createPost } from '@/routes/post';
import { useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';

const postCreation = ref(false);
const newPostForm = useForm({
    body: '',
    attachments: null,
});

function useAutoResizingTextarea(initialValue = '') {
    const value = ref(initialValue);
    const textareaRef = ref<HTMLTextAreaElement | null>(null);

    const resize = () => {
        const el = textareaRef.value;
        if (!el) return;

        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    };

    onMounted(() => {
        nextTick(() => resize());
    });

    watch(value, () => {
        nextTick(() => resize());
    });

    return { value, textareaRef, resize };
}

const {
    value: postBody,
    textareaRef,
    resize: resizeTextarea,
} = useAutoResizingTextarea();
const fileInputRef = ref<HTMLInputElement | null>(null);

// Syncning postBody with the form body
watch(postBody, (newValue) => {
    newPostForm.body = newValue;
});

const activatePostCreation = () => {
    if (!postCreation.value) {
        postCreation.value = true;
    }

    nextTick(() => resizeTextarea());
};

watch(postCreation, (isActive) => {
    if (isActive) {
        nextTick(() => resizeTextarea());
    }
});

const resetComposer = () => {
    postBody.value = '';

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }

    postCreation.value = false;

    nextTick(() => resizeTextarea());
};

function submit() {
    newPostForm.post(createPost.url(), {
        onSuccess: () => {
            newPostForm.reset();
            resetComposer();
        },
    });
}
</script>

<template>
    <div class="mb-3 rounded-lg border bg-white p-4">
        <textarea
            ref="textareaRef"
            v-model="postBody"
            @focus="activatePostCreation"
            @click="activatePostCreation"
            @input="resizeTextarea"
            class="mb-3 w-full min-w-0 overflow-hidden rounded-md border border-input bg-transparent px-3 py-2 text-base text-gray-700 shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
            rows="1"
            placeholder="Click here to create a new post"
        ></textarea>
        <div
            v-if="postCreation"
            class="flex w-full items-center justify-between gap-4"
        >
            <div class="file-wrapper relative">
                <Button type="button" class="relative">
                    Attach files
                    <input
                        ref="fileInputRef"
                        type="file"
                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                        multiple
                        accept="image/*,video/*"
                    />
                </Button>
            </div>

            <div>
                <Button type="submit" class="relative z-0" @click="submit">
                    Post
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.file-wrapper button {
    transition:
        transform 120ms ease,
        filter 120ms ease,
        box-shadow 120ms ease;
}

.file-wrapper:hover button,
.file-wrapper:focus-within button {
    transform: translateY(-2px);
    filter: brightness(0.95);
    box-shadow: 0 6px 18px rgba(16, 24, 40, 0.08);
}

.file-wrapper:active button {
    transform: translateY(0);
    filter: brightness(0.9);
    box-shadow: none;
}
</style>
