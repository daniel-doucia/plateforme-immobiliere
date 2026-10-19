<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ annonces: Object, peut_creer: Boolean });

const couleurs = {
    brouillon: 'bg-gray-100 text-gray-700',
    en_attente_paiement: 'bg-yellow-100 text-yellow-800',
    en_moderation: 'bg-blue-100 text-blue-800',
    publiee: 'bg-green-100 text-green-800',
    rejetee: 'bg-red-100 text-red-800',
    expiree: 'bg-orange-100 text-orange-800',
    cloturee: 'bg-gray-200 text-gray-600',
};

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const date = (iso) => new Date(iso).toLocaleDateString('fr-FR');

const action = (nom, id, message = null) => {
    if (!message || confirm(message)) {
        router.post(route(nom, id), {}, { preserveScroll: true });
    }
};

const supprimer = (id) => {
    if (confirm('Supprimer ce brouillon ?')) {
        router.delete(route('bailleur.annonces.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Mes annonces" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes annonces</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="peut_creer">
                    <Link
                        :href="route('bailleur.annonces.create')"
                        class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
                    >
                        + Nouvelle annonce
                    </Link>
                </div>
                <div v-else class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800">
                    Votre compte doit être vérifié avant de pouvoir créer une annonce.
                    <Link :href="route('bailleur.profil.edit')" class="font-semibold underline">Voir mon profil</Link>
                </div>

                <p v-if="annonces.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Vous n'avez pas encore d'annonce.
                </p>

                <div v-for="a in annonces.data" :key="a.id" class="space-y-3 rounded-lg bg-white p-5 shadow">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ a.titre }}</p>
                            <p class="text-sm text-gray-600">{{ a.type_bien }} · {{ a.type_offre }} · {{ a.lieu }}</p>
                            <p class="text-sm font-medium">
                                {{ prix(a.prix) }}
                                <span v-if="a.periodicite" class="font-normal text-gray-500">{{ a.periodicite }}</span>
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-medium" :class="couleurs[a.statut]">
                            {{ a.statut_libelle }}
                        </span>
                    </div>

                    <p v-if="a.statut === 'publiee' && a.expire_le" class="text-sm text-gray-600">
                        En ligne jusqu'au {{ date(a.expire_le) }}.
                    </p>
                    <p v-if="a.statut === 'expiree'" class="text-sm text-orange-700">
                        Cette annonce a expiré et n'est plus visible. Renouvelez-la pour la remettre en ligne.
                    </p>
                    <p v-if="a.statut === 'rejetee' && a.motif_rejet" class="text-sm text-red-700">
                        Motif du rejet : {{ a.motif_rejet }}. Correction gratuite : votre paiement est conservé, corrigez puis soumettez à nouveau.
                    </p>
                    <p v-if="a.statut === 'en_attente_paiement'" class="text-sm text-yellow-800">
                        <Link :href="route('bailleur.commandes.create', a.id)" class="font-semibold underline">Choisir un forfait et payer</Link>
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <p v-if="a.statut === 'brouillon' && !a.peut.soumettre" class="w-full text-xs text-orange-700">
                            Pour soumettre : compte vérifié et au moins une photo (bouton « Photos »).
                        </p>
                        <Link
                            v-if="a.peut.modifier"
                            :href="route('bailleur.annonces.edit', a.id)"
                            class="rounded-md border border-gray-300 px-3 py-1 text-sm"
                        >
                            Modifier
                        </Link>
                        <Link
                            :href="route('bailleur.annonces.photos.index', a.id)"
                            class="rounded-md border border-gray-300 px-3 py-1 text-sm"
                        >
                            Photos
                        </Link>
                        <button
                            v-if="a.peut.soumettre"
                            class="rounded-md bg-green-600 px-3 py-1 text-sm text-white"
                            @click="action('bailleur.annonces.soumettre', a.id, 'Soumettre cette annonce pour publication ?')"
                        >
                            Soumettre
                        </button>
                        <button
                            v-if="a.peut.renouveler"
                            class="rounded-md bg-indigo-600 px-3 py-1 text-sm text-white"
                            @click="action('bailleur.annonces.renouveler', a.id)"
                        >
                            Renouveler
                        </button>
                        <button
                            v-if="a.peut.rouvrir"
                            class="rounded-md border border-gray-300 px-3 py-1 text-sm"
                            @click="action('bailleur.annonces.rouvrir', a.id)"
                        >
                            Corriger
                        </button>
                        <button
                            v-if="a.peut.cloturer"
                            class="rounded-md border border-gray-300 px-3 py-1 text-sm"
                            @click="action('bailleur.annonces.cloturer', a.id, 'Clôturer définitivement cette annonce (louée ou vendue) ?')"
                        >
                            Clôturer
                        </button>
                        <button v-if="a.peut.supprimer" class="px-2 py-1 text-sm text-red-600" @click="supprimer(a.id)">
                            Supprimer
                        </button>
                    </div>
                </div>

                <div class="flex justify-between text-sm">
                    <Link v-if="annonces.prev_page_url" :href="annonces.prev_page_url" class="text-indigo-600">← Précédent</Link>
                    <span v-else />
                    <Link v-if="annonces.next_page_url" :href="annonces.next_page_url" class="text-indigo-600">Suivant →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
