<script setup lang="ts">
const props = defineProps<{
    image: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="mb-3 cursor-pointer rounded-lg p-2 hover:bg-gray-100">
        <div class="flex items-start gap-3 px-3 py-2">
            <img class="max-w-[68px] rounded-full" :src="image" alt="?" />
            <div>
                <h3 class="text-lg font-black">{{ title }}</h3>
                <div class="text-sm">{{ description }}</div>
            </div>
        </div>
    </div>
</template>
