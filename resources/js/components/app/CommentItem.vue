<script setup lang="ts">
import type { Comment, ReactionType } from '@/types';
import { useCommentBroadcasting } from '@/composables/useCommentBroadcasting';
import axios from 'axios';
import { ChevronDown, ChevronRight, MessageCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import ReactionPicker from './ReactionPicker.vue';

const props = withDefaults(
    defineProps<{
        comment: Comment;
        canDelete?: boolean;
        postId: number;
        maxDepth?: number;
    }>(),
    {
        canDelete: false,
        maxDepth: 5,
    }
);

const emit = defineEmits<{
    delete: [];
    update: [commentText: string];
    'reactions-updated': [reactions: Comment['reactions']];
    'reply-added': [reply: Comment];
}>();

const showFull = ref(false);
const isEditing = ref(false);
const editText = ref('');
const characterLimit = 100;

// Reply functionality
const isReplying = ref(false);
const replyText = ref('');
const isSubmittingReply = ref(false);

// Nested replies management
const showReplies = ref(true);
const localReplies = ref<Comment[]>(props.comment.replies || []);
const isLoadingMoreReplies = ref(false);
const repliesPage = ref(1);

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

// Calculate indentation based on depth
const indentationClass = computed(() => {
    const depth = props.comment.depth || 0;
    // Max indentation at depth 5
    const effectiveDepth = Math.min(depth, 5);
    return effectiveDepth > 0 ? `ml-${effectiveDepth * 4}` : '';
});

const canReply = computed(() => {
    const depth = props.comment.depth || 0;
    return depth < props.maxDepth;
});

const hasReplies = computed(() => {
    return localReplies.value && localReplies.value.length > 0;
});

const toggleShowFull = () => {
    showFull.value = !showFull.value;
};

const handleDelete = () => {
    const repliesCount = props.comment.replies_count || 0;
    const message =
        repliesCount > 0
            ? `Are you sure you want to delete this comment and its ${repliesCount} ${repliesCount === 1 ? 'reply' : 'replies'}?`
            : 'Are you sure you want to delete this comment?';

    if (confirm(message)) {
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

// Reply handlers
const startReply = () => {
    isReplying.value = true;
};

const cancelReply = () => {
    isReplying.value = false;
    replyText.value = '';
};

const submitReply = async () => {
    if (!replyText.value.trim() || isSubmittingReply.value) return;

    isSubmittingReply.value = true;

    try {
        const response = await axios.post(`/post/${props.postId}/comment`, {
            comment: replyText.value,
            parent_id: props.comment.id,
        });

        const newReply = response.data.comment as Comment;

        // Add reply to local list
        localReplies.value.push(newReply);

        // Ensure replies are visible
        showReplies.value = true;

        replyText.value = '';
        isReplying.value = false;

        emit('reply-added', newReply);
    } catch (error: any) {
        console.error('Error submitting reply:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Failed to submit reply. Please try again.');
        }
    } finally {
        isSubmittingReply.value = false;
    }
};

const toggleReplies = () => {
    showReplies.value = !showReplies.value;
};

const loadMoreReplies = async () => {
    if (isLoadingMoreReplies.value) return;

    isLoadingMoreReplies.value = true;
    const nextPage = repliesPage.value + 1;

    try {
        const response = await axios.get(`/comment/${props.comment.id}/replies`, {
            params: {
                page: nextPage,
                per_page: 5,
            },
        });

        const newReplies = response.data.data as Comment[];
        localReplies.value.push(...newReplies);
        repliesPage.value = nextPage;
    } catch (error) {
        console.error('Error loading more replies:', error);
    } finally {
        isLoadingMoreReplies.value = false;
    }
};

const deleteReply = async (replyId: number) => {
    try {
        await axios.delete(`/comment/${replyId}`);

        // Remove from local list
        const index = localReplies.value.findIndex((r) => r.id === replyId);
        if (index !== -1) {
            localReplies.value.splice(index, 1);
        }
    } catch (error) {
        console.error('Error deleting reply:', error);
        alert('Failed to delete reply. Please try again.');
    }
};

const updateReply = async (replyId: number, commentText: string) => {
    try {
        const response = await axios.put(`/comment/${replyId}`, {
            comment: commentText,
        });

        const updatedReply = response.data.comment as Comment;

        // Update in local list
        const index = localReplies.value.findIndex((r) => r.id === replyId);
        if (index !== -1) {
            localReplies.value[index] = updatedReply;
        }
    } catch (error) {
        console.error('Error updating reply:', error);
        alert('Failed to update reply. Please try again.');
    }
};
</script>

<template>
    <div class="group py-2" :class="indentationClass">
        <div class="flex gap-2">
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
                    <span v-if="isEdited" class="cursor-help text-gray-400"
                        :title="`Last edited: ${formattedUpdatedAt}`">
                        (edited)
                    </span>

                    <!-- Reply Button -->
                    <button v-if="canReply && !isEditing && !isReplying" @click="startReply"
                        class="flex items-center gap-1 font-semibold text-indigo-600 transition-opacity hover:underline">
                        <MessageCircle :size="12" />
                        Reply
                    </button>

                    <button v-if="canDelete && !isEditing" @click="startEdit"
                        class="font-semibold text-indigo-600 opacity-0 transition-opacity hover:underline group-hover:opacity-100">
                        Edit
                    </button>
                    <button v-if="canDelete && !isEditing" @click="handleDelete"
                        class="font-semibold text-red-600 opacity-0 transition-opacity hover:underline group-hover:opacity-100">
                        Delete
                    </button>

                    <!-- Toggle Replies Button -->
                    <button v-if="hasReplies && !isEditing" @click="toggleReplies"
                        class="flex items-center gap-1 font-semibold text-gray-600 hover:text-gray-800">
                        <component :is="showReplies ? ChevronDown : ChevronRight" :size="14" />
                        {{ localReplies.length }} {{ localReplies.length === 1 ? 'reply' : 'replies' }}
                    </button>
                </div>

                <!-- Reaction Picker -->
                <div class="mt-2 px-3">
                    <ReactionPicker :current-reaction="localReactions.current_user_reaction"
                        :total-reactions="localReactions.total" @react="handleReaction" />
                </div>

                <!-- Reply Input -->
                <div v-if="isReplying" class="mt-3 flex gap-2 px-3">
                    <input v-model="replyText" type="text" placeholder="Write a reply..."
                        class="flex-1 rounded-full border border-gray-300 bg-white px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        @keyup.enter="submitReply" @keydown.escape="cancelReply" :disabled="isSubmittingReply" />
                    <button @click="submitReply" :disabled="!replyText.trim() || isSubmittingReply"
                        class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="isSubmittingReply">Posting...</span>
                        <span v-else>Reply</span>
                    </button>
                    <button @click="cancelReply"
                        class="rounded-full bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition-all hover:bg-gray-400">
                        Cancel
                    </button>
                </div>

                <!-- Nested Replies -->
                <div v-if="hasReplies && showReplies" class="mt-2">
                    <CommentItem v-for="reply in localReplies" :key="reply.id" :comment="reply"
                        :can-delete="reply.can_delete" :post-id="postId" :max-depth="maxDepth"
                        @delete="deleteReply(reply.id)" @update="(text) => updateReply(reply.id, text)" />

                    <!-- Load More Replies -->
                    <button v-if="comment.has_more_replies && !isLoadingMoreReplies" @click="loadMoreReplies"
                        class="ml-12 mt-2 text-xs font-semibold text-indigo-600 hover:underline">
                        Load more replies
                    </button>
                    <span v-if="isLoadingMoreReplies" class="ml-12 mt-2 text-xs text-gray-500">
                        Loading...
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
