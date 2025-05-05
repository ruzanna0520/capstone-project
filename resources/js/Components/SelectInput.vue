<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

const onInput = (event) => {
    emit('update:modelValue', event.target.value);
};

defineExpose({ focus: () => select.value.focus() });
</script>

<template>
    <select
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
        :value="modelValue"
        @input="onInput"
        ref="select"
        :required="required"
        :disabled="disabled"
    >
        <option v-if="placeholder" value="" disabled>
            {{ placeholder }}
        </option>
        <option v-else-if="!required && !placeholder" value="">Все</option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
        >
            {{ option.label }}
        </option>
    </select>
</template>
