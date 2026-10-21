<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ bailleurs: Object, recherche: String });

const q = ref(props.recherche ?? '');

const rechercher = () =>
    router.get(route('admin.bailleurs.index'), q.value ? { q: q.value } : {}, {
        preserveState: true,
        replace: true,
    });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const verifications = {
    verifie: { texte: 'Vérifié', classe: 'bg-green-100 text-green-800' },
    en_attente: { texte: 'En attente', classe: 'bg-yellow-100 text-yellow-800' },
    rejete: { texte: 'Rejeté', classe: 'bg-red-100 text-red-800' },
};

const verification = (v) => verifications[v] ?? { texte: 'Dossier vide', classe: 'bg-gray-100 text-gray-600' };

const compteurs = (s) => [
    { label: 'Total', valeur: s.total },
    { label: 'Payées', valeur: s.annonces_payees },
    { label: 'En modération', valeur: s.en_moderation },
    { label: 'Publiées', valeur: s.publiee },
    { label: 'Rejetées', valeur: s.rejetee },
];
</script>

<template>
    <Head title="Bailleurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Bailleurs et leurs annonces</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <form class="flex gap-2" @submit.prevent="rechercher">
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Nom, e-mail ou téléphone"
                        class="block w-full rounded-md border-gray-300 text-sm"
                    />
                    <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Chercher</button>
                </form>

                <p v-if="bailleurs.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucun bailleur trouvé.
                </p>

                <div v-for="b in bailleurs.data" :key="b.id" class="space-y-3 rounded-lg bg-white p-5 shadow">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ b.nom }}</p>
                            <p class="text-sm text-gray-600">{{ b.telephone }} · {{ b.email }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-medium" :class="verification(b.verification).classe">
                            {{ verification(b.verification).texte }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <div v-for="c in compteurs(b.stats)" :key="c.label" class="rounded-md bg-gray-50 p-2 text-center">
                            <p class="text-xl font-bold text-gray-900">{{ c.valeur }}</p>
                            <p class="text-xs text-gray-500">{{ c.label }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500">
                        Brouillons {{ b.stats.brouillon }} · En attente de paiement {{ b.stats.en_attente_paiement }} ·
                        Expirées {{ b.stats.expiree }} · Clôturées {{ b.stats.cloturee }} ·
                        Rejets cumulés {{ b.stats.rejets_historique }}
                    </p>
                    <p class="text-sm font-medium text-gray-800">Montant payé : {{ prix(b.stats.montant_paye) }}</p>
                </div>

                <div class="flex justify-between text-sm">
                    <Link v-if="bailleurs.prev_page_url" :href="bailleurs.prev_page_url" class="text-brand-600">← Précédent</Link>
                    <span v-else />
                    <Link v-if="bailleurs.next_page_url" :href="bailleurs.next_page_url" class="text-brand-600">Suivant →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
