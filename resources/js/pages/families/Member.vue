<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Gift, Link2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SealedEnvelope from '@/components/exchange/SealedEnvelope.vue';
import ShareLinkDialog from '@/components/exchange/ShareLinkDialog.vue';
import WishListEditor from '@/components/exchange/WishListEditor.vue';
import WishText from '@/components/exchange/WishText.vue';
import { useConfetti } from '@/composables/useConfetti';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { exchangeDateLine } from '@/lib/exchange';
import { reveal, show } from '@/routes/members';

const props = defineProps<{
    family: {
        name: string;
        budget: string | null;
        exchangeOn: string | null;
        drawn: boolean;
    };
    person: { id: string; name: string; wishes: string | null };
    recipient: { name: string; wishes: string[] } | null;
}>();

const { burst } = useConfetti();
const opening = ref(false);
const justOpened = ref(false);
const envelope = ref<HTMLElement>();
const details = computed(() =>
    [
        props.family.exchangeOn
            ? exchangeDateLine(props.family.exchangeOn)
            : null,
        props.family.budget ? `budget ${props.family.budget}` : null,
    ]
        .filter(Boolean)
        .join(' · '),
);

const envelopeState = computed(() => {
    if (opening.value) {
        return 'opening';
    }

    if (props.recipient) {
        return 'open';
    }

    return props.family.drawn ? 'sealed' : 'waiting';
});

function openEnvelope(): void {
    router.visit(reveal(props.person.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            opening.value = true;
            window.setTimeout(() => {
                const box = envelope.value?.getBoundingClientRect();
                burst(
                    box
                        ? {
                              x: box.left + box.width / 2,
                              y: box.top + box.height / 3,
                          }
                        : undefined,
                );
            }, 1100);
            window.setTimeout(() => {
                opening.value = false;
                justOpened.value = true;
            }, 1700);
        },
    });
}

watch(
    () => props.person.id,
    () => {
        opening.value = false;
        justOpened.value = false;
    },
);
</script>

<template>
    <FamilyLayout>
        <header>
            <p class="eyebrow wrap-anywhere">{{ family.name }}</p>
            <h1
                class="holiday-serif mt-2 text-[clamp(2.4rem,7vw,3.6rem)] leading-[1.02] font-semibold tracking-[-0.035em] wrap-anywhere"
            >
                Bonjour {{ person.name }}&nbsp;!
            </h1>
            <p
                v-if="details"
                class="mt-3 text-[var(--ink-muted)] first-letter:uppercase"
            >
                {{ details }}
            </p>
        </header>

        <section
            aria-labelledby="draw-heading"
            class="paper-card mt-8 overflow-hidden"
        >
            <div class="px-5 pt-6 pb-7 sm:px-8">
                <h2
                    id="draw-heading"
                    class="holiday-serif text-[1.7rem] leading-tight font-semibold"
                >
                    Mon tirage
                </h2>
                <p class="mt-1 text-[var(--ink-muted)]">
                    <template v-if="envelopeState === 'waiting'"
                        >Le tirage n’a pas encore eu lieu. Revenez sur ce lien
                        quand l’organisateur aura tiré les noms&nbsp;; en
                        attendant, notez vos idées ci-dessous.</template
                    >
                    <template v-else-if="envelopeState === 'sealed'"
                        >Les noms sont tirés&nbsp;! Assurez-vous que personne ne
                        regarde par-dessus votre épaule, puis brisez le
                        sceau.</template
                    >
                    <template v-else>Chut… c’est votre secret.</template>
                </p>

                <div ref="envelope">
                    <SealedEnvelope
                        :state="envelopeState"
                        :recipient-name="recipient?.name"
                        @open="openEnvelope"
                    />
                </div>
                <p
                    v-if="envelopeState === 'sealed'"
                    class="mt-4 text-center text-sm font-semibold"
                >
                    Touchez le sceau pour ouvrir
                </p>

                <Transition name="rise">
                    <div
                        v-if="recipient && envelopeState === 'open'"
                        class="mt-7 border-t border-dashed border-[var(--paper-line)] pt-6"
                    >
                        <h3 class="flex items-center gap-2 font-semibold">
                            <Gift class="size-4 text-[var(--cranberry)]" /> Les
                            idées de {{ recipient.name }}
                        </h3>
                        <ol
                            v-if="recipient.wishes.length"
                            class="mt-3 grid gap-2"
                        >
                            <li
                                v-for="(wish, index) in recipient.wishes"
                                :key="index"
                                class="flex gap-3 rounded-[var(--radius-control)] bg-[var(--paper-deep)] px-4 py-3"
                            >
                                <span
                                    class="holiday-serif font-semibold text-[var(--cranberry)]"
                                    >{{ index + 1 }}</span
                                >
                                <WishText :text="wish" />
                            </li>
                        </ol>
                        <p
                            v-else
                            class="mt-2 rounded-[var(--radius-control)] border border-dashed border-[var(--paper-line)] px-4 py-3 text-sm text-[var(--ink-muted)]"
                        >
                            {{ recipient.name }} n’a pas encore noté d’idées.
                            Repassez plus tard, sa liste s’affichera ici dès
                            qu’elle sera remplie.
                        </p>
                    </div>
                </Transition>
            </div>
        </section>

        <section
            aria-labelledby="wishes-heading"
            class="paper-card mt-5 px-5 pt-6 pb-5 sm:px-8"
        >
            <h2
                id="wishes-heading"
                class="holiday-serif text-[1.7rem] leading-tight font-semibold"
            >
                Mes idées de cadeaux
            </h2>
            <p class="mt-1 mb-5 text-[var(--ink-muted)]">
                Votre lutin mystère les découvrira avec votre nom.
            </p>
            <WishListEditor :member-id="person.id" :wishes="person.wishes" />
        </section>

        <aside
            class="mt-6 flex flex-wrap items-center justify-between gap-3 px-1 text-sm text-[var(--ink-muted)]"
        >
            <p>Ce lien est votre clé : gardez-le pour revenir.</p>
            <ShareLinkDialog
                :path="show.url(person.id)"
                title="Votre lien personnel"
                description="Ce navigateur s’en souvient. Gardez une copie pour l’ouvrir sur un autre appareil, mais ne le partagez pas : il révèle votre tirage."
                :subject="`Mon invitation · ${family.name}`"
            >
                <button type="button" class="holiday-ghost -mr-2">
                    <Link2 class="size-4" /> Copier mon lien
                </button>
            </ShareLinkDialog>
        </aside>
    </FamilyLayout>
</template>

<style scoped>
.rise-enter-active {
    transition:
        opacity 0.5s ease 0.2s,
        transform 0.5s ease 0.2s;
}
.rise-enter-from {
    opacity: 0;
    transform: translateY(10px);
}
</style>
