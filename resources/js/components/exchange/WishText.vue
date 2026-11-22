<script setup lang="ts">
import { ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import { linkify } from '@/lib/exchange';

const props = defineProps<{ text: string }>();
const segments = computed(() => linkify(props.text));
</script>

<template>
    <span class="wrap-anywhere">
        <template v-for="(segment, index) in segments" :key="index">
            <a
                v-if="segment.href"
                :href="segment.href"
                target="_blank"
                rel="noopener noreferrer nofollow"
                class="inline-flex items-baseline gap-1 font-semibold text-[var(--cranberry)] underline decoration-[var(--cranberry)]/30 underline-offset-3 hover:decoration-[var(--cranberry)]"
                >{{ segment.text
                }}<ExternalLink
                    class="size-3 self-center"
                    aria-hidden="true"
                /><span class="sr-only">(nouvel onglet)</span></a
            >
            <template v-else>{{ segment.text }}</template>
        </template>
    </span>
</template>
