<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import { ref } from 'vue';

const postCreation = ref(false);

</script>

<template>
    <div class="py-6">
        <div @click="postCreation = true" class="py-3 px-2 text-gray-500 border rounded-lg border-indigo-900 mb-4">
            Click here to create a new post
        </div>

        <div v-if="postCreation" class="flex justify-between items-center w-full gap-4">
            <div class="relative file-wrapper">
                <Button type="button" class="relative">
                    Attach files
                </Button>
                <input type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" multiple
                    accept="image/*,video/*" />
            </div>

            <div>
                <Button type="button" class="relative z-0">
                    Post
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.file-wrapper button {
    transition: transform 120ms ease, filter 120ms ease, box-shadow 120ms ease;
}

.file-wrapper:hover button,
.file-wrapper:focus-within button {
    transform: translateY(-2px);
    filter: brightness(0.95);
    box-shadow: 0 6px 18px rgba(16, 24, 40, 0.08);
}

.file-wrapper:active button {
    transform: translateY(0);
    filter: brightness(0.9);
    box-shadow: none;
}
</style>