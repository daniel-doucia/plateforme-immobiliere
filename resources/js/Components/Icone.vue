<script setup>
defineProps({ nom: { type: String, required: true } });

const chemins = {
    accueil: 'M3 11l9-8 9 8M5 10v10h14V10',
    annonces: 'M4 6h16M4 12h16M4 18h10',
    favoris: 'M12 3l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.8 6.1 21l1.2-6.5L2.5 9.9l6.6-.9z',
    alertes: 'M18 16v-5a6 6 0 10-12 0v5l-2 2h16zM10 20a2 2 0 004 0',
    utilisateur: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    utilisateurs: 'M9 11a3 3 0 100-6 3 3 0 000 6zM3 20a6 6 0 0112 0M16 5.2a3 3 0 010 5.6M21 20a6 6 0 00-4-5.6',
    bouclier: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
    etiquette: 'M3 12V4h8l10 10-8 8zM7.5 7.5h.01',
    epingle: 'M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11zM12 12a2 2 0 100-4 2 2 0 000 4z',
    graphique: 'M4 20V10M10 20V4M16 20v-8M22 20H2',
    deconnexion: 'M15 17l5-5-5-5M20 12H9M12 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7',
};
</script>

<template>
    <svg
        class="h-5 w-5 shrink-0"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="chemins[nom] ?? chemins.accueil" />
    </svg>
</template>
