<script setup lang="ts">
import { Link2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    path: string;
    label: string;
    title: string;
    description: string;
}>();
const address = ref('');
const status = ref('');
const input = ref<HTMLInputElement>();

function prepare(open: boolean): void {
    if (open) {
        address.value = new URL(props.path, window.location.origin).href;
        status.value = '';
    }
}

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(address.value);
        status.value = 'Lien copié.';
    } catch {
        input.value?.focus();
        input.value?.select();
        status.value = 'Copiez le lien sélectionné.';
    }
}
</script>
<template>
    <Dialog @update:open="prepare">
        <DialogTrigger as-child
            ><button
                class="holiday-outline px-3 py-2 text-sm"
                :aria-label="title"
            >
                <Link2 class="size-4" />{{ label }}
            </button></DialogTrigger
        >
        <DialogContent class="holiday-dialog">
            <DialogTitle class="holiday-serif pr-5 text-2xl">{{
                title
            }}</DialogTitle>
            <DialogDescription>{{ description }}</DialogDescription>
            <label class="holiday-label"
                >Lien privé<input
                    ref="input"
                    :value="address"
                    class="holiday-input"
                    readonly
                    @focus="input?.select()"
            /></label>
            <button class="holiday-primary" @click="copy">
                Copier le lien
            </button>
            <p role="status" class="text-sm">{{ status }}</p>
        </DialogContent>
    </Dialog>
</template>
