<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, KeyRound, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import DrawPanel from '@/components/exchange/DrawPanel.vue';
import ExchangeSettingsDialog from '@/components/exchange/ExchangeSettingsDialog.vue';
import ExclusionPicker from '@/components/exchange/ExclusionPicker.vue';
import ParticipantRow from '@/components/exchange/ParticipantRow.vue';
import ShareLinkDialog from '@/components/exchange/ShareLinkDialog.vue';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { exchangeDateLine } from '@/lib/exchange';
import { index, show } from '@/routes/families';
import { store } from '@/routes/families/members';
import type { ExchangeDetails, Exclusion, Participant } from '@/types';

const props = defineProps<{
    family: ExchangeDetails;
    members: Participant[];
    exclusions: Exclusion[];
    maxMembers: number;
}>();

const nameInput = ref<HTMLInputElement>();
const memberForm = useForm({ name: '' });
const isFull = computed(() => props.members.length >= props.maxMembers);
const details = computed(() =>
    [
        `${props.members.length} ${props.members.length > 1 ? 'participants' : 'participant'}`,
        props.family.exchangeOn
            ? exchangeDateLine(props.family.exchangeOn)
            : null,
        props.family.budget,
    ]
        .filter(Boolean)
        .join(' · '),
);

function addMember(): void {
    memberForm.submit(store(props.family.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            memberForm.reset();
            nameInput.value?.focus();
        },
    });
}

function organizerMessage(url: string): string {
    return `Voici le lien pour co-organiser notre Lutin mystère « ${props.family.name} » : ${url}`;
}
</script>

<template>
    <FamilyLayout wide>
        <Link :href="index()" class="holiday-ghost -ml-2"
            ><ArrowLeft class="size-4" /> Mes échanges</Link
        >

        <header
            class="mt-4 flex flex-wrap items-end justify-between gap-x-8 gap-y-5"
        >
            <div class="min-w-0">
                <h1
                    class="holiday-serif text-[clamp(2.4rem,6vw,3.6rem)] leading-[1.02] font-semibold tracking-[-0.035em] wrap-anywhere"
                >
                    {{ family.name }}
                </h1>
                <p class="mt-3 text-[var(--ink-muted)]">
                    {{ details }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <ShareLinkDialog
                    :path="show.url(family.id)"
                    title="Votre lien d’organisation"
                    description="Ce navigateur s’en souvient déjà. Gardez une copie pour retrouver l’échange sur un autre appareil ou le confier à un co-organisateur."
                    :message="organizerMessage"
                    :subject="`Organisation · ${family.name}`"
                >
                    <button
                        type="button"
                        class="holiday-outline px-3 py-2 text-sm"
                    >
                        <KeyRound class="size-4" /> Lien d’organisation
                    </button>
                </ShareLinkDialog>
                <ExchangeSettingsDialog :family="family" />
            </div>
        </header>

        <div
            class="mt-8 grid grid-cols-1 items-start gap-5 lg:mt-10 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-6"
        >
            <section
                aria-labelledby="members-heading"
                class="paper-card p-5 sm:p-7"
            >
                <h2
                    id="members-heading"
                    class="holiday-serif text-[1.85rem] leading-tight font-semibold"
                >
                    Qui participe ?
                </h2>

                <form
                    v-if="!family.drawn"
                    class="mt-5 grid gap-1.5"
                    @submit.prevent="addMember"
                >
                    <label for="participant" class="sr-only"
                        >Prénom de la personne à ajouter</label
                    >
                    <div class="flex gap-2">
                        <input
                            id="participant"
                            ref="nameInput"
                            v-model="memberForm.name"
                            class="holiday-input"
                            placeholder="Prénom, puis Entrée"
                            autocomplete="off"
                            enterkeyhint="done"
                            required
                            maxlength="80"
                            :disabled="isFull"
                            :aria-invalid="!!memberForm.errors.name"
                            aria-describedby="participant-error"
                        />
                        <button
                            class="holiday-primary shrink-0 px-4"
                            :disabled="memberForm.processing || isFull"
                        >
                            <Plus class="size-4" />
                            <span class="max-sm:sr-only">Ajouter</span>
                        </button>
                    </div>
                    <p
                        id="participant-error"
                        role="alert"
                        class="holiday-error empty:hidden"
                    >
                        {{ memberForm.errors.name }}
                    </p>
                </form>
                <p
                    v-else
                    class="mt-4 rounded-[var(--radius-control)] bg-[var(--gold-tint)]/60 px-4 py-3 text-sm"
                >
                    La liste est figée depuis le tirage. Annulez-le pour ajouter
                    ou retirer quelqu’un.
                </p>

                <TransitionGroup
                    tag="ul"
                    name="row"
                    role="list"
                    class="mt-3 divide-y divide-dashed divide-[var(--paper-line)]"
                >
                    <ParticipantRow
                        v-for="member in members"
                        :key="member.id"
                        :member="member"
                        :family-id="family.id"
                        :family-name="family.name"
                        :drawn="family.drawn"
                    />
                </TransitionGroup>
                <p
                    v-if="members.length < 3 && !family.drawn"
                    class="holiday-hint mt-3"
                >
                    Encore {{ 3 - members.length }}
                    {{ 3 - members.length > 1 ? 'personnes' : 'personne' }} et
                    le tirage sera possible.
                </p>
            </section>

            <div
                class="grid min-w-0 grid-cols-1 gap-5 lg:sticky lg:top-24 lg:gap-6"
            >
                <section
                    v-if="
                        members.length >= 2 &&
                        (!family.drawn || exclusions.length)
                    "
                    aria-labelledby="exclusions-heading"
                    class="px-1 py-2 sm:px-2 lg:px-7"
                >
                    <h2
                        id="exclusions-heading"
                        class="holiday-serif text-[1.5rem] leading-tight font-semibold"
                    >
                        Qui ne doit pas se tirer ?
                    </h2>
                    <p class="mt-1 mb-4 text-sm text-[var(--ink-muted)]">
                        Les couples, par exemple.
                    </p>
                    <ExclusionPicker
                        :family-id="family.id"
                        :members="members"
                        :exclusions="exclusions"
                        :drawn="family.drawn"
                    />
                </section>

                <DrawPanel :family="family" :members="members" />
            </div>
        </div>
    </FamilyLayout>
</template>

<style scoped>
.row-enter-active,
.row-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s ease;
}
.row-enter-from {
    opacity: 0;
    transform: translateY(-6px);
}
.row-leave-to {
    opacity: 0;
    transform: translateX(12px);
}
</style>
