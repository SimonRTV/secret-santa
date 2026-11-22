<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        path: string;
        title: string;
        description: string;
        /** Builds the text sent along with the link. */
        message?: (url: string) => string;
        subject?: string;
    }>(),
    { message: undefined, subject: 'Lutin mystère' },
);

const open = defineModel<boolean>('open', { default: false });
const copied = ref(false);
const input = ref<HTMLInputElement>();
const copyButton = ref<HTMLButtonElement>();

function focusCopyButton(event: Event): void {
    event.preventDefault();
    copyButton.value?.focus();
}
let copiedTimer: number | undefined;

const url = computed(() =>
    typeof window === 'undefined'
        ? props.path
        : new URL(props.path, window.location.origin).href,
);
const text = computed(() => props.message?.(url.value) ?? url.value);
const canShareNatively = computed(
    () =>
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function',
);
const channels = computed(() => [
    {
        label: 'WhatsApp',
        href: `https://wa.me/?text=${encodeURIComponent(text.value)}`,
        external: true,
    },
    {
        label: 'SMS',
        href: `sms:?&body=${encodeURIComponent(text.value)}`,
        external: false,
    },
    {
        label: 'E-mail',
        href: `mailto:?subject=${encodeURIComponent(props.subject)}&body=${encodeURIComponent(text.value)}`,
        external: false,
    },
]);

async function copy(): Promise<void> {
    try {
        if (!navigator.clipboard || !window.isSecureContext) {
            throw new Error('Clipboard unavailable');
        }

        await navigator.clipboard.writeText(url.value);
    } catch {
        input.value?.focus();
        input.value?.select();

        if (!document.execCommand('copy')) {
            return;
        }
    }

    copied.value = true;
    window.clearTimeout(copiedTimer);
    copiedTimer = window.setTimeout(() => (copied.value = false), 2200);
}

async function shareNatively(): Promise<void> {
    try {
        await navigator.share({ title: props.subject, text: text.value });
    } catch {
        // The visitor closed the share sheet.
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger v-if="$slots.default" as-child><slot /></DialogTrigger>
        <DialogContent
            @open-auto-focus="focusCopyButton"
            class="holiday-dialog gap-5 p-6 sm:p-7"
        >
            <div class="grid gap-1.5 pr-6">
                <DialogTitle class="holiday-serif text-2xl leading-tight">{{
                    title
                }}</DialogTitle>
                <DialogDescription class="text-[var(--ink-muted)]">{{
                    description
                }}</DialogDescription>
            </div>

            <div class="flex gap-2">
                <label class="sr-only" :for="`link-${path}`">Lien privé</label>
                <input
                    :id="`link-${path}`"
                    ref="input"
                    :value="url"
                    class="holiday-input text-sm"
                    readonly
                    @focus="input?.select()"
                />
                <button
                    ref="copyButton"
                    type="button"
                    class="holiday-primary shrink-0 px-3.5"
                    :aria-label="copied ? 'Lien copié' : 'Copier le lien'"
                    @click="copy"
                >
                    <Transition name="swap" mode="out-in">
                        <Check v-if="copied" class="size-4" />
                        <Copy v-else class="size-4" />
                    </Transition>
                    <span class="hidden sm:inline">{{
                        copied ? 'Copié' : 'Copier'
                    }}</span>
                </button>
            </div>

            <p class="flex flex-wrap items-center gap-x-1 gap-y-2 text-sm">
                <span class="mr-1 text-[var(--ink-muted)]">Ou envoyer par</span>
                <a
                    v-for="channel in channels"
                    :key="channel.label"
                    :href="channel.href"
                    :target="channel.external ? '_blank' : undefined"
                    :rel="channel.external ? 'noopener noreferrer' : undefined"
                    class="holiday-outline px-3 py-1.5"
                    >{{ channel.label }}</a
                >
                <button
                    v-if="canShareNatively"
                    type="button"
                    class="holiday-ghost"
                    @click="shareNatively"
                >
                    Autres…
                </button>
            </p>
            <p role="status" class="sr-only">
                {{ copied ? 'Lien copié.' : '' }}
            </p>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.swap-enter-active,
.swap-leave-active {
    transition:
        opacity 0.15s,
        transform 0.15s;
}
.swap-enter-from,
.swap-leave-to {
    opacity: 0;
    transform: scale(0.6);
}
</style>
