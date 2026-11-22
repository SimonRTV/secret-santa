<script setup lang="ts">
import FrontendHeader from '@/components/frontend/FrontendHeader.vue';
import FrontendFooter from '@/components/frontend/FrontendFooter.vue';
</script>

<template>
    <div class="christmas flex min-h-screen flex-col">
        <FrontendHeader />
        <main
            class="mx-auto w-full max-w-3xl flex-1 px-5 py-10 sm:px-8 sm:py-14"
        >
            <div aria-hidden="true" class="postal-edge mb-7 h-1.5 rounded" />
            <slot />
        </main>
        <FrontendFooter />
    </div>
</template>
