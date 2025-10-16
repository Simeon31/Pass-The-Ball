<script setup lang="ts">
import {
    ClassicEditor,
    Bold,
    Essentials,
    Italic,
    Paragraph,
    Undo,
    Link,
    List,
    BlockQuote,
    Heading,
} from 'ckeditor5';
import { onMounted, onBeforeUnmount, ref, nextTick } from 'vue';

import 'ckeditor5/ckeditor5.css';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLDivElement | null>(null);
let editorInstance: ClassicEditor | null = null;
let isDestroying = false;

onMounted(async () => {
    await nextTick();
    if (!editorRef.value || isDestroying) return;

    try {
        const editor = await ClassicEditor.create(editorRef.value, {
            licenseKey: 'GPL',
            plugins: [
                Essentials,
                Paragraph,
                Bold,
                Italic,
                Undo,
                Link,
                List,
                BlockQuote,
                Heading,
            ],
            toolbar: {
                items: [
                    'undo',
                    'redo',
                    '|',
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'link',
                    '|',
                    'bulletedList',
                    'numberedList',
                    '|',
                    'blockQuote',
                ],
            },
            placeholder: props.placeholder || "What's on your mind?",
            initialData: props.modelValue || '',
        });

        if (isDestroying) {
            editor.destroy();
            return;
        }

        editorInstance = editor;

        // Listen for changes
        editor.model.document.on('change:data', () => {
            if (!isDestroying && editorInstance) {
                try {
                    const data = editor.getData();
                    emit('update:modelValue', data);
                } catch (error) {
                    console.error('Error getting editor data:', error);
                }
            }
        });

        // Handle initial disabled state
        if (props.disabled && !isDestroying) {
            try {
                editor.enableReadOnlyMode('disabled');
            } catch (error) {
                console.error('Error setting read-only mode:', error);
            }
        }
    } catch (error) {
        console.error('Error initializing CKEditor:', error);
    }
});

// Cleanup on unmount
onBeforeUnmount(() => {
    isDestroying = true;
    if (editorInstance) {
        try {
            editorInstance.destroy();
        } catch (error) {
            console.error('Error destroying editor:', error);
        } finally {
            editorInstance = null;
        }
    }
});
</script>

<template>
    <div>
        <div ref="editorRef" class="ckeditor-wrapper"></div>
    </div>
</template>

<style>
/* CKEditor custom styling to match the app's theme */
.ck-editor__editable {
    min-height: 200px;
    border-radius: 0.5rem;
}

.ck.ck-editor__main>.ck-editor__editable {
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

.ck.ck-editor__main>.ck-editor__editable:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
}

.ck.ck-toolbar {
    border: 1px solid #d1d5db;
    border-bottom: none;
    background-color: #f9fafb;
    border-radius: 0.5rem 0.5rem 0 0;
}

.ck.ck-toolbar .ck-toolbar__items {
    gap: 0.25rem;
}

.ck-editor {
    width: 100%;
}

.ck-content {
    font-size: 0.875rem;
    line-height: 1.5;
}

.ck-content p {
    margin-bottom: 0.5rem;
}

.ck-content h1,
.ck-content h2,
.ck-content h3 {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.ck-content ul,
.ck-content ol {
    margin-left: 1.5rem;
    margin-bottom: 0.5rem;
}

.ck-content blockquote {
    border-left: 4px solid #d1d5db;
    padding-left: 1rem;
    margin-left: 0;
    font-style: italic;
    color: #6b7280;
}
</style>
