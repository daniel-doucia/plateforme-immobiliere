<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ profil: Object });

const form = useForm({
    type: props.profil.type,
    nom_commercial: props.profil.nom_commercial ?? '',
    piece_identite: null,
});

const statuts = {
    en_attente: { texte: 'En attente de vérification', classe: 'bg-yellow-100 text-yellow-800' },
    verifie: { texte: 'Compte vérifié', classe: 'bg-green-100 text-green-800' },
    rejete: { texte: 'Dossier rejeté', classe: 'bg-red-100 text-red-800' },
};

const submit = () => {
    form.post(route('bailleur.profil.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset('piece_identite'),
    });
};
</script>

<template>
    <Head title="Mon profil bailleur" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mon profil bailleur</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow">
                    <span class="rounded-full px-3 py-1 text-sm font-medium" :class="statuts[profil.statut].classe">
                        {{ statuts[profil.statut].texte }}
                    </span>
                    <p v-if="profil.statut === 'rejete' && profil.motif_rejet" class="mt-3 text-sm text-red-700">
                        Motif : {{ profil.motif_rejet }}
                    </p>
                    <p v-if="profil.statut !== 'verifie'" class="mt-3 text-sm text-gray-600">
                        Votre compte doit être vérifié avant de pouvoir publier des annonces.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4 rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel for="type" value="Type de bailleur" />
                        <select
                            id="type"
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="particulier">Particulier</option>
                            <option value="agence">Agence</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                    <div v-if="form.type === 'agence'">
                        <InputLabel for="nom_commercial" value="Nom de l'agence" />
                        <TextInput id="nom_commercial" type="text" class="mt-1 block w-full" v-model="form.nom_commercial" />
                        <InputError class="mt-2" :message="form.errors.nom_commercial" />
                    </div>

                    <div>
                        <InputLabel for="piece" value="Pièce d'identité (JPG, PNG ou PDF, 4 Mo max)" />
                        <input
                            id="piece"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="mt-1 block w-full text-sm"
                            @input="form.piece_identite = $event.target.files[0]"
                        />
                        <p v-if="profil.a_piece" class="mt-1 text-xs text-gray-500">
                            Une pièce est déjà enregistrée. En envoyer une nouvelle relance la vérification.
                        </p>
                        <InputError class="mt-2" :message="form.errors.piece_identite" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                        <span v-if="form.recentlySuccessful" class="text-sm text-gray-600">Enregistré.</span>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
