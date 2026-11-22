<script setup lang="ts">
import FrontendFooter from '@/components/frontend/FrontendFooter.vue';
import FrontendHeader from '@/components/frontend/FrontendHeader.vue';
import { Toaster } from '@/components/ui/sonner';

withDefaults(defineProps<{ wide?: boolean }>(), { wide: false });
</script>

<template>
    <div class="christmas flex min-h-screen flex-col">
        <FrontendHeader />
        <main
            class="mx-auto w-full flex-1 px-4 pt-7 pb-16 sm:px-8 sm:pt-10 sm:pb-20"
            :class="wide ? 'max-w-[1120px] lg:px-12' : 'max-w-3xl'"
        >
            <slot />
        </main>
        <FrontendFooter />
        <Toaster
            position="top-center"
            :toast-options="{ class: 'holiday-dialog' }"
        />
    </div>
</template>
