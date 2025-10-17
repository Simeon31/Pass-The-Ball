<script setup lang="ts">
import type { Comment } from '@/types';
import { computed, ref } from 'vue';

const props = defineProps<{
    comment: Comment;
    canDelete?: boolean;
}>();

const emit = defineEmits<{
    delete: [];
    update: [commentText: string];
}>();

const showFull = ref(false);
const isEditing = ref(false);
const editText = ref('');
const characterLimit = 100;

const isLongComment = computed(() => props.comment.comment.length > characterLimit);
const displayedComment = computed(() => {
    if (!isLongComment.value || showFull.value) {
        return props.comment.comment;
    }
    return props.comment.comment.substring(0, characterLimit) + '...';
});

const isEdited = computed(() => props.comment.created_at !== props.comment.updated_at);

const formattedCreatedAt = computed(() => {
    const date = new Date(props.comment.created_at);
    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const formattedUpdatedAt = computed(() => {
    if (!isEdited.value) return '';
    const date = new Date(props.comment.updated_at);
    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const toggleShowFull = () => {
    showFull.value = !showFull.value;
};

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this comment?')) {
        emit('delete');
    }
};

const startEdit = () => {
    editText.value = props.comment.comment;
    isEditing.value = true;
};

const cancelEdit = () => {
    editText.value = '';
    isEditing.value = false;
};

const saveEdit = () => {
    if (!editText.value.trim()) return;
    emit('update', editText.value);
    isEditing.value = false;
};
</script>

<template>
    <div class="group flex gap-2 py-2">
        <img :src="comment.user.profile_picture_url || 'https://avatar.iran.liara.run/public/'" alt="User avatar"
            class="h-8 w-8 flex-shrink-0 rounded-full border object-cover" />
        <div class="flex-1">
            <!-- Normal Display Mode -->
            <div v-if="!isEditing" class="rounded-lg bg-gray-100 px-3 py-2">
                <a href="javascript:void(0)" class="text-sm font-semibold hover:underline">
                    {{ comment.user.name }}
                </a>
                <p class="mt-1 whitespace-pre-wrap break-words text-sm text-gray-800">
                    {{ displayedComment }}
                </p>
                <button v-if="isLongComment" @click="toggleShowFull"
                    class="mt-1 text-xs font-semibold text-indigo-600 hover:underline">
                    {{ showFull ? 'Show Less' : 'See More' }}
                </button>
            </div>

            <!-- Edit Mode -->
            <div v-else class="rounded-lg bg-gray-100 px-3 py-2">
                <a href="javascript:void(0)" class="text-sm font-semibold hover:underline">
                    {{ comment.user.name }}
                </a>
                <textarea v-model="editText"
                    class="mt-2 w-full resize-none rounded border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    rows="3" @keydown.escape="cancelEdit"></textarea>
                <div class="mt-2 flex gap-2">
                    <button @click="saveEdit" :disabled="!editText.trim()"
                        class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white transition-all hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Save
                    </button>
                    <button @click="cancelEdit"
                        class="rounded bg-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 transition-all hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </div>

            <div class="mt-1 flex items-center gap-3 px-3 text-xs text-gray-500">
                <span>{{ formattedCreatedAt }}</span>
                <span v-if="isEdited" class="text-gray-400 cursor-help" :title="`Last edited: ${formattedUpdatedAt}`">
                    (edited)
                </span>
                <button v-if="canDelete && !isEditing" @click="startEdit"
                    class="font-semibold text-indigo-600 opacity-0 transition-opacity hover:underline group-hover:opacity-100">
                    Edit
                </button>
                <button v-if="canDelete && !isEditing" @click="handleDelete"
                    class="font-semibold text-red-600 opacity-0 transition-opacity hover:underline group-hover:opacity-100">
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>
