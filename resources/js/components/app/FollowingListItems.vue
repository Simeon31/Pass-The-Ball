<script setup lang="ts">

import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';
import FollowerItem from './FollowingItem.vue';
import FollowingItem from './FollowingItem.vue';

const seachKeyword = ref('');
</script>

<template>
    <TextInput :model-value="seachKeyword" placeholder="Type to seach friend's list" class="w-full" />
    <div class="mt-3 lg:flex-1">
        <div v-if="false" class="text-gray-400 flex text-center p-3">You currently have no friends</div>
        <div v-else>
            <FollowingItem image="https://lipsum.app/random/1600x900" title="John" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </FollowingItem>

            <FollowingItem image="https://lipsum.app/random/1600x900" title="Larry" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </FollowingItem>
            <FollowingItem image="https://lipsum.app/random/1600x900" title="Larry" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </FollowingItem>
            <FollowingItem image="https://lipsum.app/random/1600x900" title="Larry" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </FollowingItem>
            <FollowingItem image="https://lipsum.app/random/1600x900" title="Larry" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </FollowingItem>

        </div>
    </div>
</template>