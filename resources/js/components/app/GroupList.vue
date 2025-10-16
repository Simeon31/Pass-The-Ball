<script setup lang="ts">
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import { ref } from 'vue';
import GroupListItems from './GroupListItems.vue';

const seachKeyword = ref('');
</script>

<template>
    <div class="py-3] rounded border bg-white px-3">
        <div class="block flex h-full flex-col overflow-hidden lg:hidden">
            <Disclosure v-slot="{ open }">
                <DisclosureButton>
                    <h2 class="text-xl font-bold">My groups</h2>
                </DisclosureButton>
                <DisclosurePanel>
                    <GroupListItems />
                </DisclosurePanel>
            </Disclosure>
        </div>
        <div class="flex hidden flex-col overflow-hidden lg:block lg:h-full">
            <h2 class="text-xl font-bold">My groups</h2>
            <GroupListItems />
        </div>
    </div>
</template>
