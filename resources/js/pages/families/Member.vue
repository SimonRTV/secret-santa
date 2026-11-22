<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Gift, MailOpen } from '@lucide/vue';
import { ref, watch } from 'vue';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { update } from '@/routes/members';

const props = defineProps<{
    familyName: string;
    person: { id: string; name: string; wishes: string | null };
    recipient: { name: string; wishes: string | null } | null;
}>();
const form = useForm({ wishes: props.person.wishes ?? '' });
const revealed = ref(false);

watch(
    () => props.person.id,
    () => {
        form.wishes = props.person.wishes ?? '';
        revealed.value = false;
    },
);
</script>
<template>
    <FamilyLayout>
        <p class="mb-3 text-sm font-semibold text-[var(--cranberry)]">
            {{ familyName }}
        </p>
        <h1
            class="holiday-serif text-5xl font-semibold tracking-tight wrap-anywhere"
        >
            Bonjour {{ person.name }} !
        </h1>
        <section class="mt-7 rounded-xl border border-[var(--paper-line)] p-6">
            <h2 class="holiday-serif text-3xl">Mes envies de cadeaux</h2>
            <form
                class="mt-3 grid gap-4"
                @submit.prevent="
                    form.submit(update(person.id), { preserveScroll: true })
                "
            >
                <label class="holiday-label"
                    >Qu’est-ce qui vous ferait plaisir ?<textarea
                        v-model="form.wishes"
                        class="holiday-input resize-y"
                        rows="5"
                        maxlength="2000"
                        placeholder="Un roman policier, du thé aux épices, une sortie en famille…"
                    />
                </label>
                <p v-if="form.errors.wishes" class="holiday-error">
                    {{ form.errors.wishes }}
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <button class="holiday-primary" :disabled="form.processing">
                        <Gift class="size-4" /> Enregistrer mes envies</button
                    ><span role="status" class="text-sm">{{
                        form.recentlySuccessful
                            ? 'Vos envies sont enregistrées.'
                            : ''
                    }}</span>
                </div>
            </form>
        </section>
        <section class="mt-5 rounded-xl border border-[var(--paper-line)] p-6">
            <h2 class="holiday-serif text-3xl">Mon tirage secret</h2>
            <p v-if="!recipient" class="mt-2 text-[var(--ink-muted)]">
                Revenez avec ce lien après le tirage pour découvrir à qui offrir
                un cadeau.
            </p>
            <button
                v-else-if="!revealed"
                class="holiday-primary mt-4"
                @click="revealed = true"
            >
                <MailOpen class="size-4" /> Découvrir mon destinataire
            </button>
            <div v-else class="mt-3">
                <p>
                    Vous offrez un cadeau à <strong>{{ recipient.name }}</strong
                    >.
                </p>
                <h3 class="mt-4 font-semibold">Ses envies</h3>
                <p class="mt-1 wrap-anywhere whitespace-pre-wrap">
                    {{
                        recipient.wishes ||
                        'Sa liste est encore vide. Vous pourrez la consulter ici plus tard.'
                    }}
                </p>
                <button
                    class="mt-4 text-sm underline"
                    @click="revealed = false"
                >
                    Refermer l’enveloppe
                </button>
            </div>
        </section>
    </FamilyLayout>
</template>
