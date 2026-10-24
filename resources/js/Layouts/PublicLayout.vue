<script setup>
import Icone from '@/Components/Icone.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const url = computed(() => page.url ?? '/');
const utilisateur = computed(() => page.props.auth.user);
const roles = computed(() => Object.values(page.props.auth.roles ?? []));

// Un client ou un administrateur connecté n'a pas de bouton « Publier »
const peutPublier = computed(() => !utilisateur.value || roles.value.includes('bailleur'));
const lienPublier = computed(() => (utilisateur.value ? route('bailleur.annonces.index') : route('register')));

const liens = computed(() => [
    { nom: 'Louer', href: route('annonces.index', { type_offre: 'location' }), actif: url.value.includes('type_offre=location') },
    { nom: 'Acheter', href: route('annonces.index', { type_offre: 'vente' }), actif: url.value.includes('type_offre=vente') },
    { nom: 'Favoris', href: route('favoris.index'), actif: url.value.startsWith('/favoris'), coeur: true },
]);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3">
                <Link :href="route('accueil')" class="flex items-center gap-2">
                    <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="M3 15L16 4l13 11" class="text-brand-800" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M6 14v14h20V14" class="text-brand-800" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        <rect x="11.5" y="15.5" width="4" height="4" class="fill-accent-500" />
                        <rect x="16.5" y="15.5" width="4" height="4" class="fill-accent-500" />
                        <rect x="11.5" y="20.5" width="4" height="4" class="fill-accent-500" />
                        <rect x="16.5" y="20.5" width="4" height="4" class="fill-accent-500" />
                    </svg>
                    <span class="text-2xl font-extrabold tracking-tight text-brand-800">Immo<span class="text-accent-500">Tchad</span></span>
                </Link>

                <nav class="order-3 flex w-full items-center justify-center gap-6 sm:order-none sm:w-auto" aria-label="Navigation principale">
                    <Link
                        v-for="lien in liens"
                        :key="lien.nom"
                        :href="lien.href"
                        class="flex items-center gap-1.5 border-b-2 py-2 text-sm transition"
                        :class="lien.actif
                            ? 'border-accent-500 font-bold text-brand-900'
                            : 'border-transparent font-medium text-gray-600 hover:text-brand-800'"
                    >
                        {{ lien.nom }}
                        <Icone v-if="lien.coeur" nom="coeur" taille="h-4 w-4" />
                    </Link>
                </nav>

                <div class="flex items-center gap-2 text-sm">
                    <Link
                        v-if="peutPublier"
                        :href="lienPublier"
                        class="flex items-center gap-1.5 rounded-xl bg-accent-500 px-4 py-2 font-bold text-white hover:bg-accent-600"
                    >
                        <Icone nom="plus" taille="h-4 w-4" />
                        <span class="hidden sm:inline">Publier une annonce</span>
                        <span class="sm:hidden">Publier</span>
                    </Link>

                    <details v-if="utilisateur" class="relative">
                        <summary
                            class="flex cursor-pointer list-none items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 font-semibold text-white hover:bg-brand-900 [&::-webkit-details-marker]:hidden"
                        >
                            <Icone nom="utilisateur" taille="h-4 w-4" />
                            Mon espace
                            <Icone nom="chevron" taille="h-4 w-4" />
                        </summary>
                        <div class="absolute right-0 z-40 mt-2 w-52 overflow-hidden rounded-xl bg-white py-1 text-sm shadow-xl ring-1 ring-black/5">
                            <Link :href="route('dashboard')" class="block px-4 py-2 text-gray-700 hover:bg-brand-50">Tableau de bord</Link>
                            <Link :href="route('favoris.index')" class="block px-4 py-2 text-gray-700 hover:bg-brand-50">Mes favoris</Link>
                            <Link :href="route('alertes.index')" class="block px-4 py-2 text-gray-700 hover:bg-brand-50">Mes alertes</Link>
                            <Link :href="route('profile.edit')" class="block px-4 py-2 text-gray-700 hover:bg-brand-50">Mon compte</Link>
                            <Link :href="route('logout')" method="post" as="button" class="block w-full px-4 py-2 text-left text-gray-700 hover:bg-brand-50">
                                Déconnexion
                            </Link>
                        </div>
                    </details>

                    <template v-else>
                        <Link :href="route('login')" class="px-2 font-semibold text-gray-700 hover:text-brand-800">Connexion</Link>
                        <Link :href="route('register')" class="rounded-xl bg-brand-800 px-4 py-2 font-semibold text-white hover:bg-brand-900">
                            Inscription
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="bg-brand-950 text-brand-100">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm sm:grid-cols-3">
                <div class="space-y-2">
                    <p class="text-lg font-bold text-white">Immo<span class="text-accent-400">Tchad</span></p>
                    <p class="text-brand-200">Chambres, appartements, maisons, terrains et hôtels à louer ou à vendre au Tchad.</p>
                </div>
                <div class="space-y-2">
                    <p class="font-semibold text-white">Explorer</p>
                    <ul class="space-y-1">
                        <li><Link :href="route('annonces.index', { type_offre: 'location' })" class="hover:text-white">Biens à louer</Link></li>
                        <li><Link :href="route('annonces.index', { type_offre: 'vente' })" class="hover:text-white">Biens à vendre</Link></li>
                    </ul>
                </div>
                <div class="space-y-2">
                    <p class="font-semibold text-white">Vous êtes bailleur ?</p>
                    <p class="text-brand-200">Publiez votre bien et touchez des locataires ou acheteurs.</p>
                    <Link
                        :href="utilisateur ? route('bailleur.annonces.index') : route('register')"
                        class="inline-block rounded-xl bg-accent-500 px-4 py-1.5 font-semibold text-white hover:bg-accent-600"
                    >
                        Publier une annonce
                    </Link>
                </div>
            </div>
            <p class="border-t border-brand-800 py-3 text-center text-xs text-brand-300">
                © ImmoTchad · Annonces immobilières au Tchad
            </p>
        </footer>
    </div>
</template>
