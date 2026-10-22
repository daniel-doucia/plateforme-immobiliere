<script setup>
import { computed } from 'vue';

const props = defineProps({ points: { type: Array, required: true } });

const gauche = 30;
const droite = 14;
const haut = 14;
const bas = 104;
const largeur = 300;

const max = computed(() => Math.max(1, ...props.points.map((p) => p.valeur)));

const x = (i) =>
    props.points.length === 1
        ? (gauche + largeur - droite) / 2
        : gauche + (i * (largeur - gauche - droite)) / (props.points.length - 1);

const y = (valeur) => bas - (valeur / max.value) * (bas - haut);

const ligne = computed(() => props.points.map((p, i) => `${x(i)},${y(p.valeur)}`).join(' '));
const aire = computed(() => `${x(0)},${bas} ${ligne.value} ${x(props.points.length - 1)},${bas}`);
</script>

<template>
    <svg viewBox="0 0 300 130" class="w-full" role="img" aria-label="Évolution du nombre d'annonces créées par mois">
        <line :x1="gauche" :x2="largeur - droite" :y1="bas" :y2="bas" class="stroke-gray-300" stroke-width="1" />
        <line :x1="gauche" :x2="largeur - droite" :y1="haut" :y2="haut" class="stroke-gray-200" stroke-width="1" stroke-dasharray="3 3" />
        <text :x="gauche - 6" :y="bas + 3" text-anchor="end" font-size="8" class="fill-gray-500">0</text>
        <text :x="gauche - 6" :y="haut + 3" text-anchor="end" font-size="8" class="fill-gray-500">{{ max }}</text>

        <polygon :points="aire" class="fill-brand-100" opacity="0.7" />
        <polyline :points="ligne" fill="none" class="stroke-brand-600" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

        <g v-for="(p, i) in points" :key="p.label">
            <circle :cx="x(i)" :cy="y(p.valeur)" r="3" class="fill-white stroke-brand-600" stroke-width="2" />
            <text :x="x(i)" :y="y(p.valeur) - 7" text-anchor="middle" font-size="8" font-weight="700" class="fill-brand-900">{{ p.valeur }}</text>
            <text :x="x(i)" y="122" text-anchor="middle" font-size="8" class="fill-gray-500">{{ p.label }}</text>
        </g>
    </svg>
</template>
