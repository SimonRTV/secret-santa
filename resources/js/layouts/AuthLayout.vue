<script setup lang="ts">
import { onMounted } from 'vue';
import { applyAdminTheme } from '@/composables/useAdminTheme';
import SplitLayout from '@/layouts/auth/AuthSplitLayout.vue';

onMounted(() => applyAdminTheme('neutral'));

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <SplitLayout :title="title" :description="description">
        <slot />
    </SplitLayout>
</template>
