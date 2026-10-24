<script setup>
defineProps({
    nom: { type: String, required: true },
    taille: { type: String, default: 'h-5 w-5' },
});

const chemins = {
    accueil: 'M3 11l9-8 9 8M5 10v10h14V10',
    maison: 'M3 11l9-8 9 8M5 10v10h14V10M10 20v-6h4v6',
    annonces: 'M4 6h16M4 12h16M4 18h10',
    favoris: 'M12 3l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.8 6.1 21l1.2-6.5L2.5 9.9l6.6-.9z',
    etoile: 'M12 3l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.8 6.1 21l1.2-6.5L2.5 9.9l6.6-.9z',
    alertes: 'M18 16v-5a6 6 0 10-12 0v5l-2 2h16zM10 20a2 2 0 004 0',
    utilisateur: 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    utilisateurs: 'M9 11a3 3 0 100-6 3 3 0 000 6zM3 20a6 6 0 0112 0M16 5.2a3 3 0 010 5.6M21 20a6 6 0 00-4-5.6',
    bouclier: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
    verifie: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6zM9 12l2 2 4-4',
    etiquette: 'M3 12V4h8l10 10-8 8zM7.5 7.5h.01',
    epingle: 'M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11zM12 12a2 2 0 100-4 2 2 0 000 4z',
    graphique: 'M4 20V10M10 20V4M16 20v-8M22 20H2',
    deconnexion: 'M15 17l5-5-5-5M20 12H9M12 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7',
    mail: 'M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zM22 6l-10 7L2 6',
    cadenas: 'M5 11h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2zM7 11V7a5 5 0 0110 0v4',
    oeil: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8zM12 15a3 3 0 100-6 3 3 0 000 6z',
    oeilBarre: 'M17.9 17.9A10.9 10.9 0 0112 20C5 20 1 12 1 12a18.5 18.5 0 015.1-5.9M9.9 4.2A9.1 9.1 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.2 3.2M14.1 14.1a3 3 0 11-4.2-4.2M1 1l22 22',
    telephone: 'M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z',
    fleche: 'M7 17L17 7M7 7h10v10',
    recherche: 'M11 19a8 8 0 100-16 8 8 0 000 16zM21 21l-4.3-4.3',
    coeur: 'M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z',
    plus: 'M12 5v14M5 12h14',
    chevron: 'M6 9l6 6 6-6',
    lit: 'M2 20V8M2 14h20v6M22 14v-2a3 3 0 00-3-3h-8v5M6.5 11.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
    immeuble: 'M4 21V4a1 1 0 011-1h9a1 1 0 011 1v17M15 9h4a1 1 0 011 1v11M2 21h20M8 7h3M8 11h3M8 15h3',
    terrain: 'M3 20h18M5 20l5-9 3 5 2-3 4 7',
    hotel: 'M4 21V3h16v18M2 21h20M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-5h4v5',
    billets: 'M2 6h20v12H2zM12 15a3 3 0 100-6 3 3 0 000 6z',
    echange: 'M7 4L3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7',
    carte: 'M9 4L3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14',
    bulle: 'M21 11.5a8.4 8.4 0 01-12.4 7.4L3 20.5l1.7-5.4A8.4 8.4 0 1121 11.5z',
    baignoire: 'M3 12h18v3a5 5 0 01-5 5H8a5 5 0 01-5-5zM6 12V6a2 2 0 014 0M5 20l-1 2M19 20l1 2',
    surface: 'M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7',
};
</script>

<template>
    <svg
        class="shrink-0"
        :class="taille"
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
