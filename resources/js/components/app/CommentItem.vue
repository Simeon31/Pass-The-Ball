<script setup lang="ts">
import type { Comment, ReactionType } from '@/types';
import { useCommentBroadcasting } from '@/composables/useCommentBroadcasting';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ReactionPicker from './ReactionPicker.vue';

const props = defineProps<{
    comment: Comment;
    canDelete?: boolean;
}>();

const emit = defineEmits<{
    delete: [];
    update: [commentText: string];
    'reactions-updated': [reactions: Comment['reactions']];
}>();

const showFull = ref(false);
const isEditing = ref(false);
const editText = ref('');
const characterLimit = 100;

// Reactions state
const localReactions = ref(props.comment.reactions);

const handleReaction = async (type: ReactionType) => {
    try {
        const response = await axios.post(`/comment/${props.comment.id}/reaction`, {
            type,
        });

        // Update local reactions state
        localReactions.value = response.data.reactions;
        emit('reactions-updated', response.data.reactions);
    } catch (error) {
        console.error('Error reacting to comment:', error);
    }
};

// Real-time reaction updates
const { listenForReactions, disconnect } = useCommentBroadcasting(props.comment.id);

onMounted(() => {
    listenForReactions((reactions) => {
        localReactions.value = reactions;
        emit('reactions-updated', reactions);
    });
});

onUnmounted(() => {
    disconnect();
});

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

            <!-- Reaction Picker -->
            <div class="mt-2 px-3">
                <ReactionPicker :current-reaction="localReactions.current_user_reaction"
                    :total-reactions="localReactions.total" @react="handleReaction" />
            </div>
        </div>
    </div>
</template>
