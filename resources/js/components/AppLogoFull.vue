<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const props = withDefaults(defineProps<{ background?: 'light' | 'dark' }>(), {
    background: 'light',
});
const logoUrl = computed(() =>
    props.background === 'dark'
        ? (page.props.branding.darkFullLogoUrl ??
          page.props.branding.fullLogoUrl)
        : page.props.branding.fullLogoUrl,
);
</script>

<template>
    <img
        v-if="logoUrl"
        :src="logoUrl"
        class="object-contain"
        alt=""
        aria-hidden="true"
    />
    <AppLogoIcon v-else class="object-contain" />
</template>
