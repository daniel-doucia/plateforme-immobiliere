<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ stats: Object, annonces: Object });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
</script>

<template>
    <Head title="Administration" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Administration</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Annonces</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        <div class="rounded-lg bg-white p-4 shadow">
                            <p class="text-2xl font-bold">{{ annonces.total }}</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow">
                            <p class="text-2xl font-bold">{{ annonces.annonces_payees }}</p>
                            <p class="text-xs text-gray-500">Payées</p>
                        </div>
                        <Link :href="route('admin.moderation.index')" class="rounded-lg bg-white p-4 shadow hover:bg-gray-50">
                            <p class="text-2xl font-bold">{{ annonces.en_moderation }}</p>
                            <p class="text-xs text-gray-500">En modération</p>
                        </Link>
                        <div class="rounded-lg bg-white p-4 shadow">
                            <p class="text-2xl font-bold">{{ annonces.publiee }}</p>
                            <p class="text-xs text-gray-500">Publiées</p>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow">
                            <p class="text-2xl font-bold">{{ annonces.rejetee }}</p>
                            <p class="text-xs text-gray-500">Rejetées</p>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow">
                            <p class="text-2xl font-bold">{{ annonces.rejets_historique }}</p>
                            <p class="text-xs text-gray-500">Rejets cumulés</p>
                        </div>
                    </div>
                    <p class="mt-2 text-sm font-medium text-gray-800">Montant encaissé : {{ prix(annonces.montant_paye) }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Utilisateurs inscrits</p>
                        <p class="text-3xl font-bold">{{ stats.utilisateurs }}</p>
                    </div>

                    <Link :href="route('admin.bailleurs.index')" class="rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                        <p class="font-semibold">Bailleurs et leurs annonces</p>
                        <p class="mt-1 text-sm text-indigo-600">Voir les compteurs par bailleur →</p>
                    </Link>

                    <Link :href="route('admin.forfaits.index')" class="rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                        <p class="font-semibold">Tarifs des forfaits</p>
                        <p class="mt-1 text-sm text-indigo-600">Modifier les prix et les durées →</p>
                    </Link>

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
        </div>
    </AuthenticatedLayout>
</template>
