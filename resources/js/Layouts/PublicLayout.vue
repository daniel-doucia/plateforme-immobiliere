<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2 px-4 py-3">
                <Link :href="route('annonces.index')" class="flex items-center gap-2 text-lg font-bold text-brand-800">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11l9-8 9 8" />
                        <path d="M5 10v10h14V10" />
                        <path d="M10 20v-6h4v6" />
                    </svg>
                    ImmoTchad
                </Link>

                <nav class="order-3 flex w-full items-center gap-5 text-sm font-medium text-gray-700 sm:order-none sm:w-auto">
                    <Link :href="route('annonces.index', { type_offre: 'location' })" class="hover:text-brand-700">Louer</Link>
                    <Link :href="route('annonces.index', { type_offre: 'vente' })" class="hover:text-brand-700">Acheter</Link>
                    <Link v-if="$page.props.auth.user" :href="route('favoris.index')" class="hover:text-brand-700">Favoris</Link>
                </nav>

                <div class="flex items-center gap-3 text-sm">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-full bg-brand-600 px-4 py-1.5 font-semibold text-white hover:bg-brand-700">
                        Mon espace
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="font-medium text-gray-700 hover:text-brand-700">Connexion</Link>
                        <Link :href="route('register')" class="rounded-full bg-brand-600 px-4 py-1.5 font-semibold text-white hover:bg-brand-700">
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
                    <p class="text-lg font-bold text-white">ImmoTchad</p>
                    <p class="text-brand-200">Chambres, appartements, maisons et terrains à louer ou à vendre au Tchad.</p>
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
                    <p class="text-brand-200">Publiez votre bien et touchez des locataires ou acheteurs vérifiés.</p>
                    <Link
                        :href="$page.props.auth.user ? route('bailleur.annonces.index') : route('register')"
                        class="inline-block rounded-full bg-accent-500 px-4 py-1.5 font-semibold text-white hover:bg-accent-600"
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
