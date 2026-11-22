<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, MailCheck, MailOpen } from '@lucide/vue';
import CreateFamilyDialog from '@/components/CreateFamilyDialog.vue';
import StampAvatar from '@/components/exchange/StampAvatar.vue';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { countdownLabel } from '@/lib/exchange';
import { show } from '@/routes/families';
import { show as invitation } from '@/routes/members';

defineProps<{
    families: {
        id: string;
        name: string;
        membersCount: number;
        drawn: boolean;
        exchangeOn: string | null;
    }[];
    invitations: {
        id: string;
        name: string;
        familyName: string;
        drawn: boolean;
        revealed: boolean;
    }[];
}>();
</script>

<template>
    <FamilyLayout>
        <header class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <h1
                    class="holiday-serif text-[clamp(2.4rem,7vw,3.4rem)] leading-none font-semibold tracking-[-0.035em]"
                >
                    Mes échanges
                </h1>
            </div>
            <CreateFamilyDialog
                v-if="families.length || invitations.length"
                label="Nouvel échange"
            />
        </header>

        <div
            v-if="!families.length && !invitations.length"
            class="mt-10 grid max-w-md gap-5"
        >
            <svg viewBox="0 0 120 80" class="w-28 -rotate-3" aria-hidden="true">
                <rect width="120" height="80" rx="6" fill="#cdb68b" />
                <path
                    d="M0 6 60 46 120 6V74a6 6 0 0 1-6 6H6a6 6 0 0 1-6-6Z"
                    fill="#e6d5b2"
                />
                <path
                    d="M0 4a4 4 0 0 1 4-4h112a4 4 0 0 1 4 4L60 48Z"
                    fill="#dbc69f"
                />
                <circle cx="60" cy="47" r="11" fill="#7c8379" />
                <circle
                    cx="60"
                    cy="47"
                    r="7.5"
                    fill="none"
                    stroke="#a9b0a6"
                    stroke-dasharray="2 2"
                />
            </svg>
            <div>
                <h2
                    class="holiday-serif text-[1.7rem] leading-tight font-semibold"
                >
                    Aucune enveloppe pour l’instant
                </h2>
                <p class="mt-2 text-[var(--ink-muted)]">
                    Organisez un échange, ou ouvrez le lien d’invitation que
                    l’on vous a envoyé&nbsp;: il s’affichera ici, sur cet
                    appareil.
                </p>
            </div>
            <div><CreateFamilyDialog /></div>
        </div>

        <section
            v-if="families.length"
            aria-labelledby="organised-heading"
            class="mt-9"
        >
            <h2
                id="organised-heading"
                class="text-sm font-semibold text-[var(--ink-muted)]"
            >
                J’organise
            </h2>
            <ul class="mt-3 grid gap-3">
                <li v-for="family in families" :key="family.id">
                    <Link
                        :href="show(family.id)"
                        class="exchange-link paper-card"
                    >
                        <div class="min-w-0 flex-1">
                            <p
                                class="holiday-serif text-xl font-semibold wrap-anywhere"
                            >
                                {{ family.name }}
                            </p>
                            <p
                                class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[var(--ink-muted)]"
                            >
                                <span
                                    >{{ family.membersCount }}
                                    {{
                                        family.membersCount > 1
                                            ? 'participants'
                                            : 'participant'
                                    }}</span
                                >
                                <span
                                    class="status-dot"
                                    :class="family.drawn ? 'is-done' : ''"
                                    >{{
                                        family.drawn
                                            ? 'Tirage fait'
                                            : 'Tirage à faire'
                                    }}</span
                                >
                                <span
                                    v-if="family.exchangeOn"
                                    class="inline-flex items-center gap-1"
                                    ><CalendarDays class="size-3.5" />
                                    {{
                                        countdownLabel(family.exchangeOn)
                                    }}</span
                                >
                            </p>
                        </div>
                        <ArrowRight class="arrow size-5 shrink-0" />
                    </Link>
                </li>
            </ul>
        </section>

        <section
            v-if="invitations.length"
            aria-labelledby="invited-heading"
            class="mt-9"
        >
            <h2
                id="invited-heading"
                class="text-sm font-semibold text-[var(--ink-muted)]"
            >
                Mes invitations
            </h2>
            <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                <li v-for="member in invitations" :key="member.id">
                    <Link
                        :href="invitation(member.id)"
                        class="exchange-link paper-card h-full"
                    >
                        <StampAvatar :name="member.name" :seed="member.id" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold wrap-anywhere">
                                {{ member.name }}
                            </p>
                            <p
                                class="text-sm wrap-anywhere text-[var(--ink-muted)]"
                            >
                                {{ member.familyName }}
                            </p>
                            <p
                                class="mt-1.5 inline-flex items-center gap-1.5 text-[0.8125rem] font-semibold"
                                :class="
                                    member.drawn && !member.revealed
                                        ? 'text-[var(--cranberry)]'
                                        : 'text-[var(--ink-muted)]'
                                "
                            >
                                <component
                                    :is="member.revealed ? MailCheck : MailOpen"
                                    class="size-3.5"
                                />
                                {{
                                    member.revealed
                                        ? 'Enveloppe ouverte'
                                        : member.drawn
                                          ? 'Une enveloppe vous attend'
                                          : 'En attente du tirage'
                                }}
                            </p>
                        </div>
                        <ArrowRight class="arrow size-5 shrink-0" />
                    </Link>
                </li>
            </ul>
        </section>
    </FamilyLayout>
</template>

<style scoped>
.exchange-link {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 1.1rem 1.25rem;
    transition:
        border-color 0.15s,
        transform 0.15s;
}
.exchange-link:hover {
    border-color: color-mix(in srgb, var(--pine) 35%, var(--paper-line));
}
.exchange-link:focus-visible {
    outline: 2px solid var(--pine);
    outline-offset: 2px;
}
.exchange-link .arrow {
    color: var(--ink-muted);
    transition: transform 0.15s;
}
.exchange-link:hover .arrow {
    transform: translateX(3px);
    color: var(--pine);
}
.status-dot {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.status-dot::before {
    content: '';
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: var(--gold);
}
.status-dot.is-done::before {
    background: var(--pine-soft);
}
</style>
