<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    annonce: Object,
    villes: Array,
    types_bien: Array,
    types_offre: Array,
    periodicites: Array,
});

const a = props.annonce;
const enEdition = !!a;

const form = useForm({
    quartier_id: a?.quartier_id ?? '',
    titre: a?.titre ?? '',
    description: a?.description ?? '',
    type_bien: a?.type_bien ?? 'chambre',
    type_offre: a?.type_offre ?? 'location',
    prix: a?.prix ?? '',
    periodicite: a?.periodicite ?? 'mensuel',
    caution: a?.caution ?? '',
    superficie: a?.superficie ?? '',
    nb_pieces: a?.nb_pieces ?? '',
    nb_chambres: a?.nb_chambres ?? '',
    nb_salles_bain: a?.nb_salles_bain ?? '',
    adresse_indicative: a?.adresse_indicative ?? '',
});

const submit = () => {
    if (enEdition) {
        form.put(route('bailleur.annonces.update', a.id));
    } else {
        form.post(route('bailleur.annonces.store'));
    }
};

const champ = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head :title="enEdition ? 'Modifier l\'annonce' : 'Nouvelle annonce'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ enEdition ? "Modifier l'annonce" : 'Nouvelle annonce' }}
            </h2>
        </template>

        <div class="py-8">
            <form class="mx-auto max-w-2xl space-y-5 rounded-lg bg-white p-6 shadow sm:px-8" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="type_offre" value="Je veux" />
                        <select id="type_offre" v-model="form.type_offre" :class="champ">
                            <option v-for="o in types_offre" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.type_offre" />
                    </div>
                    <div>
                        <InputLabel for="type_bien" value="Type de bien" />
                        <select id="type_bien" v-model="form.type_bien" :class="champ">
                            <option v-for="t in types_bien" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.type_bien" />
                    </div>
                </div>

                <div>
                    <InputLabel for="titre" value="Titre de l'annonce" />
                    <TextInput id="titre" type="text" class="mt-1 block w-full" v-model="form.titre" required />
                    <InputError class="mt-1" :message="form.errors.titre" />
                </div>

                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea id="description" v-model="form.description" rows="5" :class="champ" required />
                    <InputError class="mt-1" :message="form.errors.description" />
                </div>

                <div>
                    <InputLabel for="quartier_id" value="Quartier" />
                    <select id="quartier_id" v-model="form.quartier_id" :class="champ" required>
                        <option value="" disabled>Choisir un quartier</option>
                        <optgroup v-for="v in villes" :key="v.id" :label="v.nom">
                            <option v-for="q in v.quartiers" :key="q.id" :value="q.id">{{ q.nom }}</option>
                        </optgroup>
                    </select>
                    <InputError class="mt-1" :message="form.errors.quartier_id" />
                </div>

                <div>
                    <InputLabel for="adresse_indicative" value="Repère ou adresse indicative (facultatif)" />
                    <TextInput id="adresse_indicative" type="text" class="mt-1 block w-full" v-model="form.adresse_indicative" />
                    <InputError class="mt-1" :message="form.errors.adresse_indicative" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="prix" value="Prix (FCFA)" />
                        <TextInput id="prix" type="number" inputmode="numeric" min="1" class="mt-1 block w-full" v-model="form.prix" required />
                        <InputError class="mt-1" :message="form.errors.prix" />
                    </div>
                    <div v-if="form.type_offre === 'location'">
                        <InputLabel for="periodicite" value="Paiement" />
                        <select id="periodicite" v-model="form.periodicite" :class="champ">
                            <option v-for="p in periodicites" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.periodicite" />
                    </div>
                </div>

                <div v-if="form.type_offre === 'location'">
                    <InputLabel for="caution" value="Caution (FCFA, facultatif)" />
                    <TextInput id="caution" type="number" inputmode="numeric" min="0" class="mt-1 block w-full" v-model="form.caution" />
                    <InputError class="mt-1" :message="form.errors.caution" />
                </div>

                <div>
                    <InputLabel for="superficie" value="Superficie (m², facultatif)" />
                    <TextInput id="superficie" type="number" inputmode="numeric" min="1" class="mt-1 block w-full" v-model="form.superficie" />
                    <InputError class="mt-1" :message="form.errors.superficie" />
                </div>

                <div v-if="form.type_bien !== 'terrain'" class="grid grid-cols-3 gap-3">
                    <div>
                        <InputLabel for="nb_pieces" value="Pièces" />
                        <TextInput id="nb_pieces" type="number" inputmode="numeric" min="0" class="mt-1 block w-full" v-model="form.nb_pieces" />
                        <InputError class="mt-1" :message="form.errors.nb_pieces" />
                    </div>
                    <div>
                        <InputLabel for="nb_chambres" value="Chambres" />
                        <TextInput id="nb_chambres" type="number" inputmode="numeric" min="0" class="mt-1 block w-full" v-model="form.nb_chambres" />
                        <InputError class="mt-1" :message="form.errors.nb_chambres" />
                    </div>
                    <div>
                        <InputLabel for="nb_salles_bain" value="Salles de bain" />
                        <TextInput id="nb_salles_bain" type="number" inputmode="numeric" min="0" class="mt-1 block w-full" v-model="form.nb_salles_bain" />
                        <InputError class="mt-1" :message="form.errors.nb_salles_bain" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                    <Link :href="route('bailleur.annonces.index')" class="text-sm text-gray-600 underline">Annuler</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
