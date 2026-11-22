<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, UsersRound } from '@lucide/vue';
import CreateFamilyDialog from '@/components/CreateFamilyDialog.vue';
import FamilyLayout from '@/layouts/FamilyLayout.vue';
import { show } from '@/routes/families';

defineProps<{ families: { id: string; name: string }[] }>();
</script>
<template>
    <FamilyLayout>
        <h1 class="holiday-serif text-5xl font-semibold tracking-tight">
            Mes familles
        </h1>
        <p class="my-5 text-[var(--ink-muted)]">
            Retrouvez les échanges que vous avez créés dans ce navigateur. Pour
            participer à un autre échange, ouvrez votre lien d’invitation.
        </p>
        <ul
            v-if="families.length"
            class="mb-6 divide-y divide-[var(--paper-line)] rounded-xl border border-[var(--paper-line)] px-5"
        >
            <li v-for="family in families" :key="family.id">
                <Link
                    :href="show(family.id)"
                    class="flex items-center gap-3 py-5 font-semibold"
                    ><UsersRound class="size-5" /><span
                        class="flex-1 break-words"
                        >{{ family.name }}</span
                    ><ArrowRight class="size-4"
                /></Link>
            </li>
        </ul>
        <p
            v-else
            class="mb-6 rounded-lg border border-dashed border-[var(--paper-line)] p-5"
        >
            Vous n’avez pas encore créé de famille ici.
        </p>
        <CreateFamilyDialog />
    </FamilyLayout>
</template>
