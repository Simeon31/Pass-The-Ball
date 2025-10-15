<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { nextTick, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { create as createPost } from '@/routes/post';

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

const { value: postBody, textareaRef, resize: resizeTextarea } = useAutoResizingTextarea();
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
            newPostForm.reset()
            resetComposer()
        }
    });
}

</script>

<template>
    <div class="p-4 bg-white rounded-lg border mb-3">
        <textarea ref="textareaRef" v-model="postBody" @focus="activatePostCreation" @click="activatePostCreation"
            @input="resizeTextarea"
            class="selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base text-gray-700 shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive overflow-hidden mb-3"
            rows="1" placeholder="Click here to create a new post"></textarea>
        <div v-if="postCreation" class="flex justify-between items-center w-full gap-4">
            <div class="relative file-wrapper">
                <Button type="button" class="relative">
                    Attach files
                    <input ref="fileInputRef" type="file"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" multiple
                        accept="image/*,video/*" />
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
    transition: transform 120ms ease, filter 120ms ease, box-shadow 120ms ease;
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