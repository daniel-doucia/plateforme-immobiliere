<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const textes = {
    'Auth/Login': ['Bon retour parmi nous', 'Connectez-vous pour gérer vos annonces, vos favoris et vos alertes.'],
    'Auth/Register': ['Créez votre compte', 'Gratuit pour chercher un bien. Les bailleurs publient en quelques minutes.'],
    'Auth/ForgotPassword': ['Mot de passe oublié', 'Nous vous envoyons un lien pour en choisir un nouveau.'],
    'Auth/ResetPassword': ['Nouveau mot de passe', 'Choisissez un mot de passe long et unique.'],
    'Auth/ConfirmPassword': ['Zone sécurisée', 'Confirmez votre mot de passe pour continuer.'],
    'Auth/VerifyEmail': ['Vérifiez votre e-mail', 'Un lien de confirmation vous a été envoyé.'],
};

const titre = computed(() => textes[page.component]?.[0] ?? 'ImmoTchad');
const sousTitre = computed(() => textes[page.component]?.[1] ?? '');

const avantages = [
    'Bailleurs dont l\'identité est vérifiée',
    'Annonces relues avant publication',
    'Contact direct par appel ou WhatsApp',
];
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <!-- Panneau de marque (grands écrans) -->
        <aside
            class="relative hidden overflow-hidden bg-brand-900 lg:block"
            style="background-image: url('/images/hero-n-djamena.jpg'); background-size: cover; background-position: 72% center;"
        >
            <div class="absolute inset-0 bg-gradient-to-b from-brand-950/90 via-brand-900/75 to-brand-950/90"></div>

            <div class="relative flex h-full min-h-screen flex-col justify-between p-10 text-white">
                <Link href="/" class="flex items-center gap-2 text-xl font-bold">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 11l9-8 9 8" />
                        <path d="M5 10v10h14V10" />
                        <path d="M10 20v-6h4v6" />
                    </svg>
                    ImmoTchad
                </Link>

                <div class="space-y-6">
                    <h2 class="text-4xl font-extrabold leading-tight">
                        Trouvez votre <span class="text-accent-400">chez-vous</span>
                    </h2>
                    <ul class="space-y-3 text-brand-100">
                        <li v-for="a in avantages" :key="a" class="flex items-center gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-500 text-sm font-bold text-brand-950">✓</span>
                            {{ a }}
                        </li>
                    </ul>
                </div>

                <p class="text-sm text-brand-200">© ImmoTchad · Annonces immobilières au Tchad</p>
            </div>
        </aside>

        <!-- Formulaire -->
        <main class="flex min-h-screen flex-col bg-gray-50 lg:min-h-0">
            <header class="bg-brand-900 px-4 py-4 text-white lg:hidden">
                <Link href="/" class="text-lg font-bold">ImmoTchad</Link>
            </header>

            <div class="flex flex-1 items-center justify-center px-4 py-8 sm:px-8">
                <div class="w-full max-w-md space-y-6">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900">{{ titre }}</h1>
                        <p v-if="sousTitre" class="mt-1 text-sm text-gray-600">{{ sousTitre }}</p>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-black/5 sm:p-8">
                        <slot />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
