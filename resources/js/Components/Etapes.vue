<script setup>
defineProps({ courante: { type: Number, default: 1 } });

const etapes = ['Informations', 'Lieu et prix', 'Photos', 'Récapitulatif'];

const classe = (numero, courante) => {
    if (numero < courante) return 'bg-brand-600 text-white';
    if (numero === courante) return 'bg-accent-500 text-brand-950 ring-4 ring-accent-100';

    return 'bg-gray-200 text-gray-500';
};
</script>

<template>
    <ol class="flex items-center">
        <li v-for="(nom, i) in etapes" :key="nom" class="flex flex-1 items-center last:flex-none">
            <div class="flex flex-col items-center gap-1 sm:flex-row sm:gap-2">
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                    :class="classe(i + 1, courante)"
                >
                    {{ i + 1 < courante ? '✓' : i + 1 }}
                </span>
                <span class="text-xs font-medium sm:text-sm" :class="i + 1 === courante ? 'text-brand-800' : 'text-gray-500'">
                    {{ nom }}
                </span>
            </div>
            <span
                v-if="i < etapes.length - 1"
                class="mx-2 h-0.5 flex-1 rounded"
                :class="i + 1 < courante ? 'bg-brand-600' : 'bg-gray-200'"
            ></span>
        </li>
    </ol>
</template>
