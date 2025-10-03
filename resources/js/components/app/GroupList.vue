<script setup lang="ts">
import GroupItem from '@/components/app/GroupItem.vue';
import TextInput from '@/components/TextInput.vue';
import { ref } from 'vue';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import GroupListItems from './GroupListItems.vue';

const seachKeyword = ref('');
</script>

<template>
    <div class="px-3 bg-white rounded border py-3]">
        <div class="block lg:hidden overflow-hidden flex flex-col h-full">
            <Disclosure v-slot="{ open }">
                <DisclosureButton>
                    <h2 class="text-xl font-bold">My groups</h2>
                </DisclosureButton>
                <DisclosurePanel>
                    <GroupListItems />
                </DisclosurePanel>
            </Disclosure>
        </div>
        <div class="hidden lg:block overflow-hidden flex flex-col lg:h-full">
            <h2 class="text-xl font-bold">My groups</h2>
            <GroupListItems />
        </div>
    </div>
</template>