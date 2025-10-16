<script setup>
defineProps({
    selected: Boolean,
    text: String,
});
</script>

<template>
    <button
        :class="[
            'cursor-pointer px-3 py-2.5 text-sm outline-none focus:outline-none',
            selected
                ? 'border-b-2 border-indigo-800 bg-white text-indigo-600'
                : 'text-gray-700',
        ]"
    >
        {{ text }}
    </button>
</template>
