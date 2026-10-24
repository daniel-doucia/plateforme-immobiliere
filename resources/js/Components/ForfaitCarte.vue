<script setup>
import InputError from '@/Components/InputError.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    forfait: { type: Object, required: true },
    types: { type: Array, required: true },
    vedette: { type: Boolean, default: false },
    maxPhotos: { type: Number, default: 8 },
});

const edition = ref(false);

const form = useForm({
    nom: props.forfait.nom,
    type_bien: props.forfait.type_bien ?? '',
    duree_jours: props.forfait.duree_jours,
    prix: props.forfait.prix,
    actif: props.forfait.actif,
    ordre: props.forfait.ordre,
});

const enregistrer = () =>
    form.patch(route('admin.forfaits.update', props.forfait.id), {
        preserveScroll: true,
        onSuccess: () => (edition.value = false),
    });

const annuler = () => {
    form.reset();
    form.clearErrors();
    edition.value = false;
};

const supprimer = () => {
    if (confirm(`Supprimer le forfait « ${props.forfait.nom} » ?`)) {
        router.delete(route('admin.forfaits.destroy', props.forfait.id), {
            preserveScroll: true,
            onError: (erreurs) => alert(erreurs.forfait ?? 'Suppression impossible.'),
        });
    }
};

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n);

const avantages = computed(() => [
    `Visible pendant ${props.forfait.duree_jours} jours`,
    'Relue avant publication',
    `Jusqu'à ${props.maxPhotos} photos`,
    'Contact direct par appel et WhatsApp',
    "Renouvelable à l'expiration",
]);

const champ = 'mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600';
</script>

<template>
    <article
        class="flex flex-col gap-4 rounded-2xl p-5 shadow-sm"
        :class="[
            vedette ? 'bg-brand-800 text-white' : 'bg-white text-gray-900 ring-1 ring-black/5',
            forfait.actif ? '' : 'opacity-70',
        ]"
    >
        <header class="flex items-start justify-between gap-2">
            <h4 class="font-semibold leading-snug">{{ forfait.nom }}</h4>
            <span v-if="!forfait.actif" class="shrink-0 rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                Désactivé
            </span>
            <span v-else-if="vedette" class="shrink-0 rounded-full bg-accent-500 px-2.5 py-0.5 text-xs font-bold text-white">
                ★ Le plus choisi
            </span>
        </header>

        <div>
            <p class="flex items-end gap-1.5">
                <span class="text-4xl font-extrabold leading-none">{{ prix(forfait.prix) }}</span>
                <span class="pb-0.5 text-sm font-medium opacity-80">FCFA</span>
            </p>
            <p class="mt-1 text-sm" :class="vedette ? 'text-brand-200' : 'text-gray-500'">pour {{ forfait.duree_jours }} jours</p>
        </div>

        <button
            type="button"
            class="w-full rounded-full px-4 py-2.5 text-sm font-bold transition"
            :class="vedette ? 'bg-accent-500 text-white hover:bg-accent-600' : 'bg-brand-100 text-brand-900 hover:bg-brand-200'"
            @click="edition = !edition"
        >
            {{ edition ? 'Fermer' : 'Modifier ce forfait' }}
        </button>

        <form v-if="edition" class="space-y-3 rounded-xl bg-white p-4 text-gray-900" @submit.prevent="enregistrer">
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

            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-brand-600" />
                Forfait actif (proposé aux bailleurs)
            </label>
            <InputError :message="form.errors.actif" />

            <div class="flex flex-wrap items-center gap-3">
                <button class="rounded-full bg-brand-700 px-5 py-2 text-sm font-bold text-white hover:bg-brand-800 disabled:opacity-50" :disabled="form.processing">
                    Enregistrer
                </button>
                <button type="button" class="text-sm text-gray-500 underline" @click="annuler">Annuler</button>
                <button type="button" class="ms-auto text-sm text-red-600" @click="supprimer">Supprimer</button>
            </div>
            <p v-if="forfait.commandes_count" class="text-xs text-gray-500">
                Déjà commandé : pour le retirer, décochez « actif » (la suppression est refusée).
            </p>
        </form>

        <div class="rounded-xl p-3" :class="vedette ? 'bg-white/10' : 'bg-gray-50'">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide" :class="vedette ? 'text-brand-200' : 'text-gray-500'">
                Ce forfait inclut
            </p>
            <ul class="space-y-1.5 text-sm">
                <li v-for="a in avantages" :key="a" class="flex gap-2">
                    <span class="font-bold" :class="vedette ? 'text-accent-400' : 'text-brand-600'">✓</span>
                    {{ a }}
                </li>
            </ul>
        </div>

        <p class="mt-auto text-xs" :class="vedette ? 'text-brand-200' : 'text-gray-500'">
            {{ forfait.commandes_count ? `Commandé ${forfait.commandes_count} fois` : 'Jamais commandé' }}
        </p>
    </article>
</template>
