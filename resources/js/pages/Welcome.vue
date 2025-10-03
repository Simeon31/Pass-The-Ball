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

    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-3 py-6 px-3">
            <GroupList />
        </div>
        <div class="col-span-6">
            <CreatePost />

            <PostList />
        </div>
        <div class="col-span-3 py-6 px-3">
            <FollowingList />
        </div>
    </div>
</template>
