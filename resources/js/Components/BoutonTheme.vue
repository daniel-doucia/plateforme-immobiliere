<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    // 'icone' : bouton rond de l'en-tête ; 'menu' : ligne du menu latéral
    variante: { type: String, default: 'icone' },
});

const sombre = ref(false);

onMounted(() => {
    sombre.value = document.documentElement.classList.contains('dark');
});

const basculer = () => {
    sombre.value = !sombre.value;
    document.documentElement.classList.toggle('dark', sombre.value);

    try {
        localStorage.setItem('theme', sombre.value ? 'dark' : 'light');
    } catch (e) {
        /* stockage indisponible : le choix vaut pour cette visite seulement */
    }
};
</script>

<template>
    <button
        type="button"
        :aria-pressed="sombre"
        :aria-label="sombre ? 'Passer en mode clair' : 'Passer en mode nuit'"
        :title="sombre ? 'Mode clair' : 'Mode nuit'"
        :class="variante === 'menu'
            ? 'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-brand-100 transition hover:bg-white/10'
            : 'flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-brand-800 transition hover:bg-brand-50'"
        @click="basculer"
    >
        <svg v-if="sombre" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
        <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
        </svg>
        <span v-if="variante === 'menu'">{{ sombre ? 'Mode clair' : 'Mode nuit' }}</span>
    </button>
</template>
