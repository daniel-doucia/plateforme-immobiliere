<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ profils: Object, statut: String });

const onglets = [
    { valeur: 'en_attente', texte: 'En attente' },
    { valeur: 'verifie', texte: 'Vérifiés' },
    { valeur: 'rejete', texte: 'Rejetés' },
];

const rejetEnCours = ref(null);
const formRejet = useForm({ motif_rejet: '' });

const approuver = (id) => {
    if (confirm('Valider ce bailleur ?')) {
        router.post(route('admin.verifications.approuver', id), {}, { preserveScroll: true });
    }
};

const rejeter = (id) => {
    formRejet.post(route('admin.verifications.rejeter', id), {
        preserveScroll: true,
        onSuccess: () => {
            formRejet.reset();
            rejetEnCours.value = null;
        },
    });
};

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '');
</script>

<template>
    <Head title="Vérification des bailleurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Vérification des bailleurs</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex gap-2">
                    <Link
                        v-for="o in onglets"
                        :key="o.valeur"
                        :href="route('admin.verifications.index', { statut: o.valeur })"
                        class="rounded-full px-4 py-1 text-sm"
                        :class="statut === o.valeur ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow'"
                    >
                        {{ o.texte }}
                    </Link>
                </div>

                <p v-if="profils.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucun dossier dans cette catégorie.
                </p>

                <div v-for="p in profils.data" :key="p.id" class="space-y-3 rounded-lg bg-white p-5 shadow">
                    <div>
                        <p class="font-semibold">{{ p.nom }}</p>
                        <p class="text-sm text-gray-600">{{ p.email }} · {{ p.telephone }}</p>
                        <p class="text-sm text-gray-600">
                            {{ p.type === 'agence' ? 'Agence : ' + (p.nom_commercial ?? '—') : 'Particulier' }}
                            · inscrit le {{ date(p.cree_le) }}
                        </p>
                        <p v-if="p.motif_rejet" class="text-sm text-red-700">Motif du rejet : {{ p.motif_rejet }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="p.a_piece"
                            :href="route('admin.verifications.piece', p.id)"
                            target="_blank"
                            rel="noopener"
                            class="rounded-md border border-gray-300 px-3 py-1 text-sm"
                        >
                            Voir la pièce
                        </a>
                        <span v-else class="text-sm text-orange-600">Aucune pièce envoyée</span>

                        <template v-if="statut === 'en_attente'">
                            <button
                                v-if="p.a_piece"
                                class="rounded-md bg-green-600 px-3 py-1 text-sm text-white"
                                @click="approuver(p.id)"
                            >
                                Valider
                            </button>
                            <button
                                class="rounded-md bg-red-600 px-3 py-1 text-sm text-white"
                                @click="rejetEnCours = rejetEnCours === p.id ? null : p.id"
                            >
                                Rejeter
                            </button>
                        </template>
                    </div>

                    <form v-if="rejetEnCours === p.id" class="space-y-2" @submit.prevent="rejeter(p.id)">
                        <textarea
                            v-model="formRejet.motif_rejet"
                            rows="2"
                            placeholder="Motif du rejet (visible par le bailleur)"
                            class="block w-full rounded-md border-gray-300 text-sm"
                        />
                        <InputError :message="formRejet.errors.motif_rejet" />
                        <button class="rounded-md bg-red-600 px-3 py-1 text-sm text-white" :disabled="formRejet.processing">
                            Confirmer le rejet
                        </button>
                    </form>
                </div>

                <div class="flex justify-between text-sm">
                    <Link v-if="profils.prev_page_url" :href="profils.prev_page_url" class="text-indigo-600">← Précédent</Link>
                    <span v-else />
                    <Link v-if="profils.next_page_url" :href="profils.next_page_url" class="text-indigo-600">Suivant →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
