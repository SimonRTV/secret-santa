<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Shuffle } from '@lucide/vue';
import { ref } from 'vue';
import PrivateLinkDialog from '@/components/PrivateLinkDialog.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { draw, index, show } from '@/routes/families';
import { store } from '@/routes/families/members';
import { show as invitation } from '@/routes/members';

const props = defineProps<{
    family: { id: string; name: string };
    drawn: boolean;
    members: { id: string; name: string; hasWishes: boolean }[];
}>();
const memberForm = useForm({ name: '' });
const drawForm = useForm({});
const confirmDraw = ref(false);
function addMember(): void {
    memberForm.submit(store(props.family.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
}
function drawNames(): void {
    drawForm.submit(draw(props.family.id), {
        preserveScroll: true,
        onFinish: () => {
            confirmDraw.value = false;
        },
    });
}
</script>
<template>
    <FamilyLayout>
        <Link
            :href="index()"
            class="inline-flex items-center gap-2 text-sm hover:underline"
            ><ArrowLeft class="size-4" /> Mes familles</Link
        >
        <h1
            class="holiday-serif mt-4 text-5xl font-semibold tracking-tight wrap-anywhere"
        >
            {{ family.name }}
        </h1>
        <p class="mt-3 mb-4 text-[var(--ink-muted)]">
            Gardez votre lien d’organisation pour retrouver cet échange sur un
            autre appareil.
        </p>
        <PrivateLinkDialog
            :path="show.url(family.id)"
            label="Mon lien d’organisation"
            title="Mon lien d’organisation"
            description="Ce lien permet de gérer la famille et d’accéder aux invitations. Gardez-le pour vous."
        />
        <section
            class="mt-8 rounded-xl border border-[var(--paper-line)] p-5 sm:p-7"
        >
            <h2 class="holiday-serif text-3xl">
                Les participants
                <small class="text-xl">({{ members.length }})</small>
            </h2>
            <p class="mt-2 text-sm text-[var(--ink-muted)]">
                Envoyez son invitation à chaque personne pour qu’elle indique
                ses envies et découvre son tirage.
            </p>
            <form v-if="!drawn" class="mt-5" @submit.prevent="addMember">
                <label for="participant" class="holiday-label mb-1.5"
                    >Ajouter une personne</label
                >
                <div class="flex flex-wrap gap-2">
                    <input
                        id="participant"
                        v-model="memberForm.name"
                        class="holiday-input min-w-32 flex-1"
                        placeholder="Prénom"
                        required
                        maxlength="80"
                    /><button
                        :disabled="memberForm.processing"
                        class="holiday-primary"
                    >
                        <Plus class="size-4" /> Ajouter
                    </button>
                </div>
                <p v-if="memberForm.errors.name" class="holiday-error">
                    {{ memberForm.errors.name }}
                </p>
            </form>
            <ul class="mt-4 divide-y divide-[var(--paper-line)]" role="list">
                <li
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-3 py-4"
                >
                    <div class="min-w-0">
                        <Link
                            :href="invitation(member.id)"
                            class="font-semibold break-words hover:underline"
                            >{{ member.name }}</Link
                        >
                        <p class="text-sm text-[var(--ink-muted)]">
                            {{
                                member.hasWishes
                                    ? 'Envies renseignées'
                                    : 'Liste à remplir'
                            }}
                        </p>
                    </div>
                    <PrivateLinkDialog
                        :path="invitation.url(member.id)"
                        label="Invitation"
                        :title="`Invitation de ${member.name}`"
                        :description="`Transmettez ce lien uniquement à ${member.name}. Il donne accès à sa liste et à son tirage.`"
                    />
                </li>
            </ul>
        </section>
        <section
            class="mt-6 rounded-xl border border-[var(--paper-line)] p-5 sm:p-7"
        >
            <h2 class="holiday-serif text-3xl">Le tirage au sort</h2>
            <p class="mt-2 text-[var(--ink-muted)]">
                {{
                    drawn
                        ? 'Le tirage est fait ! Chaque personne peut découvrir son destinataire depuis son invitation.'
                        : members.length < 2
                          ? 'Réunissez au moins deux participants pour lancer le tirage.'
                          : 'Tout le monde est là ? Chaque participant recevra le nom d’un proche à gâter.'
                }}
            </p>
            <Dialog v-if="!drawn" v-model:open="confirmDraw">
                <DialogTrigger as-child
                    ><button
                        class="holiday-primary mt-4"
                        :disabled="members.length < 2"
                    >
                        <Shuffle class="size-4" /> Lancer le tirage
                    </button></DialogTrigger
                >
                <DialogContent class="holiday-dialog">
                    <DialogTitle class="holiday-serif text-3xl"
                        >Faire le tirage ?</DialogTitle
                    >
                    <DialogDescription
                        >La liste des {{ members.length }} participants sera
                        définitive. Chacun pourra encore modifier ses
                        envies.</DialogDescription
                    >
                    <button
                        class="holiday-primary"
                        :disabled="drawForm.processing"
                        @click="drawNames"
                    >
                        Confirmer le tirage
                    </button>
                </DialogContent>
            </Dialog>
            <p
                v-for="error in drawForm.errors"
                :key="error"
                role="alert"
                class="holiday-error"
            >
                {{ error }}
            </p>
        </section>
    </FamilyLayout>
</template>
