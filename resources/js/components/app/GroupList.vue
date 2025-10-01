<script setup lang="ts">
import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';

const seachKeyword = ref('');
</script>

<template>
    <div class="px-3">
        <h2 class="text-2xl font-bold">My groups</h2>
        <TextInput :model-value="seachKeyword" placeholder="Type to seach groups" />
        <div class="py-8">
            <div v-if="false" class="flex text-center">You're not joined into any group</div>
            <div v-else>
                <GroupItem image="https://lipsum.app/random/1600x900" title="Football" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
                </GroupItem>

                <GroupItem image="https://lipsum.app/random/1600x900" title="Tennis" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
                </GroupItem>

            </div>
        </div>
    </div>
</template>