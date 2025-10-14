<script setup lang="ts">

const props = defineProps<{
    image: string;
    title: string;
    description: string;
}>();

</script>

<template>
    <div class="mb-3 hover:bg-gray-100 p-2 rounded-lg cursor-pointer">
        <div class="flex items-start gap-3 py-2 px-3">
            <img class="max-w-[68px] rounded-full" :src="image" alt="?">
            <div>
                <h3 class=" font-black text-lg">{{ title }}</h3>
                <div class="text-sm">{{ description }}</div>
            </div>
        </div>
    </div>
</template>