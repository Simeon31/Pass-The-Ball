<script setup>
defineProps({
    selected: Boolean,
    text: String,
})
</script>

<template>
    <button :class="[
        'px-3 py-2.5 outline-none focus:outline-none cursor-pointer text-sm',
        selected
            ? 'bg-white text-indigo-600 border-b-2 border-indigo-800 '
            : 'text-gray-700',
    ]">
        {{ text }}
    </button>
</template>