<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { update } from '@/routes/members/email';

const props = defineProps<{ memberId: string; email: string | null }>();

const form = useForm({ email: props.email ?? '' });
const unchanged = computed(() => form.email.trim() === (props.email ?? ''));

function save(): void {
    form.submit(update(props.memberId), {
        preserveScroll: true,
        preserveState: true,
    });
}

function remove(): void {
    form.email = '';
    save();
}

watch(
    () => [props.memberId, props.email],
    () => {
        form.email = props.email ?? '';
        form.clearErrors();
    },
);
</script>

<template>
    <form class="grid gap-2" novalidate @submit.prevent="save">
        <label for="notice-email" class="sr-only">Adresse e-mail</label>
        <div class="flex gap-2">
            <input
                id="notice-email"
                v-model="form.email"
                type="email"
                class="holiday-input"
                placeholder="prenom@exemple.ch"
                autocomplete="email"
                inputmode="email"
                maxlength="255"
                :aria-invalid="!!form.errors.email"
                aria-describedby="notice-email-error"
            />
            <button
                class="holiday-primary shrink-0 px-4"
                :disabled="form.processing || unchanged"
            >
                Enregistrer
            </button>
        </div>
        <p
            id="notice-email-error"
            role="alert"
            class="holiday-error empty:hidden"
        >
            {{ form.errors.email }}
        </p>
        <button
            v-if="email"
            type="button"
            class="holiday-ghost -ml-2 justify-self-start"
            :disabled="form.processing"
            @click="remove"
        >
            Ne plus me prévenir
        </button>
    </form>
</template>
