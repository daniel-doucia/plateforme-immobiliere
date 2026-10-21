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
const estConnexion = computed(() => page.component === 'Auth/Login');
const estInscription = computed(() => page.component === 'Auth/Register');

const avantages = [
    "Bailleurs dont l'identité est vérifiée",
    'Annonces relues avant publication',
    'Contact direct par appel ou WhatsApp',
];
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-brand-950 sm:p-6">
        <!-- Fond flouté -->
        <div
            class="absolute inset-0 scale-110 blur-md"
            style="background-image: url('/images/hero-n-djamena.jpg'); background-size: cover; background-position: center;"
        ></div>
        <div class="absolute inset-0 bg-brand-950/70"></div>

        <div class="relative grid min-h-screen w-full max-w-5xl overflow-hidden bg-white shadow-2xl sm:min-h-0 sm:rounded-3xl md:grid-cols-2">
            <!-- Panneau photo -->
            <aside
                class="relative hidden min-h-[600px] bg-brand-900 md:block"
                style="background-image: url('/images/hero-n-djamena.jpg'); background-size: cover; background-position: 70% center;"
            >
                <div class="absolute inset-0 bg-gradient-to-t from-brand-950/95 via-brand-900/40 to-brand-950/30"></div>

                <div class="relative flex h-full flex-col justify-between p-8 text-white">
                    <Link href="/" class="inline-flex w-fit items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 font-bold backdrop-blur">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 11l9-8 9 8" />
                            <path d="M5 10v10h14V10" />
                            <path d="M10 20v-6h4v6" />
                        </svg>
                        ImmoTchad
                    </Link>

                    <div class="space-y-4">
                        <h2 class="text-3xl font-extrabold leading-tight">
                            Trouvez votre <span class="text-accent-400">chez-vous</span>
                        </h2>
                        <ul class="space-y-2 text-sm text-brand-100">
                            <li v-for="a in avantages" :key="a" class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-accent-500 text-xs font-bold text-brand-950">✓</span>
                                {{ a }}
                            </li>
                        </ul>
                        <div class="flex gap-1.5 pt-2">
                            <span class="h-1.5 w-8 rounded-full bg-accent-400"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-white/60"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-white/60"></span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Formulaire -->
            <main class="flex flex-col">
                <div class="flex items-center justify-between px-6 pt-6 sm:px-10">
                    <Link href="/" class="flex items-center gap-2 font-bold text-brand-800">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 11l9-8 9 8" />
                            <path d="M5 10v10h14V10" />
                            <path d="M10 20v-6h4v6" />
                        </svg>
                        ImmoTchad
                    </Link>

                    <Link
                        v-if="estConnexion"
                        :href="route('register')"
                        class="rounded-full bg-brand-800 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-900"
                    >
                        Créer un compte
                    </Link>
                    <Link
                        v-else-if="estInscription"
                        :href="route('login')"
                        class="rounded-full bg-brand-800 px-4 py-1.5 text-sm font-semibold text-white hover:bg-brand-900"
                    >
                        Se connecter
                    </Link>
                </div>

                <div class="flex flex-1 flex-col justify-center px-6 py-8 sm:px-10">
                    <h1 class="text-2xl font-extrabold text-brand-900 sm:text-3xl">{{ titre }}</h1>
                    <p v-if="sousTitre" class="mb-6 mt-1 text-sm text-gray-500">{{ sousTitre }}</p>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
