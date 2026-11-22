<script setup lang="ts">
import { Eye, EyeOff, Gift } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    state: 'waiting' | 'sealed' | 'opening' | 'open';
    recipientName?: string | null;
}>();
const emit = defineEmits<{ open: [] }>();
const hidden = ref(false);
</script>

<template>
    <div class="envelope-stage" :class="`is-${state}`">
        <div class="envelope">
            <div class="envelope-back" />
            <div class="letter" :aria-hidden="state !== 'open'">
                <p
                    class="text-[0.7rem] font-semibold tracking-[0.16em] text-[var(--ink-muted)] uppercase"
                >
                    Vous offrez un cadeau à
                </p>
                <p
                    class="holiday-serif mt-1 text-[clamp(1.7rem,7vw,2.4rem)] leading-tight font-semibold wrap-anywhere text-[var(--cranberry)] transition-[filter] duration-300"
                    :class="{ 'blur-md select-none': hidden }"
                >
                    {{ recipientName ?? '…' }}
                </p>
            </div>
            <div class="envelope-pocket" />
            <div class="envelope-flap" />
            <button
                type="button"
                class="wax-seal"
                :disabled="state !== 'sealed'"
                :aria-label="
                    state === 'waiting'
                        ? 'Enveloppe pas encore prête'
                        : 'Ouvrir l’enveloppe'
                "
                @click="emit('open')"
            >
                <Gift class="size-6" :stroke-width="1.6" />
            </button>
        </div>
        <button
            v-if="state === 'open'"
            type="button"
            class="holiday-ghost mx-auto mt-4"
            :aria-pressed="hidden"
            @click="hidden = !hidden"
        >
            <component :is="hidden ? Eye : EyeOff" class="size-4" />
            {{ hidden ? 'Afficher le nom' : 'Cacher le nom' }}
        </button>
    </div>
</template>

<style scoped>
.envelope-stage {
    display: grid;
    padding-top: 1.75rem;
    transition: padding-top 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
}
/* Room for the opened flap, which is 41% as tall as the envelope is wide. */
.envelope-stage.is-opening,
.envelope-stage.is-open {
    padding-top: calc(min(43%, 9.5rem) + 0.75rem);
}
.envelope {
    position: relative;
    width: min(100%, 22rem);
    aspect-ratio: 3 / 2;
    margin-inline: auto;
    perspective: 1000px;
    filter: drop-shadow(0 14px 18px #46331d24);
}
.envelope-back,
.envelope-pocket,
.envelope-flap {
    position: absolute;
    border-radius: 0.5rem;
}
.envelope-back {
    inset: 0;
    background: #cdb68b;
}
.letter {
    position: absolute;
    right: 6%;
    bottom: 7%;
    left: 6%;
    z-index: 2;
    display: grid;
    align-content: center;
    min-height: 86%;
    padding: 0.75rem 1rem 2.5rem;
    border-radius: 0.35rem;
    background:
        linear-gradient(var(--card), var(--card)) padding-box,
        repeating-linear-gradient(
                120deg,
                #a54740 0 9px,
                var(--card) 9px 17px,
                #576c57 17px 26px,
                var(--card) 26px 34px
            )
            border-box;
    border: 4px solid transparent;
    text-align: center;
    transition: transform 0.75s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s;
}
.envelope-pocket {
    inset: 0;
    z-index: 3;
    background:
        linear-gradient(
            to top right,
            transparent 49.6%,
            #00000010 50%,
            transparent 50.4%
        ),
        linear-gradient(
            to top left,
            transparent 49.6%,
            #00000010 50%,
            transparent 50.4%
        ),
        #e6d5b2;
    clip-path: polygon(0 0, 50% 56%, 100% 0, 100% 100%, 0 100%);
}
.envelope-flap {
    top: 0;
    right: 0;
    left: 0;
    z-index: 4;
    height: 62%;
    background: #dbc69f;
    clip-path: polygon(0 0, 100% 0, 50% 100%);
    transform-origin: top;
    transition:
        transform 0.5s ease-in-out,
        z-index 0s linear 0.25s;
}
.wax-seal {
    position: absolute;
    top: 62%;
    left: 50%;
    z-index: 5;
    display: grid;
    place-items: center;
    width: 3.75rem;
    height: 3.75rem;
    border-radius: 47% 53% 50% 50%;
    background: radial-gradient(circle at 35% 30%, #c1544b, #8e2c26 70%);
    box-shadow:
        0 0 0 4px #8e2c2633,
        0 3px 6px #5a1a1640,
        inset 0 -3px 5px #00000030;
    color: #fde8d8;
    transform: translate(-50%, -50%);
    transition:
        transform 0.3s ease,
        opacity 0.3s ease;
    cursor: pointer;
}
.is-sealed .wax-seal {
    animation: pulse 2.4s ease-in-out infinite;
}
.is-sealed .wax-seal:hover,
.is-sealed .wax-seal:focus-visible {
    transform: translate(-50%, -50%) scale(1.08) rotate(-6deg);
    outline: none;
}
.is-sealed .wax-seal:focus-visible {
    box-shadow:
        0 0 0 3px var(--card),
        0 0 0 5px var(--pine);
}
.is-waiting .wax-seal {
    background: radial-gradient(circle at 35% 30%, #9aa197, #6f766c 70%);
    box-shadow:
        0 0 0 4px #6f766c26,
        0 3px 6px #2a2e2840,
        inset 0 -3px 5px #00000030;
    cursor: default;
}
.is-opening .wax-seal {
    animation: crack 0.45s ease-in forwards;
}
.is-open .wax-seal {
    opacity: 0;
    pointer-events: none;
}
.is-opening .envelope-flap,
.is-open .envelope-flap {
    transform: rotateX(180deg);
    z-index: 1;
}
.is-opening .letter,
.is-open .letter {
    transform: translateY(-52%);
}
.is-opening .letter {
    transition-delay: 0.9s;
}
.is-opening .envelope-flap {
    transition-delay: 0.4s, 0.65s;
}
@keyframes pulse {
    0%,
    100% {
        box-shadow:
            0 0 0 4px #8e2c2633,
            0 3px 6px #5a1a1640,
            inset 0 -3px 5px #00000030;
    }
    50% {
        box-shadow:
            0 0 0 10px #8e2c2600,
            0 3px 6px #5a1a1640,
            inset 0 -3px 5px #00000030;
    }
}
@keyframes crack {
    40% {
        transform: translate(-50%, -50%) scale(1.15) rotate(8deg);
    }
    100% {
        transform: translate(-50%, -30%) scale(0.6) rotate(-20deg);
        opacity: 0;
    }
}
</style>
