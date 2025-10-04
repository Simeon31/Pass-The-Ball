<template>
    <AppLayout>
        <div class="container mx-auto h-full overflow-auto">
            <div class="relative">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQeFl_J905mD_ZKUJGJAqAMjyzSnJi_fnVvBQ&s"
                    class="bg-white w-full h-[200px] object-cover" alt="No cover image">
                <div class="flex">
                    <img src="https://static.vecteezy.com/system/resources/previews/054/720/352/non_2x/student-3d-icon-for-education-projects-on-transparent-background-png.png"
                        class="ml-[48px] w-[128px] h-[128px] -mt-[64px]" alt="No avatar image">
                    <div class="flex justify-between item-center flex-1 p-4 text-2xl font-semibold text-gray-700">
                        {{ user.name }}

                        <div class="flex justify-evenly">
                            <Button>
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
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TabItem from './Partials/TabItem.vue';
import Profile from './Profile.vue';
import Button from '@/components/ui/button/Button.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
})

const user = usePage().props.auth.user;

</script>
