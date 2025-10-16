<script setup lang="ts">
import type { Comment, Post, PostAttachment, ReactionType } from '@/types';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import { usePostBroadcasting } from '@/composables/usePostBroadcasting';
import AttachmentFullScreen from './AttachmentFullScreen.vue';
import AttachmentPreview from './AttachmentPreview.vue';
import CommentSection from './CommentSection.vue';
import DeletePostDialog from './DeletePostDialog.vue';
import EditPostModal from './EditPostModal.vue';
import PostMenu from './PostMenu.vue';
import ReactionPicker from './ReactionPicker.vue';

const props = defineProps<{
    post: Post;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Checking if the current user owns this post
const canManagePost = computed(() => authUser.value.id === props.post.user.id);

// Modal states
const isEditModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

// Attachment viewer state
const isAttachmentViewerOpen = ref(false);
const currentAttachmentIndex = ref(0);

// Comments section state
const showComments = ref(false);
const commentSectionRef = ref<InstanceType<typeof CommentSection> | null>(null);

// Reactions state
const localReactions = ref(props.post.reactions);
const totalComments = ref(props.post.comments.total);

// Refs for post content containers
const postContentRef = ref<HTMLDivElement | null>(null);
const postContentFullRef = ref<HTMLDivElement | null>(null);

// Broadcasting setup
const { listenForReactions, listenForComments, disconnect } = usePostBroadcasting(props.post.id);

function isImage(attachment: PostAttachment) {
    const mime = attachment.mime_type.split('/');
    return mime[0] === 'image';
}

const openEditModal = () => {
    isEditModalOpen.value = true;
};

const openDeleteDialog = () => {
    isDeleteDialogOpen.value = true;
};

function openAttachmentViewer(attachment: PostAttachment, index: number) {
    currentAttachmentIndex.value = index;
    isAttachmentViewerOpen.value = true;
}

const toggleComments = () => {
    showComments.value = !showComments.value;
};

// Handle reaction toggle
const handleReaction = async (type: ReactionType) => {
    try {
        const response = await axios.post(`/post/${props.post.id}/reaction`, {
            type,
        });

        // Update local reactions state
        localReactions.value = response.data.reactions;
    } catch (error) {
        console.error('Error toggling reaction:', error);
        alert('Failed to react to post. Please try again.');
    }
};

// Handle new comment added locally by current user
const handleCommentAdded = (comment: Comment) => {
    // Increment the total since a new comment was added
    totalComments.value++;
};

// Handle comment deleted
const handleCommentDeleted = (commentId: number) => {
    totalComments.value--;
};

// Function to make links in HTML content clickable
const makeLinksClickable = (element: HTMLElement | null) => {
    if (!element) return;

    const links = element.querySelectorAll('a');
    links.forEach((link) => {
        // Ensuring the link has proper attributes
        if (!link.hasAttribute('target')) {
            link.setAttribute('target', '_blank');
        }
        if (!link.hasAttribute('rel')) {
            link.setAttribute('rel', 'noopener noreferrer');
        }
        // Adding click handler to ensure navigation works
        link.addEventListener('click', (e) => {
            e.stopPropagation();
            const href = link.getAttribute('href');
            if (href && href !== '#') {
                window.open(href, '_blank', 'noopener,noreferrer');
            }
        });
    });
};

// Make links clickable after component mounts and when content updates
onMounted(() => {
    nextTick(() => {
        makeLinksClickable(postContentRef.value);
        makeLinksClickable(postContentFullRef.value);
    });

    // Setup WebSocket listeners for real-time updates
    listenForReactions((reactions) => {
        localReactions.value = reactions;
    });

    listenForComments((comment) => {
        addCommentFromBroadcast(comment);
    });
});

onUnmounted(() => {
    disconnect();
});

// Expose method for WebSocket updates
const updateReactionsFromBroadcast = (reactions: typeof localReactions.value) => {
    localReactions.value = reactions;
};

const addCommentFromBroadcast = (comment: Comment) => {
    if (commentSectionRef.value) {
        // Only increment counter if the comment was actually added (not a duplicate)
        const wasAdded = commentSectionRef.value.addCommentFromBroadcast(comment);
        if (wasAdded) {
            totalComments.value++;
        }
    }
};

defineExpose({
    updateReactionsFromBroadcast,
    addCommentFromBroadcast,
});
</script>

<template>
    <div class="mb-6 rounded border bg-white p-3 shadow">
        <div class="mb-4 flex items-center gap-4">
            <a href="javascript:void(0)">
                <img :src="post.user.profile_picture_url ||
                    'https://avatar.iran.liara.run/public/'
                    " alt="User avatar"
                    class="hover-ring-blue-400 h-12 w-12 rounded-full border border-2 object-cover" />
            </a>
            <div class="flex flex-1 flex-col">
                <a href="javascript:void(0)" class="hover:underline">
                    <span class="text-base leading-tight font-bold text-gray-900">{{ post.user.name }}</span>
                </a>
                <template v-if="post.group">
                    Group: {{ post.group.name }}
                </template>
                <span class="mt-1 text-xs text-gray-400">{{
                    post.created_at
                }}</span>
            </div>

            <!-- Post Menu (only shown to post owner) -->
            <PostMenu v-if="canManagePost" :post="post" @edit="openEditModal" @delete="openDeleteDialog" />
        </div>
        <div v-if="post.body" class="mb-4">
            <Disclosure v-slot="{ open }">
                <div v-if="!open" ref="postContentRef" v-html="post.body.substring(0, 200)" class="post-content" />
                <DisclosurePanel v-else>
                    <div ref="postContentFullRef" v-html="post.body" class="post-content" />
                </DisclosurePanel>

                <DisclosureButton v-if="post.body.length > 200"
                    class="mt-2 cursor-pointer text-indigo-600 hover:underline">
                    {{ open ? 'Show Less' : 'Show More' }}
                </DisclosureButton>
            </Disclosure>
        </div>

        <!-- Attachments -->
        <div v-if="post.attachments.length > 0" class="mb-3">
            <AttachmentPreview :attachments="post.attachments" @click="openAttachmentViewer" />
        </div>

        <!-- Reactions Summary -->
        <div v-if="localReactions.total > 0" class="mb-2 flex items-center gap-2 text-sm text-gray-600">
            <div class="flex items-center gap-1">
                <span v-if="localReactions.summary.like" class="text-base">👍</span>
                <span v-if="localReactions.summary.love" class="text-base">❤️</span>
                <span v-if="localReactions.summary.haha" class="text-base">😂</span>
                <span v-if="localReactions.summary.wow" class="text-base">😮</span>
                <span v-if="localReactions.summary.sad" class="text-base">😢</span>
                <span v-if="localReactions.summary.angry" class="text-base">😠</span>
            </div>
            <span class="font-semibold">{{ localReactions.total }}</span>
            <span v-if="totalComments > 0" class="ml-auto">{{ totalComments }} {{ totalComments === 1 ? 'comment' :
                'comments' }}</span>
        </div>

        <div class="flex gap-2">
            <ReactionPicker :current-reaction="localReactions.current_user_reaction"
                :total-reactions="localReactions.total" @react="handleReaction" />
            <button @click="toggleComments"
                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-800 transition-all hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                </svg>
                Comment
            </button>
        </div>

        <!-- Comments Section -->
        <CommentSection v-if="showComments" ref="commentSectionRef" :post-id="post.id" :comments="post.comments.data"
            :total-comments="totalComments" @comment-added="handleCommentAdded"
            @comment-deleted="handleCommentDeleted" />

        <!-- Edit Post Modal -->
        <EditPostModal :post="post" :is-open="isEditModalOpen" @update:is-open="isEditModalOpen = $event" />

        <!-- Delete Confirmation Dialog -->
        <DeletePostDialog :post="post" :is-open="isDeleteDialogOpen" @update:is-open="isDeleteDialogOpen = $event" />

        <!-- Attachment Full Screen Viewer -->
        <AttachmentFullScreen v-if="post.attachments.length > 0" :attachments="post.attachments"
            :initial-index="currentAttachmentIndex" :is-open="isAttachmentViewerOpen"
            @update:is-open="isAttachmentViewerOpen = $event" />
    </div>
</template>

<style scoped>
/* Style for links in post content */
.post-content :deep(a) {
    color: #4f46e5;
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.2s ease;
}

.post-content :deep(a:hover) {
    color: #4338ca;

}

/* Style for CKEditor formatted content */
.post-content :deep(h1),
.post-content :deep(h2),
.post-content :deep(h3) {
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.post-content :deep(h1) {
    font-size: 1.5rem;
}

.post-content :deep(h2) {
    font-size: 1.25rem;
}

.post-content :deep(h3) {
    font-size: 1.125rem;
}

.post-content :deep(ul),
.post-content :deep(ol) {
    margin-left: 1.5rem;
    margin-bottom: 0.5rem;
}

.post-content :deep(blockquote) {
    border-left: 4px solid #d1d5db;
    padding-left: 1rem;
    margin-left: 0;
    font-style: italic;
    color: #6b7280;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}

.post-content :deep(p) {
    margin-bottom: 0.5rem;
}
</style>
