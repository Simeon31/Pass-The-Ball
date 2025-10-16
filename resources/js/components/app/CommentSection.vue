<script setup lang="ts">
import type { Comment } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import CommentItem from './CommentItem.vue';

const props = defineProps<{
    postId: number;
    comments: Comment[];
    totalComments: number;
}>();

const emit = defineEmits<{
    'comment-added': [comment: Comment];
    'comment-deleted': [commentId: number];
    'comments-loaded': [comments: Comment[]];
}>();

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const commentText = ref('');
const isSubmitting = ref(false);
const isLoadingMore = ref(false);
const currentPage = ref(1);
const localComments = ref<Comment[]>([...props.comments]);

const hasMoreComments = computed(() => localComments.value.length < props.totalComments);

const submitComment = async () => {
    if (!commentText.value.trim() || isSubmitting.value) return;

    isSubmitting.value = true;

    try {
        const response = await axios.post(`/post/${props.postId}/comment`, {
            comment: commentText.value,
        });

        const newComment = response.data.comment as Comment;

        // Add to the top of the list (most recent first)
        localComments.value.unshift(newComment);

        commentText.value = '';
        emit('comment-added', newComment);
    } catch (error) {
        console.error('Error submitting comment:', error);
        alert('Failed to submit comment. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const loadMoreComments = async () => {
    if (isLoadingMore.value || !hasMoreComments.value) return;

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    try {
        const response = await axios.get(`/post/${props.postId}/comments`, {
            params: {
                page: nextPage,
                per_page: 5,
            },
        });

        const newComments = response.data.data as Comment[];

        // Add older comments to the end
        localComments.value.push(...newComments);
        currentPage.value = nextPage;

        emit('comments-loaded', newComments);
    } catch (error) {
        console.error('Error loading comments:', error);
    } finally {
        isLoadingMore.value = false;
    }
};

const deleteComment = async (commentId: number) => {
    try {
        await axios.delete(`/comment/${commentId}`);

        // Remove from local list
        const index = localComments.value.findIndex(c => c.id === commentId);
        if (index !== -1) {
            localComments.value.splice(index, 1);
        }

        emit('comment-deleted', commentId);
    } catch (error) {
        console.error('Error deleting comment:', error);
        alert('Failed to delete comment. Please try again.');
    }
};

const canDeleteComment = (comment: Comment) => {
    // User can delete their own comment or if they own the post
    return authUser.value.id === comment.user.id;
};

// Method to add comment from external source (WebSocket)
// Returns true if comment was added, false if it was a duplicate
const addCommentFromBroadcast = (comment: Comment): boolean => {
    // Check if comment already exists
    if (!localComments.value.find(c => c.id === comment.id)) {
        localComments.value.unshift(comment);
        return true; // Comment was added
    }
    return false; // Comment already exists
};

// Expose method for parent components
defineExpose({
    addCommentFromBroadcast,
});
</script>

<template>
    <div class="border-t pt-3">
        <!-- Comment Input -->
        <div class="mb-3 flex gap-2">
            <img :src="authUser.profile_picture_url || 'https://avatar.iran.liara.run/public/'" alt="Your avatar"
                class="h-8 w-8 flex-shrink-0 rounded-full border object-cover" />
            <div class="flex flex-1 items-center gap-2">
                <input v-model="commentText" type="text" placeholder="Write a comment..."
                    class="flex-1 rounded-full border border-gray-300 bg-gray-100 px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    @keyup.enter="submitComment" :disabled="isSubmitting" />
                <button @click="submitComment" :disabled="!commentText.trim() || isSubmitting"
                    class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span v-if="isSubmitting">Posting...</span>
                    <span v-else>Post</span>
                </button>
            </div>
        </div>

        <!-- Comments Count -->
        <div v-if="totalComments > 0" class="mb-2 text-sm font-semibold text-gray-600">
            {{ totalComments }} {{ totalComments === 1 ? 'Comment' : 'Comments' }}
        </div>

        <!-- Comments List -->
        <div v-if="localComments.length > 0" class="space-y-1">
            <CommentItem v-for="comment in localComments" :key="comment.id" :comment="comment"
                :can-delete="canDeleteComment(comment)" @delete="deleteComment(comment.id)" />
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreComments" class="mt-3">
            <button @click="loadMoreComments" :disabled="isLoadingMore"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                <span v-if="isLoadingMore">Loading...</span>
                <span v-else>Load More Comments</span>
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="localComments.length === 0 && totalComments === 0" class="py-4 text-center text-sm text-gray-500">
            No comments yet. Be the first to comment!
        </div>
    </div>
</template>
