<script setup lang="ts">
import type { Post, PostAttachment } from '@/types';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, nextTick } from 'vue';
import DeletePostDialog from './DeletePostDialog.vue';
import EditPostModal from './EditPostModal.vue';
import PostMenu from './PostMenu.vue';

const props = defineProps<{
    post: Post;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Check if the current user owns this post
const canManagePost = computed(() => authUser.value.id === props.post.user.id);

// Modal states
const isEditModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);

// Refs for post content containers
const postContentRef = ref<HTMLDivElement | null>(null);
const postContentFullRef = ref<HTMLDivElement | null>(null);

function isImage(attachment: PostAttachment) {
    const mime = attachment.mime.split('/');
    return mime[0] === 'image';
}

const openEditModal = () => {
    isEditModalOpen.value = true;
};

const openDeleteDialog = () => {
    isDeleteDialogOpen.value = true;
};

// Function to make links in HTML content clickable
const makeLinksClickable = (element: HTMLElement | null) => {
    if (!element) return;

    const links = element.querySelectorAll('a');
    links.forEach((link) => {
        // Ensure the link has proper attributes
        if (!link.hasAttribute('target')) {
            link.setAttribute('target', '_blank');
        }
        if (!link.hasAttribute('rel')) {
            link.setAttribute('rel', 'noopener noreferrer');
        }
        // Add click handler to ensure navigation works
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
        <div class="mb-3 grid grid-cols-2 gap-3 lg:grid-cols-3">
            <template v-for="attachment in post.attachments">
                <div
                    class="group flec relative aspect-square flex-col items-center justify-center bg-indigo-100 text-gray-500">
                    <button
                        class="item-center absolute top-2 right-2 flex h-8 w-8 cursor-pointer justify-center rounded bg-gray-800 text-white opacity-0 transition-all group-hover:opacity-100 hover:bg-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                        </svg>
                    </button>
                    <img v-if="isImage(attachment)" :src="attachment.url" alt="No image to show"
                        class="aspect-square object-cover" />
                </div>
            </template>
        </div>
        <div class="flex gap-2">
            <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-800 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                </svg>
                Like
            </button>
            <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1 rounded-lg bg-gray-100 px-4 py-2 text-gray-800 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                </svg>

                Comment
            </button>
        </div>

        <!-- Edit Post Modal -->
        <EditPostModal :post="post" :is-open="isEditModalOpen" @update:is-open="isEditModalOpen = $event" />

        <!-- Delete Confirmation Dialog -->
        <DeletePostDialog :post="post" :is-open="isDeleteDialogOpen" @update:is-open="isDeleteDialogOpen = $event" />
    </div>
</template>

<style scoped>
/* Style for links in post content */
.post-content :deep(a) {
    color: #4f46e5;
    /* indigo-600 */
    text-decoration: underline;
    cursor: pointer;
    transition: color 0.2s ease;
}

.post-content :deep(a:hover) {
    color: #4338ca;
    /* indigo-700 */
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
