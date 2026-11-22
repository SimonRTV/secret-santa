<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowRight, Gift } from '@lucide/vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { store } from '@/routes/families';

withDefaults(defineProps<{ label?: string }>(), {
    label: 'Organiser un échange',
});

const open = ref(false);
const form = useForm({ name: '', first_name: '' });

function submit(): void {
    form.submit(store(), {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <button type="button" class="holiday-primary">
                <Gift class="size-[18px]" :stroke-width="1.8" /> {{ label }}
            </button>
        </DialogTrigger>
        <DialogContent class="holiday-dialog gap-6 p-6 sm:p-8">
            <div class="grid gap-1.5 pr-6">
                <DialogTitle class="holiday-serif text-[1.9rem] leading-tight"
                    >Un nouvel échange</DialogTitle
                >
                <DialogDescription class="text-[var(--ink-muted)]"
                    >Pas de compte ni d’e-mail : ce navigateur se souviendra de
                    votre échange.</DialogDescription
                >
            </div>
            <form class="grid gap-4" @submit.prevent="submit">
                <label class="holiday-label"
                    >Nom de l’échange
                    <input
                        v-model="form.name"
                        class="holiday-input"
                        maxlength="80"
                        required
                        placeholder="Noël chez les Martin"
                        :aria-invalid="!!form.errors.name"
                    />
                    <span v-if="form.errors.name" class="holiday-error">{{
                        form.errors.name
                    }}</span>
                </label>
                <label class="holiday-label"
                    >Votre prénom
                    <input
                        v-model="form.first_name"
                        class="holiday-input"
                        maxlength="80"
                        autocomplete="given-name"
                        required
                        placeholder="Camille"
                        :aria-invalid="!!form.errors.first_name"
                    />
                    <span class="holiday-hint"
                        >Vous participez aussi au tirage.</span
                    >
                    <span v-if="form.errors.first_name" class="holiday-error">{{
                        form.errors.first_name
                    }}</span>
                </label>
                <button
                    class="holiday-primary mt-1"
                    :disabled="form.processing"
                >
                    Créer l’échange <ArrowRight class="size-4" />
                </button>
            </form>
        </DialogContent>
    </Dialog>
</template>
