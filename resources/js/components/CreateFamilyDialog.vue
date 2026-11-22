<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowRight, Gift } from '@lucide/vue';
import { ref } from 'vue';
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { store } from '@/routes/families';

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
        <DialogTrigger as-child
            ><button class="holiday-primary">
                <Gift class="size-[18px]" /> Créer ma famille
                <ArrowRight class="ml-1 size-4" /></button
        ></DialogTrigger>
        <DialogContent class="holiday-dialog p-7">
            <DialogTitle class="holiday-serif text-3xl"
                >Créer ma famille</DialogTitle
            >
            <DialogDescription
                >Donnez un nom à votre échange. Vous en serez la première
                personne inscrite.</DialogDescription
            >
            <form class="grid gap-4" @submit.prevent="submit">
                <label class="holiday-label"
                    >Nom de la famille<input
                        v-model="form.name"
                        class="holiday-input"
                        maxlength="80"
                        required
                        placeholder="Famille Martin"
                    /><span v-if="form.errors.name" class="holiday-error">{{
                        form.errors.name
                    }}</span></label
                >
                <label class="holiday-label"
                    >Votre prénom<input
                        v-model="form.first_name"
                        class="holiday-input"
                        maxlength="80"
                        autocomplete="given-name"
                        required
                        placeholder="Camille"
                    /><span
                        v-if="form.errors.first_name"
                        class="holiday-error"
                        >{{ form.errors.first_name }}</span
                    ></label
                >
                <button
                    class="holiday-primary mt-2"
                    :disabled="form.processing"
                >
                    Créer la famille <ArrowRight class="size-4" />
                </button>
            </form>
        </DialogContent>
    </Dialog>
</template>
