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
                    <div class="item-center flex flex-1 justify-between p-4">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-700">{{ user.name }}</h1>
                            <div class="mt-2 flex gap-4 text-sm text-gray-600">
                                <Link :href="`/users/${user.id}/followers`"
                                    class="font-medium transition hover:text-gray-900 hover:underline">
                                {{ followersCount }}
                                <span class="font-normal">{{ followersCount === 1 ? 'Follower' : 'Followers'
                                }}</span>
                                </Link>
                                <Link :href="`/users/${user.id}/following`"
                                    class="font-medium transition hover:text-gray-900 hover:underline">
                                {{ followingCount }}
                                <span class="font-normal">Following</span>
                                </Link>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <FollowButton v-if="!isOwnProfile" :user-id="user.id"
                                :is-following="user.is_followed_by_auth || false" />

                            <Link v-if="isOwnProfile" :href="edit.url()">
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
                            <TabItem :text="`Followers (${followersCount})`" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem :text="`Following (${followingCount})`" :selected="selected" />
                        </Tab>

                        <Tab v-slot="{ selected }" as="template">
                            <TabItem text="Gallery" :selected="selected" />
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

                        <TabPanel key="about" class="bg-white shadow">
                            <div class="mx-auto max-w-4xl p-6">
                                <!-- Header -->
                                <div class="mb-6">
                                    <h3 class="text-2xl font-bold text-gray-900">About {{ user.name }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">Get to know more about this user</p>
                                </div>

                                <!-- Info Cards Grid -->
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Contact Information Card -->
                                    <div
                                        class="rounded-xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition-shadow hover:shadow-md">
                                        <div class="mb-4 flex items-center">
                                            <div class="rounded-lg bg-blue-100 p-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor"
                                                    class="h-6 w-6 text-blue-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                            </div>
                                            <h4 class="ml-3 text-lg font-semibold text-gray-900">Contact Information
                                            </h4>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Username -->
                                            <div class="flex items-start">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-purple-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-5 w-5 text-purple-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-xs font-medium text-gray-500">Username</p>
                                                    <p class="mt-1 text-sm font-semibold text-gray-900">@{{
                                                        user.username }}</p>
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="flex items-start">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-5 w-5 text-green-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-xs font-medium text-gray-500">Email Address</p>
                                                    <p class="mt-1 break-all text-sm font-semibold text-gray-900">{{
                                                        user.email }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Account Details Card -->
                                    <div
                                        class="rounded-xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm transition-shadow hover:shadow-md">
                                        <div class="mb-4 flex items-center">
                                            <div class="rounded-lg bg-indigo-100 p-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                    stroke-width="1.5" stroke="currentColor"
                                                    class="h-6 w-6 text-indigo-600">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </div>
                                            <h4 class="ml-3 text-lg font-semibold text-gray-900">Account Details</h4>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Member Since -->
                                            <div class="flex items-start">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-5 w-5 text-orange-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-xs font-medium text-gray-500">Member Since</p>
                                                    <p class="mt-1 text-sm font-semibold text-gray-900">
                                                        {{ new Date(user.created_at).toLocaleDateString('en-US', {
                                                            year: 'numeric',
                                                            month: 'long',
                                                            day: 'numeric'
                                                        }) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Account Age -->
                                            <div class="flex items-start">
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-5 w-5 text-rose-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-xs font-medium text-gray-500">Account Age</p>
                                                    <p class="mt-1 text-sm font-semibold text-gray-900">
                                                        {{ calculateAccountAge(user.created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Stats Section (Instagram/X inspired) -->
                                <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                    <h4 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                                        Activity Overview</h4>
                                    <div class="grid grid-cols-3 gap-4">
                                        <!-- Posts Count -->
                                        <div class="text-center">
                                            <div class="mb-2 flex items-center justify-center">
                                                <div class="rounded-full bg-blue-100 p-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-6 w-6 text-blue-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-2xl font-bold text-gray-900">{{ posts?.length || 0 }}</p>
                                            <p class="text-xs font-medium text-gray-500">Posts</p>
                                        </div>

                                        <!-- Albums Count -->
                                        <div class="text-center">
                                            <div class="mb-2 flex items-center justify-center">
                                                <div class="rounded-full bg-purple-100 p-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-6 w-6 text-purple-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-2xl font-bold text-gray-900">{{ totalAlbumsCount || 0 }}</p>
                                            <p class="text-xs font-medium text-gray-500">Albums</p>
                                        </div>

                                        <!-- Followers Count -->
                                        <div class="text-center">
                                            <div class="mb-2 flex items-center justify-center">
                                                <div class="rounded-full bg-green-100 p-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        class="h-6 w-6 text-green-600">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-2xl font-bold text-gray-900">{{ followersCount }}</p>
                                            <p class="text-xs font-medium text-gray-500">Followers</p>
                                        </div>
                                    </div>
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
                            <div class="space-y-4">
                                <!-- Header with View All link -->
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold">Photo Albums</h3>
                                    <Link v-if="latestAlbums && latestAlbums.length > 0"
                                        :href="`/profile/${user.username}/gallery`"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                    View All ({{ totalAlbumsCount }})
                                    </Link>
                                </div>

                                <!-- Albums Grid -->
                                <div v-if="latestAlbums && latestAlbums.length > 0"
                                    class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                    <Link v-for="album in latestAlbums" :key="album.id"
                                        :href="`/profile/${user.username}/gallery/${album.slug}`"
                                        class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 shadow transition-shadow hover:shadow-lg">
                                    <img v-if="album.cover_url" :src="album.cover_url" :alt="album.title"
                                        class="h-full w-full object-cover transition-transform group-hover:scale-105" />
                                    <div v-else
                                        class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    </div>
                                    <div
                                        class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                                        <h4 class="truncate text-sm font-medium text-white">{{ album.title }}</h4>
                                        <p class="text-xs text-gray-300">{{ album.photos_count || 0 }} photos</p>
                                    </div>
                                    </Link>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="mx-auto h-12 w-12 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <p class="mt-4 text-gray-600">
                                        <template v-if="isOwnProfile">You haven't created any albums yet</template>
                                        <template v-else>No photo albums to display</template>
                                    </p>
                                    <p v-if="isOwnProfile" class="mt-2 text-sm text-gray-500">
                                        Create an album to start organizing your photos!
                                    </p>
                                    <Link v-if="isOwnProfile" :href="`/profile/${user.username}/gallery`"
                                        class="mt-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="mr-2 h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Create Album
                                    </Link>
                                </div>
                            </div>
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
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import TabItem from './settings/Partials/TabItem.vue';
import PostItem from '@/components/app/PostItem.vue';
import CreatePost from '@/components/app/CreatePost.vue';
import FollowButton from '@/components/app/FollowButton.vue';
import type { Post } from '@/types';
import type { Album } from '@/types';
import Echo from '@/echo';

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    created_at: string;
    profile_picture_url?: string | null;
    cover_url?: string | null;
    followers_count?: number;
    following_count?: number;
    is_followed_by_auth?: boolean;
}

interface Props {
    errors?: Record<string, string>;
    user: User;
    posts?: Post[];
    latestAlbums?: Album[];
    totalAlbumsCount?: number;
}

const page = usePage();

const imagesForm = useForm({
    avatar: null as File | null,
    cover: null as File | null,
});

const props = defineProps<Props>();

// Reactive follower counts
const followersCount = ref(props.user.followers_count || 0);
const followingCount = ref(props.user.following_count || 0);

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

// Calculate how long the user has been a member
function calculateAccountAge(createdAt: string): string {
    const now = new Date();
    const created = new Date(createdAt);
    const diffInMs = now.getTime() - created.getTime();
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));

    if (diffInDays < 1) {
        return 'Joined today';
    } else if (diffInDays < 7) {
        return `${diffInDays} day${diffInDays === 1 ? '' : 's'}`;
    } else if (diffInDays < 30) {
        const weeks = Math.floor(diffInDays / 7);
        return `${weeks} week${weeks === 1 ? '' : 's'}`;
    } else if (diffInDays < 365) {
        const months = Math.floor(diffInDays / 30);
        return `${months} month${months === 1 ? '' : 's'}`;
    } else {
        const years = Math.floor(diffInDays / 365);
        return `${years} year${years === 1 ? '' : 's'}`;
    }
}

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

// Listen for real-time follower/following count updates
onMounted(() => {
    if (page.props.auth.user) {
        Echo.channel(`users.${page.props.auth.user.id}`)
            .listen('.user.followed', (event: any) => {
                // Update followers count if someone followed/unfollowed us
                if (event.followed_user_id === page.props.auth.user.id) {
                    followersCount.value = event.followers_count;
                }
                // Update following count if we followed/unfollowed someone
                if (event.follower_id === page.props.auth.user.id) {
                    followingCount.value = event.following_count;
                }
            });
    }
});

onUnmounted(() => {
    if (page.props.auth.user) {
        Echo.leave(`users.${page.props.auth.user.id}`);
    }
});
</script>
