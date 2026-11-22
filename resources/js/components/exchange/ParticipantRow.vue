<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Check,
    Ellipsis,
    MailCheck,
    MailQuestion,
    PencilLine,
    Send,
    UserMinus,
} from '@lucide/vue';
import { nextTick, ref } from 'vue';
import ShareLinkDialog from '@/components/exchange/ShareLinkDialog.vue';
import StampAvatar from '@/components/exchange/StampAvatar.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { invitationMessage } from '@/lib/exchange';
import { destroy, update } from '@/routes/families/members';
import { show as invitation } from '@/routes/members';
import type { Participant } from '@/types';

const props = defineProps<{
    member: Participant;
    familyId: string;
    familyName: string;
    drawn: boolean;
}>();

const mode = ref<'idle' | 'renaming' | 'removing'>('idle');
const sharing = ref(false);
const removing = ref(false);
const nameInput = ref<HTMLInputElement>();
const form = useForm({ name: props.member.name });

async function startRenaming(): Promise<void> {
    form.name = props.member.name;
    form.clearErrors();
    mode.value = 'renaming';
    await nextTick();
    nameInput.value?.focus();
    nameInput.value?.select();
}

function rename(): void {
    if (form.name.trim() === props.member.name) {
        mode.value = 'idle';

        return;
    }

    form.submit(update([props.familyId, props.member.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (mode.value = 'idle'),
    });
}

function remove(): void {
    router.visit(destroy([props.familyId, props.member.id]), {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (removing.value = true),
        onFinish: () => {
            removing.value = false;
            mode.value = 'idle';
        },
    });
}
</script>

<template>
    <li class="participant-row" :class="{ 'is-removing': removing }">
        <div
            v-if="mode === 'removing'"
            class="flex flex-1 flex-wrap items-center gap-x-4 gap-y-2 py-1"
        >
            <p class="min-w-0 flex-1 text-sm">
                Retirer
                <strong class="wrap-anywhere">{{ member.name }}</strong> de
                l’échange ? Son lien d’invitation ne fonctionnera plus.
            </p>
            <div class="flex gap-1.5">
                <button
                    type="button"
                    class="holiday-danger px-3 py-1.5 text-sm"
                    :disabled="removing"
                    @click="remove"
                >
                    Retirer
                </button>
                <button
                    type="button"
                    class="holiday-ghost"
                    @click="mode = 'idle'"
                >
                    Annuler
                </button>
            </div>
        </div>

        <template v-else>
            <StampAvatar :name="member.name" :seed="member.id" />

            <form
                v-if="mode === 'renaming'"
                class="grid min-w-0 flex-1 gap-1"
                @submit.prevent="rename"
            >
                <div class="flex gap-1.5">
                    <label :for="`rename-${member.id}`" class="sr-only"
                        >Nouveau prénom</label
                    >
                    <input
                        :id="`rename-${member.id}`"
                        ref="nameInput"
                        v-model="form.name"
                        class="holiday-input py-1.5"
                        maxlength="80"
                        required
                        :aria-invalid="!!form.errors.name"
                        @keydown.esc.prevent="mode = 'idle'"
                    />
                    <button
                        class="holiday-primary px-3 py-1.5"
                        :disabled="form.processing"
                        aria-label="Enregistrer le prénom"
                    >
                        <Check class="size-4" />
                    </button>
                </div>
                <span v-if="form.errors.name" class="holiday-error">{{
                    form.errors.name
                }}</span>
            </form>

            <div v-else class="min-w-0 flex-1">
                <p
                    class="flex flex-wrap items-center gap-x-2 gap-y-0.5 font-semibold"
                >
                    <span class="wrap-anywhere">{{ member.name }}</span>
                    <span
                        v-if="member.isMine"
                        class="me-tag"
                        title="Invitation ouverte sur cet appareil"
                        >vous</span
                    >
                </p>
                <p
                    v-if="drawn"
                    class="status"
                    :class="
                        member.revealed
                            ? 'text-[var(--pine-soft)]'
                            : 'text-[var(--ink-muted)]'
                    "
                >
                    <component
                        :is="member.revealed ? MailCheck : MailQuestion"
                        class="size-3.5"
                    />
                    {{
                        member.revealed
                            ? 'Enveloppe ouverte'
                            : 'Pas encore ouverte'
                    }}
                </p>
                <p v-else class="status text-[var(--ink-muted)]">
                    {{
                        member.wishCount === 0
                            ? 'Pas encore d’idées'
                            : member.wishCount === 1
                              ? '1 idée de cadeau'
                              : `${member.wishCount} idées de cadeaux`
                    }}
                </p>
            </div>

            <div
                v-if="mode === 'idle'"
                class="flex shrink-0 items-center gap-1"
            >
                <Link
                    v-if="member.isMine"
                    :href="invitation(member.id)"
                    class="holiday-ghost hidden sm:inline-flex lg:hidden xl:inline-flex"
                    >Mon invitation <ArrowUpRight class="size-3.5"
                /></Link>
                <ShareLinkDialog
                    v-model:open="sharing"
                    :path="invitation.url(member.id)"
                    :title="`Inviter ${member.name}`"
                    :description="`Ce lien est réservé à ${member.name} : il donne accès à sa liste d’envies et à son tirage.`"
                    :message="
                        (url) => invitationMessage(member.name, familyName, url)
                    "
                    :subject="`Ton invitation · ${familyName}`"
                >
                    <button
                        type="button"
                        class="holiday-outline px-2.5 py-1.5 text-sm sm:border-transparent sm:bg-transparent sm:px-2 sm:text-[var(--cranberry)] sm:underline-offset-4 sm:hover:bg-transparent sm:hover:underline"
                        :aria-label="`Inviter ${member.name}`"
                    >
                        <Send
                            class="size-4 sm:hidden"
                            :stroke-width="1.8"
                        /><span class="hidden sm:inline">Inviter</span>
                    </button>
                </ShareLinkDialog>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="holiday-ghost px-2"
                            :aria-label="`Plus d’actions pour ${member.name}`"
                        >
                            <Ellipsis class="size-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="end"
                        class="holiday-dialog min-w-48"
                    >
                        <DropdownMenuItem v-if="member.isMine" as-child>
                            <Link :href="invitation(member.id)"
                                ><ArrowUpRight /> Ouvrir mon invitation</Link
                            >
                        </DropdownMenuItem>
                        <DropdownMenuItem @select="startRenaming"
                            ><PencilLine /> Renommer</DropdownMenuItem
                        >
                        <template v-if="!drawn">
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @select="mode = 'removing'"
                            >
                                <UserMinus /> Retirer de l’échange
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </template>
    </li>
</template>

<style scoped>
.participant-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-height: 4.5rem;
    padding: 0.75rem 0;
    transition: opacity 0.2s;
}
.participant-row.is-removing {
    opacity: 0.45;
}
.status {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.1rem;
    font-size: 0.8125rem;
}
.me-tag {
    border-radius: 999px;
    background: var(--gold-tint);
    padding: 0.05rem 0.5rem;
    color: var(--gold-ink);
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
</style>
