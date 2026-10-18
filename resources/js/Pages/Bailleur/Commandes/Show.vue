<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ commande: Object });

const form = useForm({});

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const payer = () => form.post(route('paiement.payer', props.commande.id));

const couleurs = {
    en_attente: 'bg-yellow-100 text-yellow-800',
    payee: 'bg-green-100 text-green-800',
    echouee: 'bg-red-100 text-red-800',
    annulee: 'bg-gray-100 text-gray-600',
};
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
                    <p class="text-sm text-gray-600">{{ commande.libelle }}</p>
                    <p class="text-2xl font-bold">{{ prix(commande.montant) }}</p>
                    <span class="inline-block rounded-full px-3 py-1 text-xs font-medium" :class="couleurs[commande.statut]">
                        {{ commande.statut_libelle }}
                    </span>
                </div>

                <div v-if="commande.peut_payer">
                    <PrimaryButton :disabled="form.processing" @click="payer">
                        Payer {{ prix(commande.montant) }}
                    </PrimaryButton>
                </div>

                <p v-if="commande.statut === 'payee'" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    Paiement reçu. Votre annonce est en cours de modération : elle sera publiée après validation.
                </p>
                <p v-if="commande.statut === 'echouee'" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
                    Le paiement a échoué. Vous pouvez recommencer depuis « Mes annonces ».
                </p>

                <Link :href="route('bailleur.annonces.index')" class="text-sm text-indigo-600 underline">
                    ← Retour à mes annonces
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
