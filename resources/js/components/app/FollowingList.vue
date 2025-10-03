<script setup lang="ts">
import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';
import FollowingListItems from './FollowingListItems.vue';
</script>

<template>
    <div class="px-3 bg-white rounded border py-3">
        <h2 class="text-xl font-bold">My Followings</h2>
        <FollowingListItems />
    </div>
</template>