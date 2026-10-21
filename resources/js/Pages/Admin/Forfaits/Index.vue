<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ForfaitLigne from '@/Components/ForfaitLigne.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    forfaits: Array,
    types: Array,
    couverture: Array,
    historique: Array,
});

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
        onSuccess: () => form.reset(),
    });

const sansForfait = computed(() => props.couverture.filter((c) => c.actifs === 0));

const date = (iso) =>
    new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });

const champ = 'mt-1 block w-full rounded-md border-gray-300 text-sm';
</script>

<template>
    <Head title="Tarifs des forfaits" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Tarifs des forfaits</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="$page.props.errors.forfait" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">
                    {{ $page.props.errors.forfait }}
                </p>

                <p v-if="sansForfait.length" class="rounded-lg bg-orange-100 p-3 text-sm text-orange-800">
                    Attention : aucun forfait actif pour
                    <span class="font-semibold">{{ sansForfait.map((c) => c.type).join(', ') }}</span>.
                    Les bailleurs de ces biens ne peuvent pas payer leurs annonces.
                </p>

                <p class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                    Un nouveau prix s'applique aux prochaines commandes. Les commandes déjà passées gardent le prix
                    qu'elles avaient.
                </p>

                <form class="space-y-3 rounded-lg border-2 border-dashed border-gray-300 bg-white p-4" @submit.prevent="ajouter">
                    <p class="font-semibold">Nouveau forfait</p>

                    <div>
                        <label class="text-xs text-gray-500">Nom du forfait</label>
                        <input v-model="form.nom" type="text" placeholder="Ex. Maison · 90 jours" :class="champ" required />
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
                            <input v-model="form.ordre" type="number" min="0" inputmode="numeric" placeholder="Auto" :class="champ" />
                            <InputError class="mt-1" :message="form.errors.ordre" />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.actif" type="checkbox" class="rounded border-gray-300 text-brand-600" />
                        Actif dès la création
                    </label>

                    <PrimaryButton :disabled="form.processing">Ajouter le forfait</PrimaryButton>
                </form>

                <p v-if="forfaits.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucun forfait pour le moment.
                </p>

                <ForfaitLigne v-for="f in forfaits" :key="f.id" :forfait="f" :types="types" />

                <div v-if="historique.length" class="space-y-2 rounded-lg bg-white p-4 shadow">
                    <p class="font-semibold">Dernières modifications</p>
                    <ul class="divide-y divide-gray-100 text-sm">
                        <li v-for="h in historique" :key="h.id" class="py-2">
                            <p class="font-medium text-gray-800">{{ h.forfait }}</p>
                            <p class="text-gray-600">{{ h.resume }}</p>
                            <p class="text-xs text-gray-400">{{ h.utilisateur }} · {{ date(h.date) }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
