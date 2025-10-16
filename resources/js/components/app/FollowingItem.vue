<script setup lang="ts">
const props = defineProps<{
    image: string;
    title: string;
}>();
</script>

<template>
    <div class="mb-3 cursor-pointer rounded-lg p-2 hover:bg-gray-100">
        <div class="flex items-center gap-2 px-3 py-2">
            <img class="w-[68px] rounded-full" :src="image" alt="?" />
            <div>
                <h3 class="font-black">{{ title }}</h3>
            </div>
        </div>
    </div>
</template>
