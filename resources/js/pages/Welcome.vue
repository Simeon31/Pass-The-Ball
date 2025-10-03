<script setup lang="ts">
import CreatePost from '@/components/app/CreatePost.vue';
import FollowingList from '@/components/app/FollowingList.vue';
import GroupItem from '@/components/app/GroupItem.vue';
import GroupList from '@/components/app/GroupList.vue';
import PostList from '@/components/app/PostList.vue';
import { Head, Link } from '@inertiajs/vue3';

</script>

<template>

    <Head title="Pass the Ball">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="grid lg:grid-cols-12 gap-3 p-4 h-screen">
        <div class="lg:col-span-3 lg:order-first h-full overflow-y-auto">
            <div class="h-[400px] overflow-y-auto">
                <GroupList />
            </div>
        </div>
        <div class="lg:col-span-3 lg:order-last h-full overflow-y-auto">
            <div class="h-[400px] overflow-y-auto">
                <FollowingList />
            </div>
        </div>
        <div class="lg:col-span-6 h-full overflow-y-auto flex flex-col">
            <CreatePost />
            <PostList class="flex-1" />
        </div>
    </div>
</template>
