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
            <p class="text-sm text-gray-500">Vue d'ensemble de la plateforme.</p>
        </template>

        <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <section class="space-y-3">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                    <div class="rounded-2xl bg-brand-800 p-4 text-white shadow-sm">
                        <p class="text-3xl font-extrabold">{{ annonces.total }}</p>
                        <p class="text-sm opacity-80">Annonces</p>
                    </div>
                    <div class="rounded-2xl bg-accent-400 p-4 text-brand-950 shadow-sm">
                        <p class="text-3xl font-extrabold">{{ annonces.annonces_payees }}</p>
                        <p class="text-sm opacity-80">Payées</p>
                    </div>
                    <Link :href="route('admin.moderation.index')" class="rounded-2xl bg-sky-100 p-4 text-sky-900 shadow-sm hover:shadow-md">
                        <p class="text-3xl font-extrabold">{{ annonces.en_moderation }}</p>
                        <p class="text-sm opacity-80">En modération</p>
                    </Link>
                    <div class="rounded-2xl bg-brand-100 p-4 text-brand-900 shadow-sm">
                        <p class="text-3xl font-extrabold">{{ annonces.publiee }}</p>
                        <p class="text-sm opacity-80">Publiées</p>
                    </div>
                    <div class="rounded-2xl bg-rose-100 p-4 text-rose-900 shadow-sm">
                        <p class="text-3xl font-extrabold">{{ annonces.rejetee }}</p>
                        <p class="text-sm opacity-80">Rejetées</p>
                    </div>
                    <div class="rounded-2xl bg-gray-200 p-4 text-gray-800 shadow-sm">
                        <p class="text-3xl font-extrabold">{{ annonces.rejets_historique }}</p>
                        <p class="text-sm opacity-80">Rejets cumulés</p>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <p class="text-sm text-gray-500">Montant encaissé</p>
                    <p class="text-3xl font-extrabold text-brand-800">{{ prix(annonces.montant_paye) }}</p>
                </div>
            </section>

            <section class="space-y-3">
                <h3 class="text-lg font-bold text-gray-900">À faire et à gérer</h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link :href="route('admin.verifications.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="text-sm text-gray-500">Bailleurs en attente de vérification</p>
                        <p class="text-3xl font-extrabold text-gray-900">{{ stats.bailleurs_en_attente }}</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Vérifier les dossiers →</p>
                    </Link>

                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <p class="text-sm text-gray-500">Utilisateurs inscrits</p>
                        <p class="text-3xl font-extrabold text-gray-900">{{ stats.utilisateurs }}</p>
                    </div>

                    <Link :href="route('admin.bailleurs.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Bailleurs et leurs annonces</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Voir les compteurs par bailleur →</p>
                    </Link>

                    <Link :href="route('admin.forfaits.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Tarifs des forfaits</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Modifier les prix et les durées →</p>
                    </Link>

                    <Link :href="route('admin.localisation.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Villes et quartiers</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Gérer la localisation →</p>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
