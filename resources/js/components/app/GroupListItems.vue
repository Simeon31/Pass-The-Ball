<script setup lang="ts">
import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

const seachKeyword = ref('');
</script>

<template>
    <TextInput :model-value="seachKeyword" placeholder="Type to seach groups" class="w-full" />
    <div class="mt-3 py-3">
        <div v-if="false" class="text-gray-400 flex text-center p-3">You're not joined into any group
        </div>
        <div v-else>
            <GroupItem image="https://lipsum.app/random/1600x900" title="Football" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </GroupItem>

            <GroupItem image="https://lipsum.app/random/1600x900" title="Tennis" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </GroupItem>

            <GroupItem image="https://lipsum.app/random/1600x900" title="Tennis" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </GroupItem>

            <GroupItem image="https://lipsum.app/random/1600x900" title="Tennis" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </GroupItem>
            <GroupItem image="https://lipsum.app/random/1600x900" title="Tennis" description="Lorem ipsum dolor sit amet consectetur adipisicing elit. Perspiciatis
                                consectetur quos">
            </GroupItem>

        </div>
    </div>
</template>