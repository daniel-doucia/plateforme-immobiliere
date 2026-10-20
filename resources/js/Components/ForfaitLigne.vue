<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ forfait: Object, types: Array });

const form = useForm({
    nom: props.forfait.nom,
    type_bien: props.forfait.type_bien ?? '',
    duree_jours: props.forfait.duree_jours,
    prix: props.forfait.prix,
    actif: props.forfait.actif,
    ordre: props.forfait.ordre,
});

const enregistrer = () =>
    form.patch(route('admin.forfaits.update', props.forfait.id), { preserveScroll: true });

const supprimer = () => {
    if (confirm(`Supprimer le forfait « ${props.forfait.nom} » ?`)) {
        router.delete(route('admin.forfaits.destroy', props.forfait.id), {
            preserveScroll: true,
            onError: (erreurs) => alert(erreurs.forfait ?? 'Suppression impossible.'),
        });
    }
};

const champ = 'mt-1 block w-full rounded-md border-gray-300 text-sm';
</script>

<template>
    <form class="space-y-3 rounded-lg bg-white p-4 shadow" @submit.prevent="enregistrer">
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm text-gray-500">
                <span v-if="forfait.commandes_count">Commandé {{ forfait.commandes_count }} fois</span>
                <span v-else>Jamais commandé</span>
            </p>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-indigo-600" />
                Actif
            </label>
        </div>

        <div>
            <label class="text-xs text-gray-500">Nom du forfait</label>
            <input v-model="form.nom" type="text" :class="champ" required />
            <InputError class="mt-1" :message="form.errors.nom" />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-gray-500">Type de bien</label>
                <select v-model="form.type_bien" :class="champ">
                    <option value="">Tous les biens</option>
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <InputError class="mt-1" :message="form.errors.type_bien" />
            </div>
            <div>
                <label class="text-xs text-gray-500">Durée (jours)</label>
                <input v-model="form.duree_jours" type="number" min="1" max="365" inputmode="numeric" :class="champ" required />
                <InputError class="mt-1" :message="form.errors.duree_jours" />
            </div>
            <div>
                <label class="text-xs text-gray-500">Prix (FCFA)</label>
                <input v-model="form.prix" type="number" min="100" inputmode="numeric" :class="champ" required />
                <InputError class="mt-1" :message="form.errors.prix" />
            </div>
            <div>
                <label class="text-xs text-gray-500">Ordre d'affichage</label>
                <input v-model="form.ordre" type="number" min="0" inputmode="numeric" :class="champ" />
                <InputError class="mt-1" :message="form.errors.ordre" />
            </div>
        </div>

        <InputError :message="form.errors.actif" />

        <div class="flex flex-wrap items-center gap-3">
            <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
            <span v-if="form.recentlySuccessful" class="text-sm text-gray-600">Enregistré.</span>
            <button type="button" class="ms-auto text-sm text-red-600" @click="supprimer">Supprimer</button>
        </div>
        <p v-if="forfait.commandes_count" class="text-xs text-gray-500">
            Déjà commandé : pour le retirer, décochez « Actif » (la suppression est refusée).
        </p>
    </form>
</template>
