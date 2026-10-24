<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ForfaitCarte from '@/Components/ForfaitCarte.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    forfaits: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    couverture: { type: Array, default: () => [] },
    historique: { type: Array, default: () => [] },
    max_photos: { type: Number, default: 8 },
});

const ajout = ref(false);

const form = useForm({
    nom: '',
    type_bien: '',
    duree_jours: 30,
    prix: '',
    actif: true,
    ordre: '',
});

const ajouter = () =>
    form.post(route('admin.forfaits.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            ajout.value = false;
        },
    });

const sansForfait = computed(() => props.couverture.filter((c) => c.actifs === 0));

// Un groupe par type de bien, puis les forfaits valables pour tous les biens
const groupes = computed(() =>
    [
        ...props.types.map((t) => ({
            cle: t.value,
            titre: t.label,
            forfaits: props.forfaits.filter((f) => f.type_bien === t.value),
        })),
        { cle: 'tous', titre: 'Tous les biens', forfaits: props.forfaits.filter((f) => !f.type_bien) },
    ]
        .filter((g) => g.forfaits.length > 0)
        .map((g) => {
            const plusCommande = [...g.forfaits].sort((a, b) => b.commandes_count - a.commandes_count)[0];

            return { ...g, vedette: plusCommande && plusCommande.commandes_count > 0 ? plusCommande.id : null };
        }),
);

const date = (iso) => new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });

const champ = 'mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600';
</script>

<template>
    <Head title="Tarifs des forfaits" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Tarifs des forfaits</h2>
                    <p class="text-sm text-gray-500">Ce que paient les bailleurs pour publier une annonce.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full bg-accent-500 px-5 py-2 text-sm font-bold text-white hover:bg-accent-600"
                    @click="ajout = !ajout"
                >
                    {{ ajout ? 'Fermer' : '+ Nouveau forfait' }}
                </button>
            </div>
        </template>

        <div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <p v-if="$page.props.errors.forfait" class="rounded-xl bg-red-100 p-3 text-sm text-red-800">
                {{ $page.props.errors.forfait }}
            </p>

            <p v-if="sansForfait.length" class="rounded-xl bg-orange-100 p-3 text-sm text-orange-800">
                Attention : aucun forfait actif pour
                <span class="font-semibold">{{ sansForfait.map((c) => c.type).join(', ') }}</span>.
                Les bailleurs de ces biens ne peuvent pas payer leurs annonces.
            </p>

            <p class="rounded-xl bg-blue-50 p-3 text-sm text-blue-800">
                Un nouveau prix s'applique aux prochaines commandes. Les commandes déjà passées gardent le prix qu'elles avaient.
            </p>

            <form
                v-if="ajout"
                class="space-y-3 rounded-2xl border-2 border-dashed border-gray-300 bg-white p-5"
                @submit.prevent="ajouter"
            >
                <p class="font-semibold">Nouveau forfait</p>

                <div>
                    <label class="text-xs text-gray-500">Nom du forfait</label>
                    <input v-model="form.nom" type="text" placeholder="Ex. Maison · 90 jours" :class="champ" required />
                    <InputError class="mt-1" :message="form.errors.nom" />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
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
                        <input v-model="form.ordre" type="number" min="0" inputmode="numeric" placeholder="Auto" :class="champ" />
                        <InputError class="mt-1" :message="form.errors.ordre" />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-brand-600" />
                    Actif dès la création
                </label>

                <button class="rounded-full bg-brand-700 px-5 py-2 text-sm font-bold text-white hover:bg-brand-800 disabled:opacity-50" :disabled="form.processing">
                    Ajouter le forfait
                </button>
            </form>

            <p v-if="groupes.length === 0" class="rounded-2xl bg-white p-8 text-center text-gray-500 shadow-sm ring-1 ring-black/5">
                Aucun forfait pour le moment.
            </p>

            <section v-for="g in groupes" :key="g.cle" class="space-y-3">
                <h3 class="flex items-center gap-3 text-lg font-extrabold text-brand-900">
                    <span class="h-0.5 w-8 bg-accent-500"></span>
                    {{ g.titre }}
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <ForfaitCarte
                        v-for="f in g.forfaits"
                        :key="f.id"
                        :forfait="f"
                        :types="types"
                        :vedette="g.vedette === f.id"
                        :max-photos="max_photos"
                    />
                </div>
            </section>

            <div v-if="historique.length" class="space-y-2 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                <p class="font-bold text-gray-900">Dernières modifications</p>
                <ul class="divide-y divide-gray-100 text-sm">
                    <li v-for="h in historique" :key="h.id" class="py-2">
                        <p class="font-medium text-gray-800">{{ h.forfait }}</p>
                        <p class="text-gray-600">{{ h.resume }}</p>
                        <p class="text-xs text-gray-400">{{ h.utilisateur }} · {{ date(h.date) }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
