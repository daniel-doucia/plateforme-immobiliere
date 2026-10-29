<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import NouveauQuartier from '@/Components/NouveauQuartier.vue';
import Icone from '@/Components/Icone.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ villes: Array });

const formVille = useForm({ nom: '' });

const ajouterVille = () =>
    formVille.post(route('admin.villes.store'), {
        preserveScroll: true,
        onSuccess: () => formVille.reset(),
    });

const supprimerVille = (ville) => {
    if (confirm(`Supprimer la ville « ${ville.nom} » ?`)) {
        router.delete(route('admin.villes.destroy', ville.id), { preserveScroll: true, onError: (e) => alert(e.ville) });
    }
};

const supprimerQuartier = (quartier) => {
    if (confirm(`Supprimer le quartier « ${quartier.nom} » ?`)) {
        router.delete(route('admin.quartiers.destroy', quartier.id), { preserveScroll: true, onError: (e) => alert(e.quartier) });
    }
};

const changerArrondissement = (quartier, valeur) => {
    router.patch(
        route('admin.quartiers.update', quartier.id),
        { nom: quartier.nom, arrondissement: valeur === '' ? null : Number(valeur) },
        { preserveScroll: true },
    );
};

// --- Affichage uniquement : recherche et cartes repliables ---
const recherche = ref('');
const ouvertes = ref({});

const normaliser = (texte) =>
    String(texte ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

const villesAffichees = computed(() => {
    const terme = normaliser(recherche.value);
    if (!terme) return props.villes;
    return props.villes.filter((v) => normaliser(v.nom).includes(terme));
});

const totalQuartiers = computed(() => props.villes.reduce((somme, v) => somme + v.quartiers.length, 0));

const libelleQuartiers = (n) => `${n} quartier${n > 1 ? 's' : ''}`;

const basculer = (id) => {
    ouvertes.value[id] = !ouvertes.value[id];
};

const toutDeplier = (etat) => {
    villesAffichees.value.forEach((v) => (ouvertes.value[v.id] = etat));
};
</script>

<template>
    <Head title="Villes et quartiers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Villes et quartiers</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                <p v-if="$page.props.errors.ville" class="rounded-xl bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200">
                    {{ $page.props.errors.ville }}
                </p>

                <!-- Ajout d'une ville -->
                <form class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5 sm:p-5" @submit.prevent="ajouterVille">
                    <label for="nouvelle-ville" class="text-sm font-semibold text-brand-900">Ajouter une ville</label>
                    <div class="mt-2 flex flex-wrap items-start gap-2">
                        <div class="min-w-[12rem] flex-1">
                            <input
                                id="nouvelle-ville"
                                v-model="formVille.nom"
                                type="text"
                                placeholder="Nouvelle ville"
                                required
                                class="block w-full rounded-xl border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600"
                            />
                            <InputError class="mt-1" :message="formVille.errors.nom" />
                        </div>
                        <button
                            class="flex items-center gap-1.5 rounded-xl bg-brand-800 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900 disabled:opacity-60"
                            :disabled="formVille.processing"
                        >
                            <Icone nom="plus" taille="h-4 w-4" />
                            Ajouter la ville
                        </button>
                    </div>
                </form>

                <!-- Recherche et résumé -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="relative min-w-[12rem] flex-1">
                        <input
                            v-model="recherche"
                            type="search"
                            placeholder="Rechercher une ville…"
                            aria-label="Rechercher une ville"
                            class="block w-full rounded-xl border-gray-300 bg-white text-sm focus:border-brand-600 focus:ring-brand-600"
                        />
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span>{{ villes.length }} villes · {{ totalQuartiers }} quartiers</span>
                        <button type="button" class="font-medium text-brand-800 hover:underline" @click="toutDeplier(true)">Tout déplier</button>
                        <button type="button" class="font-medium text-brand-800 hover:underline" @click="toutDeplier(false)">Tout replier</button>
                    </div>
                </div>

                <p v-if="villesAffichees.length === 0" class="rounded-2xl bg-white p-6 text-center text-gray-500 shadow-sm ring-1 ring-black/5">
                    Aucune ville ne correspond à « {{ recherche }} ».
                </p>

                <!-- Liste des villes -->
                <div
                    v-for="ville in villesAffichees"
                    :key="ville.id"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5"
                >
                    <div class="flex items-center gap-2 p-3 sm:p-4">
                        <button
                            type="button"
                            class="flex min-w-0 flex-1 items-center gap-3 text-left"
                            :aria-expanded="!!ouvertes[ville.id]"
                            @click="basculer(ville.id)"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-800">
                                <Icone nom="epingle" taille="h-4 w-4" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-gray-900">{{ ville.nom }}</span>
                                <span class="text-xs text-gray-500">{{ libelleQuartiers(ville.quartiers.length) }}</span>
                            </span>
                            <span class="ml-auto text-gray-400 transition-transform" :class="ouvertes[ville.id] ? 'rotate-180' : ''">
                                <Icone nom="chevron" taille="h-5 w-5" />
                            </span>
                        </button>
                        <button
                            type="button"
                            class="flex shrink-0 items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:border-red-600 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                            @click="supprimerVille(ville)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 11v6m4-6v6" /></svg>
                            <span class="hidden sm:inline">Supprimer la ville</span>
                            <span class="sm:hidden">Supprimer</span>
                        </button>
                    </div>

                    <div v-if="ouvertes[ville.id]" class="border-t border-gray-100 bg-gray-50/60 px-3 pb-4 pt-2 sm:px-4">
                        <p v-if="ville.quartiers.length === 0" class="py-3 text-sm text-gray-500">Aucun quartier pour le moment.</p>

                        <ul class="divide-y divide-gray-100">
                            <li v-for="q in ville.quartiers" :key="q.id" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span class="text-sm font-medium text-gray-800">{{ q.nom }}</span>
                                <div class="flex items-center gap-2">
                                    <select
                                        :value="q.arrondissement ?? ''"
                                        class="rounded-lg border-gray-300 bg-white py-1 text-sm focus:border-brand-600 focus:ring-brand-600"
                                        :aria-label="'Arrondissement de ' + q.nom"
                                        @change="changerArrondissement(q, $event.target.value)"
                                    >
                                        <option value="">Arr. —</option>
                                        <option v-for="n in 10" :key="n" :value="n">{{ n }}e arr.</option>
                                    </select>
                                    <button
                                        type="button"
                                        class="flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:border-red-600 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                                        @click="supprimerQuartier(q)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 11v6m4-6v6" /></svg>
                                        Supprimer
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <NouveauQuartier :ville-id="ville.id" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
