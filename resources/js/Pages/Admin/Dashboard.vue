<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ stats: Object });
</script>

<template>
    <Head title="Administration" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Administration</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow">
                    <p class="text-sm text-gray-500">Utilisateurs inscrits</p>
                    <p class="text-3xl font-bold">{{ stats.utilisateurs }}</p>
                </div>

                <Link :href="route('admin.verifications.index')" class="rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                    <p class="text-sm text-gray-500">Bailleurs en attente de vérification</p>
                    <p class="text-3xl font-bold">{{ stats.bailleurs_en_attente }}</p>
                    <p class="mt-1 text-sm text-indigo-600">Vérifier les dossiers →</p>
                </Link>

                <Link :href="route('admin.localisation.index')" class="rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                    <p class="font-semibold">Villes et quartiers</p>
                    <p class="mt-1 text-sm text-indigo-600">Gérer la localisation →</p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
