<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import NouveauQuartier from '@/Components/NouveauQuartier.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({ villes: Array });

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
        router.delete(route('admin.quartiers.destroy', quartier.id), { preserveScroll: true });
    }
};

const changerArrondissement = (quartier, valeur) => {
    router.patch(
        route('admin.quartiers.update', quartier.id),
        { nom: quartier.nom, arrondissement: valeur === '' ? null : Number(valeur) },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Villes et quartiers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Villes et quartiers</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="$page.props.errors.ville" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">
                    {{ $page.props.errors.ville }}
                </p>

                <form class="flex flex-wrap items-start gap-2 rounded-lg bg-white p-4 shadow" @submit.prevent="ajouterVille">
                    <div class="min-w-[12rem] flex-1">
                        <input
                            v-model="formVille.nom"
                            type="text"
                            placeholder="Nouvelle ville"
                            required
                            class="block w-full rounded-md border-gray-300 text-sm"
                        />
                        <InputError class="mt-1" :message="formVille.errors.nom" />
                    </div>
                    <PrimaryButton :disabled="formVille.processing">Ajouter la ville</PrimaryButton>
                </form>

                <div v-for="ville in villes" :key="ville.id" class="rounded-lg bg-white p-5 shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">
                            {{ ville.nom }}
                            <span class="text-sm font-normal text-gray-500">({{ ville.quartiers.length }} quartiers)</span>
                        </h3>
                        <button class="text-sm text-red-600" @click="supprimerVille(ville)">Supprimer la ville</button>
                    </div>

                    <ul class="mt-3 divide-y divide-gray-100">
                        <li v-for="q in ville.quartiers" :key="q.id" class="flex items-center justify-between gap-2 py-2">
                            <span class="text-sm">{{ q.nom }}</span>
                            <div class="flex items-center gap-3">
                                <select
                                    :value="q.arrondissement ?? ''"
                                    class="rounded-md border-gray-300 py-1 text-sm"
                                    @change="changerArrondissement(q, $event.target.value)"
                                >
                                    <option value="">Arr. —</option>
                                    <option v-for="n in 10" :key="n" :value="n">{{ n }}e arr.</option>
                                </select>
                                <button class="text-sm text-red-600" @click="supprimerQuartier(q)">Supprimer</button>
                            </div>
                        </li>
                    </ul>

                    <NouveauQuartier :ville-id="ville.id" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
