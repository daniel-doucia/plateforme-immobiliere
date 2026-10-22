<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: { type: Array, required: true },
    titre: { type: String, default: 'Annonces' },
});

const total = computed(() => props.segments.reduce((somme, s) => somme + s.valeur, 0));

const pourcentage = (valeur) => (total.value ? Math.round((valeur / total.value) * 100) : 0);

// Chaque arc part de la fin du précédent, en commençant en haut du cercle
const arcs = computed(() => {
    let cumul = 0;

    return props.segments
        .filter((s) => s.valeur > 0)
        .map((s) => {
            const part = (s.valeur / total.value) * 100;
            const arc = { couleur: s.couleur, dash: `${part} ${100 - part}`, decalage: 25 - cumul };
            cumul += part;

            return arc;
        });
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-4">
        <svg viewBox="0 0 42 42" class="h-32 w-32 shrink-0" role="img" :aria-label="`Répartition des ${titre.toLowerCase()}`">
            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="6" />
            <circle
                v-for="(a, i) in arcs"
                :key="i"
                cx="21"
                cy="21"
                r="15.9155"
                fill="none"
                :stroke="a.couleur"
                stroke-width="6"
                :stroke-dasharray="a.dash"
                :stroke-dashoffset="a.decalage"
            />
            <text x="21" y="21.5" text-anchor="middle" font-size="7" font-weight="700" fill="#14352a">{{ total }}</text>
            <text x="21" y="27" text-anchor="middle" font-size="3.4" fill="#6b7280">{{ titre }}</text>
        </svg>

        <ul class="space-y-1.5 text-sm">
            <li v-for="s in segments" :key="s.label" class="flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: s.couleur }"></span>
                <span class="text-gray-700">{{ s.label }}</span>
                <span class="ms-auto ps-3 font-semibold text-gray-900">{{ pourcentage(s.valeur) }} %</span>
                <span class="text-gray-400">({{ s.valeur }})</span>
            </li>
        </ul>
    </div>
</template>
