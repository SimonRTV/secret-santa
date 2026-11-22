<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Settings2, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { destroy, update } from '@/routes/families';
import type { ExchangeDetails } from '@/types';

const props = defineProps<{ family: ExchangeDetails }>();
const open = ref(false);
const confirmingDeletion = ref(false);
const deleting = ref(false);
const form = useForm({
    name: props.family.name,
    budget: props.family.budget ?? '',
    exchange_on: props.family.exchangeOn ?? '',
});

watch(open, (isOpen) => {
    if (isOpen) {
        form.defaults({
            name: props.family.name,
            budget: props.family.budget ?? '',
            exchange_on: props.family.exchangeOn ?? '',
        });
        form.reset();
        form.clearErrors();
        confirmingDeletion.value = false;
    }
});

function save(): void {
    form.transform((data) => ({
        ...data,
        budget: data.budget.trim() || null,
        exchange_on: data.exchange_on || null,
    })).submit(update(props.family.id), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}

function deleteExchange(): void {
    router.visit(destroy(props.family.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <button type="button" class="holiday-outline px-3 py-2 text-sm">
                <Settings2 class="size-4" /> Réglages
            </button>
        </DialogTrigger>
        <DialogContent
            class="holiday-dialog max-h-[calc(100dvh-2rem)] gap-6 overflow-y-auto p-6 sm:p-7"
        >
            <div class="grid gap-1.5 pr-6">
                <DialogTitle class="holiday-serif text-2xl"
                    >Réglages de l’échange</DialogTitle
                >
                <DialogDescription class="text-[var(--ink-muted)]"
                    >Le budget et la date s’affichent sur chaque
                    invitation.</DialogDescription
                >
            </div>
            <form class="grid gap-4" @submit.prevent="save">
                <label class="holiday-label"
                    >Nom de l’échange
                    <input
                        v-model="form.name"
                        class="holiday-input"
                        maxlength="80"
                        required
                        :aria-invalid="!!form.errors.name"
                    />
                    <span v-if="form.errors.name" class="holiday-error">{{
                        form.errors.name
                    }}</span>
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="holiday-label"
                        >Budget par cadeau
                        <input
                            v-model="form.budget"
                            class="holiday-input"
                            maxlength="40"
                            placeholder="Environ 30 CHF"
                            :aria-invalid="!!form.errors.budget"
                        />
                        <span v-if="form.errors.budget" class="holiday-error">{{
                            form.errors.budget
                        }}</span>
                    </label>
                    <label class="holiday-label"
                        >Date de l’échange
                        <input
                            v-model="form.exchange_on"
                            type="date"
                            class="holiday-input"
                            :aria-invalid="!!form.errors.exchange_on"
                        />
                        <span
                            v-if="form.errors.exchange_on"
                            class="holiday-error"
                            >{{ form.errors.exchange_on }}</span
                        >
                    </label>
                </div>
                <button
                    class="holiday-primary justify-self-start"
                    :disabled="form.processing || !form.isDirty"
                >
                    Enregistrer
                </button>
            </form>

            <div
                class="grid gap-3 border-t border-dashed border-[var(--paper-line)] pt-5"
            >
                <div>
                    <p class="text-sm font-semibold">Supprimer l’échange</p>
                    <p class="holiday-hint mt-0.5">
                        Les invitations, les listes d’envies et le tirage
                        disparaîtront pour tout le monde.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="!confirmingDeletion"
                        type="button"
                        class="holiday-outline px-3 py-2 text-sm text-[var(--cranberry)]"
                        @click="confirmingDeletion = true"
                    >
                        <Trash2 class="size-4" /> Supprimer…
                    </button>
                    <template v-else>
                        <button
                            type="button"
                            class="holiday-danger px-3 py-2 text-sm"
                            :disabled="deleting"
                            @click="deleteExchange"
                        >
                            Oui, tout supprimer
                        </button>
                        <button
                            type="button"
                            class="holiday-ghost"
                            @click="confirmingDeletion = false"
                        >
                            Annuler
                        </button>
                    </template>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
