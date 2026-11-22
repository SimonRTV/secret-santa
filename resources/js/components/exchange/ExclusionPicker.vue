<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeftRight, Plus, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { destroy, store } from '@/routes/families/exclusions';
import type { Exclusion, Participant } from '@/types';

const props = defineProps<{
    familyId: string;
    members: Participant[];
    exclusions: Exclusion[];
    drawn: boolean;
}>();

const page = usePage();
const removingId = ref<number | null>(null);
const form = useForm({ member_id: '', excluded_member_id: '' });
const names = computed(
    () => new Map(props.members.map((member) => [member.id, member.name])),
);
const pairs = computed(() =>
    props.exclusions.map((exclusion) => ({
        id: exclusion.id,
        names: exclusion.memberIds.map((id) => names.value.get(id) ?? '?') as [
            string,
            string,
        ],
    })),
);
const partnerOptions = computed(() =>
    props.members.filter((member) => member.id !== form.member_id),
);
const alreadyPaired = computed(() =>
    props.exclusions.some(
        (exclusion) =>
            exclusion.memberIds.includes(form.member_id) &&
            exclusion.memberIds.includes(form.excluded_member_id),
    ),
);
const error = computed(
    () =>
        form.errors.member_id ??
        form.errors.excluded_member_id ??
        (page.props.errors as Record<string, string>).exclusion,
);

watch(
    () => form.member_id,
    (memberId) => {
        if (memberId === form.excluded_member_id) {
            form.excluded_member_id = '';
        }
    },
);

function add(): void {
    form.submit(store(props.familyId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => form.reset(),
    });
}

function remove(id: number): void {
    router.visit(destroy([props.familyId, id]), {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (removingId.value = id),
        onFinish: () => (removingId.value = null),
    });
}
</script>

<template>
    <div class="grid gap-4">
        <TransitionGroup
            v-if="pairs.length"
            tag="ul"
            name="chip"
            class="flex flex-wrap gap-2"
            aria-label="Exceptions"
        >
            <li
                v-for="pair in pairs"
                :key="pair.id"
                class="holiday-chip py-1 pr-1.5"
                :class="{ 'opacity-50': removingId === pair.id }"
            >
                <span class="wrap-anywhere">{{ pair.names[0] }}</span>
                <ArrowLeftRight
                    class="size-3.5 shrink-0 text-[var(--cranberry)]"
                    aria-label="et"
                />
                <span class="wrap-anywhere">{{ pair.names[1] }}</span>
                <button
                    v-if="!drawn"
                    type="button"
                    class="grid size-6 shrink-0 place-items-center rounded-full text-[var(--ink-muted)] hover:bg-[var(--paper-line)]/60 hover:text-[var(--pine)]"
                    :aria-label="`Supprimer l’exception entre ${pair.names[0]} et ${pair.names[1]}`"
                    :disabled="removingId === pair.id"
                    @click="remove(pair.id)"
                >
                    <X class="size-3.5" />
                </button>
            </li>
        </TransitionGroup>
        <p v-else-if="drawn" class="holiday-hint">
            Aucune exception pour ce tirage.
        </p>

        <form v-if="!drawn" class="grid gap-2" @submit.prevent="add">
            <div
                class="grid grid-cols-1 items-center gap-2 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto]"
            >
                <label class="sr-only" for="exclusion-first"
                    >Première personne</label
                >
                <select
                    id="exclusion-first"
                    v-model="form.member_id"
                    class="holiday-input"
                    required
                >
                    <option value="" disabled>Choisir…</option>
                    <option
                        v-for="member in members"
                        :key="member.id"
                        :value="member.id"
                    >
                        {{ member.name }}
                    </option>
                </select>
                <span class="hidden text-sm text-[var(--ink-muted)] sm:block"
                    >et</span
                >
                <label class="sr-only" for="exclusion-second"
                    >Seconde personne</label
                >
                <select
                    id="exclusion-second"
                    v-model="form.excluded_member_id"
                    class="holiday-input"
                    required
                    :disabled="!form.member_id"
                >
                    <option value="" disabled>
                        {{ form.member_id ? 'Choisir…' : 'puis…' }}
                    </option>
                    <option
                        v-for="member in partnerOptions"
                        :key="member.id"
                        :value="member.id"
                    >
                        {{ member.name }}
                    </option>
                </select>
                <button
                    class="holiday-outline px-3.5 py-2.5 text-sm"
                    :disabled="
                        form.processing ||
                        !form.member_id ||
                        !form.excluded_member_id ||
                        alreadyPaired
                    "
                >
                    <Plus class="size-4" /> Ajouter
                </button>
            </div>
            <p v-if="alreadyPaired" class="holiday-hint">
                Ces deux personnes ne peuvent déjà pas se tirer.
            </p>
            <p v-if="error" role="alert" class="holiday-error">{{ error }}</p>
        </form>
    </div>
</template>

<style scoped>
.chip-enter-active,
.chip-leave-active {
    transition:
        opacity 0.2s,
        transform 0.2s;
}
.chip-enter-from,
.chip-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
</style>
