<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    size: { type: String as () => 'sm' | 'md' | 'lg', default: 'md' },
    clearable: { type: Boolean, default: false },
    className: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'enter']);

function onInput(e: Event) {
    const v = (e.target as HTMLInputElement).value;
    emit('update:modelValue', v);
}

function onEnter() {
    emit('enter');
}

function clear() {
    emit('update:modelValue', '');
}

const inputClass = computed(() => {
    const base = 'text-input';
    const sizeClass =
        props.size === 'sm'
            ? 'text-input--sm'
            : props.size === 'lg'
              ? 'text-input--lg'
              : 'text-input--md';
    return `${base} ${sizeClass} ${props.className}`.trim();
});
</script>

<template>
    <div class="text-input-wrapper">
        <input
            :value="modelValue"
            :placeholder="placeholder"
            :class="inputClass"
            @input="onInput"
            @keyup.enter="onEnter"
            type="text"
            autocomplete="off"
        />
        <button
            v-if="clearable && modelValue"
            type="button"
            class="text-input__clear"
            @click="clear"
            aria-label="Clear"
        >
            ✕
        </button>
    </div>
</template>

<style scoped>
.text-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.text-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #fff;
    color: #111827;
    outline: none;
}

.text-input:focus {
    border-color: #112a51;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.text-input--sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.text-input--md {
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
}

.text-input--lg {
    padding: 0.75rem 1rem;
    font-size: 1.125rem;
}

.text-input__clear {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    color: #6b7280;
    font-size: 0.9rem;
}
</style>
