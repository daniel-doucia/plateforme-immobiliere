<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    reference: String,
    montant: Number,
    libelle: String,
    annonce_titre: String,
});

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const simuler = (resultat) =>
    router.post(route('paiement.faux.simuler', props.reference), { resultat });
</script>

<template>
    <Head title="Paiement (test)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Paiement</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p class="rounded-lg bg-orange-100 p-3 text-sm font-semibold text-orange-800">
                    Page de paiement de TEST : aucun argent réel n'est débité.
                </p>

                <p v-if="$page.props.errors.paiement" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">
                    {{ $page.props.errors.paiement }}
                </p>

                <div class="space-y-2 rounded-lg bg-white p-6 shadow">
                    <p class="text-xs text-gray-500">Référence {{ reference }}</p>
                    <p class="font-semibold">{{ annonce_titre }}</p>
                    <p class="text-sm text-gray-600">{{ libelle }}</p>
                    <p class="text-2xl font-bold">{{ prix(montant) }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white" @click="simuler('reussi')">
                        Simuler un paiement réussi
                    </button>
                    <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white" @click="simuler('echec')">
                        Simuler un échec
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
