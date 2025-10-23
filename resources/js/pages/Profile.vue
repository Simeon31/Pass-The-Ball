<template>
    <AppLayout>
        <div class="container mx-auto h-full overflow-auto">
            <!-- Success Message -->
            <Transition enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="statusMessage() && showStatus"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <CheckCircleIcon class="mr-2 h-5 w-5 text-green-600" />
                            <p class="text-sm font-medium text-green-800">
                                {{ statusMessage() }}
                            </p>
                        </div>
                        <button @click="dismissStatus" class="text-green-600 hover:text-green-800">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Error Messages -->
            <Transition enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5 text-red-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm font-medium text-red-800">
                                {{ errorMessage }}
                            </p>
                        </div>
                        <button @click="validationError = null" class="text-red-600 hover:text-red-800">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Profile Header -->
            <div class="group relative bg-white">
                <!-- Cover Image -->
                <img :src="coverSrc" class="h-[200px] w-full bg-white object-cover" alt="Cover image" />
                <div v-if="isOwnProfile"
                    class="absolute right-2 top-2 rounded-full bg-gray-800 p-2 opacity-0 group-hover:opacity-100">
                    <button v-if="!coverImageSrc"
                        class="flex items-center rounded-md bg-gray-800 px-2 py-1 text-sm text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-2 h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>

                        Update Cover
                        <Input type="file" class="absolute inset-0 cursor-pointer opacity-0" @change="onCoverChange" />
                    </button>
                    <div v-else class="flex gap-2 whitespace-nowrap">
                        <button @click="cancelCoverImage"
                            class="inline-flex cursor-pointer items-center rounded-sm bg-white px-2 py-1 text-xs text-gray-900 hover:bg-gray-200">
                            <XMarkIcon class="mr-2 h-4 w-4" />

                            Cancel
                        </button>
                        <button @click="submitCoverImage"
                            class="inline-flex cursor-pointer items-center rounded-sm bg-gray-950 px-2 py-1 text-xs text-gray-100 hover:bg-gray-900">
                            <CheckCircleIcon class="mr-2 h-4 w-4" />

                            Submit
                        </button>
                    </div>
                </div>
                <!-- Profile Info Section -->
                <div class="flex">
                    <!-- Avatar -->
                    <div class="group/avatar relative -mt-[64px] ml-[48px] h-[128px] w-[128px]">
                        <img :src="avatarSrc" class="h-full w-full rounded-full border-4 border-slate-900 object-cover"
                            alt="Profile picture" />
                        <div v-if="isOwnProfile"
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-gray-900 bg-opacity-0 opacity-0 transition-all duration-200 group-hover/avatar:bg-opacity-50 group-hover/avatar:opacity-100">
                            <button v-if="!avatarImageSrc"
                                class="relative flex cursor-pointer items-center rounded-md bg-indigo-700 px-3 py-1.5 text-xs text-white shadow-lg hover:bg-indigo-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mr-1.5 h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                Update
                                <input type="file" class="absolute inset-0 cursor-pointer opacity-0"
                                    @change="onAvatarChange" />
                            </button>
                            <div v-else class="flex gap-2">
                                <button @click="cancelAvatarImage"
                                    class="inline-flex cursor-pointer items-center rounded-md bg-white px-2 py-1.5 text-xs text-gray-800 shadow-lg hover:bg-gray-100">
                                    <XMarkIcon class="h-4 w-4" />
                                </button>
                                <button @click="submitAvatarImage"
                                    class="inline-flex cursor-pointer items-center rounded-md bg-gray-800 px-2 py-1.5 text-xs text-white shadow-lg hover:bg-gray-900">
                                    <CheckCircleIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Name and Edit Button -->
                    <div class="item-center flex flex-1 justify-between p-4 text-2xl font-semibold text-gray-700">
                        {{ user.name }}

                        <div v-if="isOwnProfile" class="flex justify-evenly">
                            <Link :href="edit.url()">
                            <Button class="cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit Profile
                            </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="border-t">
                <TabGroup>
                    <TabList class="flex bg-white">
                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Posts" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="About" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Followers" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Following" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Photos" :selected="selected" />
                        </Tab>
                    </TabList>

                    <TabPanels class="mt-2">
                        <TabPanel key="posts" class="bg-white p-3 shadow">
                            <!-- Center posts with max-width like Groups page -->
                            <div class="mx-auto max-w-3xl">
                                <div class="space-y-4">
                                    <!-- Create Post Component (only for own profile) -->
                                    <!-- Posts created here are personal posts (no group_id), belonging to this user's profile -->
                                    <CreatePost v-if="isOwnProfile" />

                                    <!-- Posts List -->
                                    <div v-if="posts && posts.length > 0" class="space-y-4">
                                        <PostItem v-for="post in posts" :key="post.id" :post="post" />
                                    </div>

                                    <!-- No Posts Message -->
                                    <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor"
                                            class="mx-auto h-12 w-12 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                        </svg>
                                        <p class="mt-4 text-gray-600">
                                            {{ isOwnProfile ? "You haven't posted anything yet" : "No posts to display"
                                            }}
                                        </p>
                                        <p v-if="isOwnProfile" class="mt-2 text-sm text-gray-500">
                                            Share your thoughts with your followers!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel key="about" class="bg-white p-3 shadow">
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold">About</h3>
                                <div class="text-gray-600">
                                    <p><strong>Username:</strong> {{ user.username }}</p>
                                    <p><strong>Email:</strong> {{ user.email }}</p>
                                    <p>
                                        <strong>Member since:</strong>
                                        {{
                                            new Date(
                                                user.created_at,
                                            ).toLocaleDateString()
                                        }}
                                    </p>
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel key="followers" class="bg-white p-3 shadow">
                            <p class="text-gray-600">
                                Followers list will be displayed here
                            </p>
                        </TabPanel>

                        <TabPanel key="following" class="bg-white p-3 shadow">
                            <p class="text-gray-600">
                                Following list will be displayed here
                            </p>
                        </TabPanel>

                        <TabPanel key="photos" class="bg-white p-3 shadow">
                            <p class="text-gray-600">
                                Photos will be displayed here
                            </p>
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import { useFlashMessage } from '@/composables/useFlashMessage';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, updateImages } from '@/routes/profile';
import { Tab, TabGroup, TabList, TabPanel, TabPanels } from '@headlessui/vue';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/solid';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import TabItem from './settings/Partials/TabItem.vue';
import PostItem from '@/components/app/PostItem.vue';
import CreatePost from '@/components/app/CreatePost.vue';
import type { Post } from '@/types';

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    created_at: string;
    profile_picture_url?: string | null;
    cover_url?: string | null;
}

interface Props {
    errors?: Record<string, string>;
    user: User;
    posts?: Post[];
}

const page = usePage();

const imagesForm = useForm({
    avatar: null as File | null,
    cover: null as File | null,
});

const props = defineProps<Props>();

// Check if the viewing user is the profile owner
const isOwnProfile = computed(() => {
    return page.props.auth.user?.id === props.user.id;
});

// Use flash message composable for status messages
const {
    showMessage: showStatus,
    message: statusMessage,
    dismiss: dismissStatus,
} = useFlashMessage('status', 5000);

const validationError = ref<string | null>(null);

// Compute error message
const errorMessage = computed(() => {
    if (validationError.value) return validationError.value;
    if (props.errors?.cover) return props.errors.cover;
    if (props.errors?.avatar) return props.errors.avatar;
    return null;
});

// Auto-hide validation error after 7 seconds
watch(validationError, (newError) => {
    if (newError) {
        setTimeout(() => {
            validationError.value = null;
        }, 7000);
    }
});

const coverImageSrc = ref<string | null>(null);
const avatarImageSrc = ref<string | null>(null);

const coverSrc = computed(() => {
    // Preview from file input (data URL)
    if (coverImageSrc.value) return coverImageSrc.value;

    // Using backend-provided cover_url. If it's an absolute URL, return as-is.
    if (props.user && props.user.cover_url) {
        if (/^(https?:)?\/\//.test(props.user.cover_url))
            return props.user.cover_url;
        return props.user.cover_url.startsWith('/')
            ? props.user.cover_url
            : `/${props.user.cover_url}`;
    }

    // Fallback to the public default image if no cover image is set
    return '/images/default-cover-image.jpg';
});

const avatarSrc = computed(() => {
    // Preview from file input (data URL)
    if (avatarImageSrc.value) return avatarImageSrc.value;

    // Using backend-provided profile_picture_url
    if (props.user && props.user.profile_picture_url) {
        if (/^(https?:)?\/\//.test(props.user.profile_picture_url))
            return props.user.profile_picture_url;
        return props.user.profile_picture_url.startsWith('/')
            ? props.user.profile_picture_url
            : `/${props.user.profile_picture_url}`;
    }

    // Fallback to default avatar
    return 'https://static.vecteezy.com/system/resources/previews/054/720/352/non_2x/student-3d-icon-for-education-projects-on-transparent-background-png.png';
});

function onCoverChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Clear any previous validation errors
    validationError.value = null;

    // Validation file type
    const allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    if (!allowedTypes.includes(file.type)) {
        validationError.value =
            'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        target.value = ''; // Clear the input
        return;
    }

    // Validation file size
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        validationError.value = `File size (${fileSizeMB}MB) exceeds the maximum allowed size of 2MB.`;
        target.value = ''; // Clear the input
        return;
    }

    // If validation passes, proceed with the upload
    imagesForm.cover = file;

    // handling preview
    const reader = new FileReader();
    reader.onload = (e) => {
        coverImageSrc.value = (e.target?.result as string) || null;
    };
    reader.readAsDataURL(file);
}

function cancelCoverImage() {
    coverImageSrc.value = null;
    imagesForm.cover = null;
    validationError.value = null; // Clear any validation errors
}

function submitCoverImage() {
    if (imagesForm.cover) {
        imagesForm.post(updateImages.url(), {
            preserveScroll: true,
            onSuccess: () => {
                imagesForm.reset('cover');
                coverImageSrc.value = null;
            },
        });
    }
}

function onAvatarChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Clear any previous validation errors
    validationError.value = null;

    // Validation file type
    const allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];
    if (!allowedTypes.includes(file.type)) {
        validationError.value =
            'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        target.value = ''; // Clear the input
        return;
    }

    // Validation file size
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        validationError.value = `File size (${fileSizeMB}MB) exceeds the maximum allowed size of 2MB.`;
        target.value = ''; // Clear the input
        return;
    }

    // If validation passes, proceed with the upload
    imagesForm.avatar = file;

    // handling preview
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarImageSrc.value = (e.target?.result as string) || null;
    };
    reader.readAsDataURL(file);
}

function cancelAvatarImage() {
    avatarImageSrc.value = null;
    imagesForm.avatar = null;
    validationError.value = null; // Clear any validation errors
}

function submitAvatarImage() {
    if (imagesForm.avatar) {
        imagesForm.post(updateImages.url(), {
            preserveScroll: true,
            onSuccess: () => {
                imagesForm.reset('avatar');
                avatarImageSrc.value = null;
            },
        });
    }
}
</script>
