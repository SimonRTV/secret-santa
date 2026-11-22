<script setup lang="ts">
import {
    ArrowDown,
    Gift,
    Mail,
    PenLine,
    Snowflake,
    UsersRound,
} from '@lucide/vue';
import gifts from '../../images/christmas-gifts.webp';
import CreateFamilyDialog from '@/components/CreateFamilyDialog.vue';
import FrontendFooter from '@/components/frontend/FrontendFooter.vue';
import FrontendHeader from '@/components/frontend/FrontendHeader.vue';

const steps = [
    {
        title: 'Réunissez la famille',
        description:
            'Créez votre famille et invitez petits et grands à rejoindre l’échange.',
        icon: UsersRound,
        ink: '#36573f',
        tint: '#e8eddf',
    },
    {
        title: 'Glissez vos envies',
        description:
            'Chacun remplit sa liste d’idées pour guider son Père Noël secret.',
        icon: PenLine,
        ink: '#99433b',
        tint: '#f6e7df',
    },
    {
        title: 'Laissez faire le hasard',
        description:
            'Lancez le tirage au sort et découvrez à qui offrir un cadeau.',
        icon: Gift,
        ink: '#866824',
        tint: '#f5ecd4',
    },
];
</script>

<template>
    <div class="christmas min-h-screen">
        <FrontendHeader is-home-page />
        <main class="mx-auto max-w-[1240px] px-4 pt-6 sm:px-8 sm:pt-9 lg:px-12">
            <section
                class="postcard relative isolate overflow-hidden rounded-xl border border-[var(--paper-line)]"
            >
                <div class="postal-edge h-[7px]" aria-hidden="true" />
                <div
                    class="grid items-center gap-4 px-6 pt-10 pb-6 sm:px-10 md:grid-cols-[1fr_1fr] md:gap-0 md:px-10 md:py-12 lg:px-14 lg:py-14"
                >
                    <div class="relative z-10">
                        <p
                            class="flex items-center gap-2 text-xs font-semibold tracking-[.14em] text-[var(--cranberry)] uppercase"
                        >
                            <Mail class="size-4" /> Échange de cadeaux
                        </p>
                        <h1
                            class="holiday-serif mt-5 text-[clamp(3.4rem,5.7vw,5.25rem)] leading-[.98] font-semibold tracking-[-.06em]"
                        >
                            Secret Santa<br /><em
                                class="font-normal text-[var(--cranberry)]"
                                >en famille.</em
                            >
                        </h1>
                        <p
                            class="mt-5 max-w-[25rem] text-[1.04rem] leading-relaxed text-[var(--ink-muted)]"
                        >
                            Réunissez vos proches, partagez vos envies et
                            découvrez à qui vous ferez plaisir à Noël.
                        </p>
                        <div
                            class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-4"
                        >
                            <CreateFamilyDialog />
                            <a
                                href="#workflow"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold hover:underline"
                                >Voir les étapes <ArrowDown class="size-4"
                            /></a>
                        </div>
                    </div>
                    <div
                        class="gift-art relative mx-auto w-full max-w-[530px] py-3 md:py-0"
                    >
                        <img
                            :src="gifts"
                            alt="Trois cadeaux entourés de houx et de branches de sapin"
                            width="1200"
                            height="800"
                            fetchpriority="high"
                            class="relative w-full mix-blend-multiply brightness-[1.03]"
                        />
                    </div>
                </div>
            </section>

            <section
                id="workflow"
                aria-labelledby="steps-heading"
                class="scroll-mt-24 pt-11 pb-14 sm:pt-14 sm:pb-20"
            >
                <div class="flex items-center gap-5">
                    <h2
                        id="steps-heading"
                        class="holiday-serif shrink-0 text-[clamp(1.8rem,3vw,2.65rem)] leading-tight font-semibold tracking-[-.045em]"
                    >
                        Comment ça marche ?
                    </h2>
                    <span class="h-px grow bg-[var(--paper-line)]"></span
                    ><Snowflake
                        class="size-5 text-[var(--cranberry)]"
                        aria-hidden="true"
                    />
                </div>
                <ol class="mt-6 grid gap-4 sm:grid-cols-3 lg:gap-5">
                    <li
                        v-for="(step, i) in steps"
                        :key="step.title"
                        class="step-card rounded-xl border border-[var(--paper-line)] p-5 sm:p-6"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="stamp-icon grid size-12 place-items-center"
                                :style="{
                                    color: step.ink,
                                    backgroundColor: step.tint,
                                }"
                                ><component
                                    :is="step.icon"
                                    class="size-6"
                                    :stroke-width="1.6"
                            /></span>
                            <span
                                class="text-[10px] font-semibold tracking-[.14em] text-[var(--ink-muted)] uppercase"
                                >Étape 0{{ i + 1 }}</span
                            >
                        </div>
                        <h3
                            class="holiday-serif mt-4 text-[1.52rem] leading-tight font-semibold tracking-[-.035em]"
                        >
                            {{ step.title }}
                        </h3>
                        <p
                            class="mt-2 max-w-[34ch] text-[.93rem] leading-relaxed text-[var(--ink-muted)]"
                        >
                            {{ step.description }}
                        </p>
                    </li>
                </ol>
            </section>
        </main>
        <FrontendFooter is-home-page />
    </div>
</template>

<style scoped>
.postcard {
    background: #f6f1e5;
    box-shadow: 0 2px 4px #46331d08;
}
.step-card {
    background: #fffdf8;
}
.stamp-icon {
    border: 1px dashed currentColor;
    outline: 3px solid var(--paper);
    outline-offset: -3px;
    border-radius: 0.32rem;
    transform: rotate(-4deg);
}
li:nth-child(2) .stamp-icon {
    transform: rotate(4deg);
}
</style>
