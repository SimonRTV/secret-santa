<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CircleAlert, Check, LoaderCircle, Plus, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { update } from '@/routes/members';

const MaxLength = 2000;
const suggestions = [
    'Un bon livre',
    'Une sortie ensemble',
    'Du chocolat',
    'Un jeu de société',
    'Surprenez-moi !',
];

const props = defineProps<{ memberId: string; wishes: string | null }>();

type Wish = { key: number; text: string };
let nextKey = 0;
const toWishes = (value: string | null): Wish[] =>
    (value ?? '')
        .split('\n')
        .map((text) => text.trim())
        .filter(Boolean)
        .map((text) => ({ key: nextKey++, text }));

const items = ref<Wish[]>(toWishes(props.wishes));
const draft = ref('');
const draftInput = ref<HTMLInputElement>();
const status = ref<'idle' | 'saving' | 'saved' | 'error'>('idle');
const errorMessage = ref('');
let lastSaved = serialize();
let saveTimer: number | undefined;
let latestSave = 0;

const length = computed(() => serialize().length);
const remainingSuggestions = computed(() =>
    suggestions.filter(
        (suggestion) => !items.value.some((item) => item.text === suggestion),
    ),
);

function serialize(): string {
    return items.value
        .map((item) => item.text.trim())
        .filter(Boolean)
        .join('\n');
}

function save(): void {
    window.clearTimeout(saveTimer);
    const wishes = serialize();

    if (wishes === lastSaved) {
        return;
    }

    const thisSave = ++latestSave;
    router.visit(update(props.memberId), {
        data: { wishes },
        preserveScroll: true,
        preserveState: true,
        onStart: () => (status.value = 'saving'),
        onSuccess: () => {
            lastSaved = wishes;
            status.value = 'saved';
        },
        onError: (errors) => {
            status.value = 'error';
            errorMessage.value = errors.wishes ?? 'L’enregistrement a échoué.';
        },
        onFinish: () => {
            if (thisSave === latestSave && status.value === 'saving') {
                status.value = 'error';
                errorMessage.value =
                    'Pas de connexion ? Vos idées seront enregistrées à la prochaine modification.';
            }
        },
    });
}

function scheduleSave(delay = 900): void {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(save, delay);
}

async function add(text = draft.value): Promise<void> {
    const wish = text.trim();

    if (!wish) {
        return;
    }

    items.value.push({ key: nextKey++, text: wish });
    draft.value = '';
    save();
    await nextTick();
    draftInput.value?.focus();
}

function remove(index: number): void {
    const [removed] = items.value.splice(index, 1);
    save();
    toast('Idée retirée', {
        description: removed.text,
        action: {
            label: 'Annuler',
            onClick: () => {
                items.value.splice(
                    Math.min(index, items.value.length),
                    0,
                    removed,
                );
                save();
            },
        },
    });
}

watch(
    () => props.memberId,
    () => {
        items.value = toWishes(props.wishes);
        lastSaved = serialize();
    },
);

onBeforeUnmount(() => {
    window.clearTimeout(saveTimer);
});
</script>

<template>
    <div class="grid gap-4">
        <TransitionGroup
            v-if="items.length"
            tag="ol"
            name="wish"
            class="grid gap-2"
        >
            <li v-for="(item, index) in items" :key="item.key" class="wish-row">
                <span class="wish-number holiday-serif" aria-hidden="true">{{
                    index + 1
                }}</span>
                <label :for="`wish-${item.key}`" class="sr-only"
                    >Idée {{ index + 1 }}</label
                >
                <input
                    :id="`wish-${item.key}`"
                    v-model="item.text"
                    class="wish-input"
                    maxlength="300"
                    @input="scheduleSave()"
                    @blur="save"
                    @keydown.enter.prevent="draftInput?.focus()"
                />
                <button
                    type="button"
                    class="grid size-8 shrink-0 place-items-center rounded-md text-[var(--ink-muted)] hover:bg-[var(--paper-line)]/50 hover:text-[var(--cranberry)]"
                    :aria-label="`Retirer « ${item.text} »`"
                    @click="remove(index)"
                >
                    <X class="size-4" />
                </button>
            </li>
        </TransitionGroup>

        <form class="flex gap-2" @submit.prevent="add()">
            <label for="new-wish" class="sr-only"
                >Nouvelle idée de cadeau</label
            >
            <input
                id="new-wish"
                ref="draftInput"
                v-model="draft"
                class="holiday-input"
                maxlength="300"
                enterkeyhint="enter"
                :placeholder="
                    items.length
                        ? 'Une autre idée ?'
                        : 'Un roman policier, un lien vers un article…'
                "
            />
            <button
                class="holiday-primary shrink-0 px-4"
                :disabled="!draft.trim() || length >= MaxLength"
            >
                <Plus class="size-4" /><span class="max-sm:sr-only"
                    >Ajouter</span
                >
            </button>
        </form>

        <div
            v-if="items.length < 3 && remainingSuggestions.length"
            class="flex flex-wrap items-center gap-2"
        >
            <span class="holiday-hint">Besoin d’inspiration ?</span>
            <button
                v-for="suggestion in remainingSuggestions"
                :key="suggestion"
                type="button"
                class="holiday-chip border-dashed font-medium transition-colors hover:border-[var(--pine)]"
                @click="add(suggestion)"
            >
                <Plus class="size-3" /> {{ suggestion }}
            </button>
        </div>

        <p
            class="flex min-h-5 items-center gap-1.5 text-[0.8125rem] text-[var(--ink-muted)]"
            role="status"
        >
            <template v-if="status === 'saving'"
                ><LoaderCircle class="size-3.5 animate-spin" />
                Enregistrement…</template
            >
            <template v-else-if="status === 'saved'"
                ><Check class="size-3.5 text-[var(--pine-soft)]" /> Enregistré
                automatiquement</template
            >
            <template v-else-if="status === 'error'"
                ><CircleAlert class="size-3.5 text-[var(--cranberry)]" />
                <span class="text-[var(--cranberry)]">{{
                    errorMessage
                }}</span></template
            >

            <span v-if="length > MaxLength * 0.8" class="ml-auto tabular-nums"
                >{{ length }} / {{ MaxLength }}</span
            >
        </p>
    </div>
</template>

<style scoped>
.wish-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    border: 1px solid var(--paper-line);
    border-radius: var(--radius-control);
    background: var(--card);
    padding: 0.35rem 0.35rem 0.35rem 0.75rem;
    transition: border-color 0.15s;
}
.wish-row:focus-within {
    border-color: var(--pine);
}
.wish-number {
    width: 1.2rem;
    color: var(--cranberry);
    font-weight: 600;
    text-align: center;
}
.wish-input {
    flex: 1;
    min-width: 0;
    background: transparent;
    padding: 0.35rem 0;
    font-size: 1rem;
    outline: none;
}
.wish-enter-active,
.wish-leave-active {
    transition:
        opacity 0.2s,
        transform 0.2s;
}
.wish-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}
.wish-leave-to {
    opacity: 0;
    transform: translateX(10px);
}
</style>
