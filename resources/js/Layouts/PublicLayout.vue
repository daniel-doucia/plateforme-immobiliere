<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-100">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link :href="route('annonces.index')" class="flex items-center gap-2 font-semibold text-gray-800">
                    <ApplicationLogo class="block h-8 w-auto fill-current text-gray-800" />
                    ImmoTchad
                </Link>

                <nav class="flex items-center gap-4 text-sm">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-gray-700 hover:text-gray-900">
                        Mon espace
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-gray-700 hover:text-gray-900">Connexion</Link>
                        <Link :href="route('register')" class="rounded-md bg-indigo-600 px-3 py-1.5 font-semibold text-white">
                            Inscription
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-500">
            ImmoTchad · Annonces immobilières au Tchad
        </footer>
    </div>
</template>
