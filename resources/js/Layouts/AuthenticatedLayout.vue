<script setup>
import BoutonTheme from '@/Components/BoutonTheme.vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import Icone from '@/Components/Icone.vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const ouvert = ref(false);

// Le menu mobile se referme après chaque navigation
const retirer = router.on('navigate', () => (ouvert.value = false));
onBeforeUnmount(retirer);

const roles = computed(() => Object.values(page.props.auth.roles ?? []));
const permissions = computed(() => Object.values(page.props.auth.permissions ?? []));

const initiales = computed(() =>
    (page.props.auth.user.name ?? '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot[0].toUpperCase())
        .join(''),
);

const sections = computed(() => {
    const liste = [
        {
            titre: 'Général',
            liens: [
                { nom: 'Tableau de bord', route: 'dashboard', actif: 'dashboard', icone: 'accueil' },
                { nom: 'Parcourir les annonces', route: 'annonces.index', actif: 'annonces.*', icone: 'annonces' },
                { nom: 'Mes favoris', route: 'favoris.index', actif: 'favoris.*', icone: 'favoris' },
                { nom: 'Mes alertes', route: 'alertes.index', actif: 'alertes.*', icone: 'alertes' },
            ],
        },
    ];

    if (roles.value.includes('bailleur')) {
        liste.push({
            titre: 'Bailleur',
            liens: [
                {
                    nom: 'Mes annonces',
                    route: 'bailleur.annonces.index',
                    actif: ['bailleur.annonces.*', 'bailleur.commandes.*'],
                    icone: 'maison',
                },
                { nom: 'Mon profil bailleur', route: 'bailleur.profil.edit', actif: 'bailleur.profil.*', icone: 'utilisateur' },
            ],
        });
    }

    const administration = [];

    if (permissions.value.includes('annonces.moderer')) {
        administration.push({ nom: 'Modération', route: 'admin.moderation.index', actif: 'admin.moderation.*', icone: 'bouclier' });
    }

    if (roles.value.includes('admin')) {
        administration.push(
            { nom: 'Vue d\'ensemble', route: 'admin.dashboard', actif: 'admin.dashboard', icone: 'graphique' },
            { nom: 'Bailleurs', route: 'admin.bailleurs.index', actif: 'admin.bailleurs.*', icone: 'utilisateurs' },
            { nom: 'Vérifications', route: 'admin.verifications.index', actif: 'admin.verifications.*', icone: 'bouclier' },
            { nom: 'Tarifs', route: 'admin.forfaits.index', actif: 'admin.forfaits.*', icone: 'etiquette' },
            { nom: 'Villes et quartiers', route: 'admin.localisation.index', actif: 'admin.localisation.*', icone: 'epingle' },
            { nom: 'Messages', route: 'admin.messages.index', actif: 'admin.messages.*', icone: 'bouclier' },
        );
    }

    if (administration.length) {
        liste.push({ titre: 'Administration', liens: administration });
    }

    return liste;
});

const estActif = (lien) => [].concat(lien.actif).some((motif) => route().current(motif));
</script>

<template>
    <div class="min-h-screen bg-gray-50 lg:flex">
        <!-- Barre du haut (mobile) -->
        <div class="sticky top-0 z-30 flex items-center justify-between bg-brand-900 px-4 py-3 text-white lg:hidden">
            <button type="button" class="rounded-md p-1" aria-label="Ouvrir le menu" @click="ouvert = true">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <Link :href="route('dashboard')" class="font-bold">ImmoTchad</Link>
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-500 text-sm font-bold text-brand-950">
                {{ initiales }}
            </span>
        </div>

        <!-- Voile (mobile) -->
        <div v-if="ouvert" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="ouvert = false"></div>

        <!-- Menu latéral -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-y-auto bg-brand-900 text-brand-100 transition-transform lg:sticky lg:top-0 lg:h-screen lg:shrink-0"
            :class="ouvert ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex items-center justify-between px-5 py-5">
                <Link :href="route('dashboard')" class="flex items-center gap-2 text-lg font-bold text-white">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11l9-8 9 8" />
                        <path d="M5 10v10h14V10" />
                        <path d="M10 20v-6h4v6" />
                    </svg>
                    ImmoTchad
                </Link>
                <button type="button" class="rounded-md p-1 lg:hidden" aria-label="Fermer le menu" @click="ouvert = false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-5 px-3 pb-4">
                <div v-for="s in sections" :key="s.titre">
                    <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-brand-300">{{ s.titre }}</p>
                    <ul class="space-y-1">
                        <li v-for="lien in s.liens" :key="lien.route">
                            <Link
                                :href="route(lien.route)"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition"
                                :class="estActif(lien) ? 'bg-accent-500 font-semibold text-brand-950' : 'hover:bg-white/10'"
                            >
                                <Icone :nom="lien.icone === 'maison' ? 'accueil' : lien.icone" />
                                {{ lien.nom }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="space-y-2 border-t border-white/10 p-4">
                <BoutonTheme variante="menu" />
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-500 text-sm font-bold text-brand-950">
                        {{ initiales }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">{{ $page.props.auth.user.name }}</p>
                        <p class="truncate text-xs text-brand-300">{{ $page.props.auth.user.email }}</p>
                    </div>
                </div>
                <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/10">
                    <Icone nom="utilisateur" /> Mon compte
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-white/10">
                    <Icone nom="deconnexion" /> Déconnexion
                </Link>
            </div>
        </aside>

        <!-- Contenu -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header v-if="$slots.header" class="border-b border-gray-200 bg-white">
                <div class="px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
