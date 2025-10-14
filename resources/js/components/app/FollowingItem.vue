<script setup lang="ts">

const props = defineProps<{
    image: string;
    title: string;
}>();

</script>

<template>
    <div class="mb-3 hover:bg-gray-100 p-2 rounded-lg cursor-pointer">
        <div class="flex items-center gap-2 py-2 px-3">
            <img class="w-[68px] rounded-full" :src="image" alt="?">
            <div>
                <h3 class="font-black">{{ title }}</h3>
            </div>
        </div>
    </div>
</template>