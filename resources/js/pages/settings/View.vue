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
                    class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 mr-2" />
                            <p class="text-sm font-medium text-green-800">{{ statusMessage() }}</p>
                        </div>
                        <button @click="dismissStatus" class="text-green-600 hover:text-green-800">
                            <XMarkIcon class="w-5 h-5" />
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
                <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                        </div>
                        <button @click="validationError = null" class="text-red-600 hover:text-red-800">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <div class="group relative bg-white">
                <img :src="coverSrc" class="bg-white w-full h-[200px] object-cover" alt="Default cover image">
                <div class="absolute top-2 right-2 bg-gray-800 p-2 opacity-0 group-hover:opacity-100 rounded-full">
                    <button v-if="!coverImageSrc"
                        class="bg-gray-800 text-white py-1 px-2 text-sm flex items-center rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>

                        Update Cover
                        <Input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="onCoverChange" />
                    </button>
                    <div v-else class="flex gap-2 whitespace-nowrap">
                        <button @click="cancelCoverImage"
                            class="bg-white hover:bg-gray-200 text-gray-900 py-1 px-2 text-xs inline-flex items-center cursor-pointer rounded-sm">
                            <XMarkIcon class="w-4 h-4 mr-2" />

                            Cancel
                        </button>
                        <button @click="submitCoverImage"
                            class="bg-gray-950 hover:bg-gray-900 text-gray-100 py-1 px-2 text-xs inline-flex items-center cursor-pointer rounded-sm">
                            <CheckCircleIcon class="w-4 h-4 mr-2" />

                            Submit
                        </button>
                    </div>
                </div>
                <div class="flex">
                    <div class="group/avatar relative ml-[48px] w-[128px] h-[128px] -mt-[64px]">
                        <img :src="avatarSrc" class="w-full h-full rounded-full object-cover border-4 border-slate-900"
                            alt="Profile picture">
                        <div
                            class="absolute inset-0 bg-gray-900 bg-opacity-0 group-hover/avatar:bg-opacity-50 rounded-full transition-all duration-200 flex items-center justify-center opacity-0 group-hover/avatar:opacity-100">
                            <button v-if="!avatarImageSrc"
                                class="bg-indigo-700 hover:bg-indigo-900 text-white py-1.5 px-3 text-xs flex items-center cursor-pointer rounded-md shadow-lg relative">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                                Update
                                <input type="file" class="absolute inset-0 opacity-0 cursor-pointer"
                                    @change="onAvatarChange" />
                            </button>
                            <div v-else class="flex gap-2">
                                <button @click="cancelAvatarImage"
                                    class="bg-white hover:bg-gray-100 text-gray-800 py-1.5 px-2 text-xs inline-flex items-center cursor-pointer rounded-md shadow-lg">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                                <button @click="submitAvatarImage"
                                    class="bg-gray-800 hover:bg-gray-900 text-white py-1.5 px-2 text-xs inline-flex items-center cursor-pointer rounded-md shadow-lg">
                                    <CheckCircleIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between item-center flex-1 p-4 text-2xl font-semibold text-gray-700">
                        {{ user.name }}

                        <div class="flex justify-evenly">
                            <Button class="cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit Profile
                            </Button>
                        </div>
                    </div>
                </div>

            </div>
            <div class="border-t">
                <TabGroup>
                    <TabList class="flex bg-white">
                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="About" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Posts" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Followers" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Followings" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Photos" :selected="selected" />
                        </Tab>
                    </TabList>

                    <TabPanels class="mt-2">
                        <TabPanel key="about" class="bg-white p-3 shadow">
                            <Profile :must-verify-email="mustVerifyEmail" :status="status" />
                        </TabPanel>
                    </TabPanels>

                    <TabPanels class="mt-2">
                        <TabPanel key="post" class="bg-white p-3 shadow">
                            Posts content for the selected .
                        </TabPanel>
                    </TabPanels>

                    <TabPanels class="mt-2">
                        <TabPanel key="followers" class="bg-white p-3 shadow">
                            Followers content for the selected .
                        </TabPanel>
                    </TabPanels>

                    <TabPanels class="mt-2">
                        <TabPanel key="followings" class="bg-white p-3 shadow">
                            Followings content for the selected .
                        </TabPanel>
                    </TabPanels>

                    <TabPanels class="mt-2">
                        <TabPanel key="photos" class="bg-white p-3 shadow">
                            Photos content for the selected .
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AppLayout>

</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { XMarkIcon, CheckCircleIcon } from '@heroicons/vue/24/solid'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TabItem from './Partials/TabItem.vue';
import Profile from './Profile.vue';
import Button from '@/components/ui/button/Button.vue';
import { useForm } from "@inertiajs/vue3";
import { updateImage } from '@/routes/profile';
import Input from '@/components/ui/input/Input.vue';
import { useFlashMessage } from '@/composables/useFlashMessage';

const imagesForm = useForm({
    avatar: null,
    cover: null,
});

const props = defineProps({
    errors: Object,
    mustVerifyEmail: {
        type: Boolean,
    },
    user: {
        type: Object,
        required: true,
    },
})

// Use flash message composable for status messages
const { showMessage: showStatus, message: statusMessage, dismiss: dismissStatus } = useFlashMessage('status', 5000);

const validationError = ref(null);

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

const user = computed(() => props.user);
const coverImageSrc = ref(null);
const avatarImageSrc = ref(null);
const coverSrc = computed(() => {
    // Preview from file input (data URL)
    if (coverImageSrc.value) return coverImageSrc.value;

    // Using backend-provided cover_url. If it's an absolute URL, return as-is.
    if (user.value && user.value.cover_url) {
        if (/^(https?:)?\/\//.test(user.value.cover_url)) return user.value.cover_url;
        return user.value.cover_url.startsWith('/') ? user.value.cover_url : `/${user.value.cover_url}`;
    }

    // Fallback to the public default image if no cover image is set
    return '/images/default-cover-image.jpg';
});

const avatarSrc = computed(() => {
    // Preview from file input (data URL)
    if (avatarImageSrc.value) return avatarImageSrc.value;

    // Using backend-provided profile_picture_url
    if (user.value && user.value.profile_picture_url) {
        if (/^(https?:)?\/\//.test(user.value.profile_picture_url)) return user.value.profile_picture_url;
        return user.value.profile_picture_url.startsWith('/') ? user.value.profile_picture_url : `/${user.value.profile_picture_url}`;
    }

    // Fallback to default avatar
    return 'https://static.vecteezy.com/system/resources/previews/054/720/352/non_2x/student-3d-icon-for-education-projects-on-transparent-background-png.png';
});

function onCoverChange(event) {
    const file = event.target.files[0];

    if (!file) return;

    // Clear any previous validation errors
    validationError.value = null;

    // Validation file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        validationError.value = 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        event.target.value = ''; // Clear the input
        return;
    }

    // Validation file size 
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        validationError.value = `File size (${fileSizeMB}MB) exceeds the maximum allowed size of 2MB.`;
        event.target.value = ''; // Clear the input
        return;
    }

    // If validation passes, proceed with the upload
    imagesForm.cover = file;

    // handling preview 
    const reader = new FileReader();
    reader.onload = (e) => {
        coverImageSrc.value = e.target && e.target.result;
    };
    reader.readAsDataURL(imagesForm.cover);
}

function cancelCoverImage() {
    coverImageSrc.value = null;
    imagesForm.cover = null;
    validationError.value = null; // Clear any validation errors
}

function submitCoverImage() {
    if (imagesForm.cover) {
        imagesForm.post(updateImage.url(), {
            preserveScroll: true,
            onSuccess: () => {
                imagesForm.reset('cover');
                coverImageSrc.value = null;
            },
        });
    }
}

function onAvatarChange(event) {
    const file = event.target.files[0];

    if (!file) return;

    // Clear any previous validation errors
    validationError.value = null;

    // Validation file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        validationError.value = 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.';
        event.target.value = ''; // Clear the input
        return;
    }

    // Validation file size 
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
        validationError.value = `File size (${fileSizeMB}MB) exceeds the maximum allowed size of 2MB.`;
        event.target.value = ''; // Clear the input
        return;
    }

    // If validation passes, proceed with the upload
    imagesForm.avatar = file;

    // handling preview 
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarImageSrc.value = e.target && e.target.result;
    };
    reader.readAsDataURL(imagesForm.avatar);
}

function cancelAvatarImage() {
    avatarImageSrc.value = null;
    imagesForm.avatar = null;
    validationError.value = null; // Clear any validation errors
}

function submitAvatarImage() {
    if (imagesForm.avatar) {
        imagesForm.post(updateImage.url(), {
            preserveScroll: true,
            onSuccess: () => {
                imagesForm.reset('avatar');
                avatarImageSrc.value = null;
            },
        });
    }
}
</script>
