<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ commande: Object });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
</script>

<template>
    <Head title="Ma commande" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Ma commande</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="space-y-3 rounded-lg bg-white p-6 shadow">
                    <p class="text-xs text-gray-500">Référence {{ commande.reference }}</p>
                    <p class="font-semibold">{{ commande.annonce_titre }}</p>
                    <p class="text-sm text-gray-600">{{ commande.libelle }} · {{ commande.duree_jours }} jours</p>
                    <p class="text-2xl font-bold">{{ prix(commande.montant) }}</p>
                    <span class="inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800">
                        {{ commande.statut_libelle }}
                    </span>
                </div>

                <p class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                    Le paiement mobile sera disponible à la prochaine étape du développement.
                </p>

                <Link :href="route('bailleur.annonces.index')" class="text-sm text-indigo-600 underline">
                    ← Retour à mes annonces
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
