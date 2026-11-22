<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { RotateCcw, Shuffle } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import { toast } from 'vue-sonner';
import { AlertDialogAction, AlertDialogCancel } from 'reka-ui';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useConfetti } from '@/composables/useConfetti';
import { draw } from '@/routes/families';
import { destroy as cancelDraw } from '@/routes/families/draw';
import type { ExchangeDetails, Participant } from '@/types';

const MinimumParticipants = 3;
const ShuffleDuration = 1700;

const props = defineProps<{
    family: ExchangeDetails;
    members: Participant[];
}>();

const page = usePage();
const { burst } = useConfetti();
const shuffling = ref(false);
const shownName = ref('');
const cancelling = ref(false);
const panel = ref<HTMLElement>();
let shuffleTimer: number | undefined;

const enoughPeople = computed(
    () => props.members.length >= MinimumParticipants,
);
const withWishes = computed(
    () => props.members.filter((member) => member.wishCount > 0).length,
);
const revealed = computed(
    () => props.members.filter((member) => member.revealed).length,
);
const error = computed(
    () => (page.props.errors as Record<string, string>).draw,
);

function startShuffle(): void {
    shuffling.value = true;
    let index = 0;
    const names = props.members.map((member) => member.name);
    shuffleTimer = window.setInterval(() => {
        index =
            (index + 1 + Math.floor(Math.random() * (names.length - 1))) %
            names.length;
        shownName.value = names[index];
    }, 85);
}

function stopShuffle(): void {
    window.clearInterval(shuffleTimer);
    shuffling.value = false;
}

function drawNames(): void {
    const startedAt = performance.now();
    let succeeded = false;
    startShuffle();
    router.visit(draw(props.family.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            succeeded = true;
            window.setTimeout(
                () => {
                    stopShuffle();
                    const box = panel.value?.getBoundingClientRect();
                    burst(
                        box
                            ? { x: box.left + box.width / 2, y: box.top + 80 }
                            : undefined,
                    );
                    toast.success(
                        'C’est tiré ! Envoyez à chacun son invitation.',
                    );
                },
                Math.max(0, ShuffleDuration - (performance.now() - startedAt)),
            );
        },
        onFinish: () => {
            if (!succeeded) {
                stopShuffle();
            }
        },
    });
}

function cancel(): void {
    router.visit(cancelDraw(props.family.id), {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (cancelling.value = true),
        onFinish: () => (cancelling.value = false),
    });
}

onBeforeUnmount(() => window.clearInterval(shuffleTimer));
</script>

<template>
    <section
        ref="panel"
        aria-labelledby="draw-heading"
        class="draw-panel paper-card relative overflow-hidden"
    >
        <div class="p-5 sm:p-7">
            <h2
                id="draw-heading"
                class="holiday-serif text-[1.85rem] leading-tight font-semibold"
            >
                {{
                    family.drawn && !shuffling
                        ? 'Les noms sont tirés'
                        : 'Le tirage au sort'
                }}
            </h2>

            <Transition name="fade" mode="out-in">
                <div
                    v-if="shuffling"
                    key="shuffling"
                    class="shuffle-stage"
                    role="status"
                    aria-live="polite"
                >
                    <span class="sr-only">Tirage en cours…</span>
                    <div class="shuffle-card holiday-serif" aria-hidden="true">
                        <span :key="shownName" class="shuffle-name">{{
                            shownName
                        }}</span>
                    </div>
                    <p class="text-sm text-[var(--ink-muted)]">
                        On mélange les noms dans le bonnet…
                    </p>
                </div>

                <div
                    v-else-if="!family.drawn"
                    key="ready"
                    class="mt-4 grid gap-5"
                >
                    <p class="text-[var(--ink-muted)]">
                        <template v-if="enoughPeople"
                            >{{ members.length }} participants ·
                            {{
                                withWishes === 0
                                    ? 'aucune liste d’idées pour l’instant'
                                    : `${withWishes} ${withWishes > 1 ? 'listes' : 'liste'} d’idées`
                            }}. Chacun pourra encore compléter la sienne après
                            le tirage.</template
                        >
                        <template v-else
                            >Il faut au moins trois personnes pour que le tirage
                            reste une surprise.</template
                        >
                    </p>

                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <button
                                type="button"
                                class="holiday-primary justify-self-start"
                                :disabled="!enoughPeople"
                            >
                                <Shuffle class="size-4" /> Lancer le tirage
                            </button>
                        </AlertDialogTrigger>
                        <AlertDialogContent class="holiday-dialog">
                            <AlertDialogHeader>
                                <AlertDialogTitle class="holiday-serif text-2xl"
                                    >Tout le monde est là ?</AlertDialogTitle
                                >
                                <AlertDialogDescription
                                    class="text-[var(--ink-muted)]"
                                >
                                    Les {{ members.length }} participants
                                    recevront chacun le nom d’un proche. La
                                    liste sera figée, mais vous pourrez annuler
                                    le tirage si quelqu’un manque.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel
                                    class="holiday-outline px-4 py-2.5 text-sm"
                                    >Pas encore</AlertDialogCancel
                                >
                                <AlertDialogAction
                                    class="holiday-primary"
                                    @click="drawNames"
                                >
                                    <Shuffle class="size-4" /> Tirer au sort
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                    <p v-if="error" role="alert" class="holiday-error -mt-2">
                        {{ error }}
                    </p>
                </div>

                <div v-else key="drawn" class="mt-4 grid gap-5">
                    <p class="text-[var(--ink-muted)]">
                        Ici, vous voyez qui a ouvert son enveloppe, jamais qui
                        offre à qui.
                    </p>
                    <div class="grid gap-2">
                        <div
                            class="flex items-baseline justify-between gap-3 text-sm"
                        >
                            <span class="font-semibold"
                                >Enveloppes ouvertes</span
                            >
                            <span class="tabular-nums"
                                >{{ revealed }} / {{ members.length }}</span
                            >
                        </div>
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-[var(--paper-line)]/60"
                            role="progressbar"
                            :aria-valuenow="revealed"
                            aria-valuemin="0"
                            :aria-valuemax="members.length"
                            aria-label="Enveloppes ouvertes"
                        >
                            <div
                                class="h-full rounded-full bg-[var(--pine-soft)] transition-[width] duration-700"
                                :style="{
                                    width: `${(revealed / Math.max(members.length, 1)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>

                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <button
                                type="button"
                                class="holiday-ghost -ml-2 justify-self-start"
                                :disabled="cancelling"
                            >
                                <RotateCcw class="size-4" /> Annuler le tirage
                            </button>
                        </AlertDialogTrigger>
                        <AlertDialogContent class="holiday-dialog">
                            <AlertDialogHeader>
                                <AlertDialogTitle class="holiday-serif text-2xl"
                                    >Annuler le tirage ?</AlertDialogTitle
                                >
                                <AlertDialogDescription
                                    class="text-[var(--ink-muted)]"
                                >
                                    Vous pourrez de nouveau modifier la liste
                                    puis refaire un tirage.
                                    <template v-if="revealed > 0">
                                        {{
                                            revealed === 1
                                                ? 'Une personne a'
                                                : `${revealed} personnes ont`
                                        }}
                                        déjà découvert un nom&nbsp;:
                                        prévenez-les qu’il changera sans doute.
                                    </template>
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel
                                    class="holiday-outline px-4 py-2.5 text-sm"
                                    >Garder le tirage</AlertDialogCancel
                                >
                                <AlertDialogAction
                                    class="holiday-danger"
                                    @click="cancel"
                                    >Annuler le tirage</AlertDialogAction
                                >
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </Transition>
        </div>
    </section>
</template>

<style scoped>
.shuffle-stage {
    display: grid;
    justify-items: center;
    gap: 1rem;
    padding: 1.75rem 0 0.5rem;
}
.shuffle-card {
    display: grid;
    place-items: center;
    width: min(100%, 17rem);
    height: 6.5rem;
    border: 1px dashed var(--cranberry);
    border-radius: var(--radius-control);
    background: repeating-linear-gradient(
        -45deg,
        #fff 0 10px,
        #fbf5ea 10px 20px
    );
    box-shadow: var(--paper-shadow);
    animation: wobble 0.5s ease-in-out infinite alternate;
    overflow: hidden;
}
.shuffle-name {
    font-size: 1.9rem;
    font-weight: 600;
    color: var(--cranberry);
    animation: drop 0.085s ease-out;
}
@keyframes wobble {
    from {
        transform: rotate(-2deg);
    }
    to {
        transform: rotate(2deg);
    }
}
@keyframes drop {
    from {
        transform: translateY(-40%);
        opacity: 0.3;
    }
}
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.25s,
        transform 0.25s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
