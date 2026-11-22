<script setup lang="ts">
import { computed } from 'vue';
import { getInitials } from '@/composables/useInitials';
import { stampColors } from '@/lib/exchange';

const props = withDefaults(
    defineProps<{ name: string; seed?: string; size?: 'sm' | 'md' | 'lg' }>(),
    { seed: undefined, size: 'md' },
);
const colors = computed(() => stampColors(props.seed ?? props.name));
</script>

<template>
    <span
        aria-hidden="true"
        class="stamp-avatar holiday-serif"
        :class="`stamp-avatar--${size}`"
        :style="{ color: colors.ink, backgroundColor: colors.tint }"
        >{{ getInitials(name) }}</span
    >
</template>

<style scoped>
.stamp-avatar {
    display: inline-grid;
    flex-shrink: 0;
    place-items: center;
    border: 1px dashed currentColor;
    border-radius: 0.4rem;
    outline: 3px solid var(--card);
    outline-offset: -4px;
    font-weight: 600;
    letter-spacing: -0.02em;
}
.stamp-avatar--sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}
.stamp-avatar--md {
    width: 2.6rem;
    height: 2.6rem;
    font-size: 1rem;
}
.stamp-avatar--lg {
    width: 3.5rem;
    height: 3.5rem;
    font-size: 1.35rem;
}
</style>
