<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ annonce: Object, forfaits: Array });

const parJour = (f) => f.prix / f.duree_jours;

// Le forfait le moins cher par jour, seulement s'il l'est strictement
const meilleur = computed(() => {
    if (props.forfaits.length < 2) return null;

    const tri = [...props.forfaits].sort((a, b) => parJour(a) - parJour(b));

    return parJour(tri[0]) < parJour(tri[1]) ? tri[0].id : null;
});

const form = useForm({ forfait_id: meilleur.value ?? props.forfaits[0]?.id ?? '' });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n);

const avantages = (f) => [
    `Visible pendant ${f.duree_jours} jours`,
    'Relue avant publication',
    'Contact direct par appel et WhatsApp',
    "Renouvelable à l'expiration",
];

const submit = () => form.post(route('bailleur.commandes.store', props.annonce.id));
</script>

<template>
    <Head title="Choisir un forfait" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Choisir un forfait</h2>
            <p class="text-sm text-gray-500">{{ annonce.titre }}</p>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl space-y-5">
                <p v-if="forfaits.length === 0" class="rounded-xl bg-yellow-50 p-4 text-sm text-yellow-800">
                    Aucun forfait n'est disponible pour ce type de bien pour le moment.
                </p>

                <form v-else class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="f in forfaits"
                            :key="f.id"
                            class="relative flex cursor-pointer flex-col gap-4 rounded-2xl p-5 shadow-sm transition focus-within:ring-2 focus-within:ring-accent-500"
                            :class="form.forfait_id === f.id
                                ? 'bg-brand-800 text-white'
                                : 'bg-white text-gray-900 ring-1 ring-black/5 hover:shadow-md'"
                        >
                            <input v-model="form.forfait_id" type="radio" :value="f.id" class="sr-only" />

                            <span class="flex items-start justify-between gap-2">
                                <span class="font-semibold leading-snug">{{ f.nom }}</span>
                                <span
                                    v-if="meilleur === f.id"
                                    class="shrink-0 rounded-full bg-accent-500 px-2.5 py-0.5 text-xs font-bold text-white"
                                >
                                    Meilleur prix par jour
                                </span>
                            </span>

                            <span>
                                <span class="flex items-end gap-1.5">
                                    <span class="text-4xl font-extrabold leading-none">{{ prix(f.prix) }}</span>
                                    <span class="pb-0.5 text-sm font-medium opacity-80">FCFA</span>
                                </span>
                                <span class="mt-1 block text-sm" :class="form.forfait_id === f.id ? 'text-brand-200' : 'text-gray-500'">
                                    pour {{ f.duree_jours }} jours · environ {{ prix(Math.round(parJour(f))) }} FCFA par jour
                                </span>
                            </span>

                            <span
                                class="rounded-xl p-3 text-sm"
                                :class="form.forfait_id === f.id ? 'bg-white/10' : 'bg-gray-50'"
                            >
                                <span class="mb-2 block text-xs font-semibold uppercase tracking-wide" :class="form.forfait_id === f.id ? 'text-brand-200' : 'text-gray-500'">
                                    Ce forfait inclut
                                </span>
                                <span v-for="a in avantages(f)" :key="a" class="mb-1.5 flex gap-2 last:mb-0">
                                    <span class="font-bold" :class="form.forfait_id === f.id ? 'text-accent-400' : 'text-brand-600'">✓</span>
                                    {{ a }}
                                </span>
                            </span>

                            <span
                                class="rounded-full px-4 py-2 text-center text-sm font-bold"
                                :class="form.forfait_id === f.id ? 'bg-accent-500 text-white' : 'bg-brand-100 text-brand-900'"
                            >
                                {{ form.forfait_id === f.id ? 'Forfait choisi ✓' : 'Choisir ce forfait' }}
                            </span>
                        </label>
                    </div>
                    <InputError :message="form.errors.forfait_id" />

                    <p class="text-sm text-gray-600">
                        Après le paiement, votre annonce est relue par notre équipe, puis publiée pour la durée choisie.
                    </p>

                    <div class="flex flex-wrap items-center gap-4">
                        <button
                            class="rounded-full bg-accent-500 px-8 py-3 text-sm font-bold text-white hover:bg-accent-600 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Continuer vers le paiement →
                        </button>
                        <Link :href="route('bailleur.annonces.index')" class="text-sm text-gray-500 underline">Retour à mes annonces</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
