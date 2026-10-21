<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ annonce: Object, forfaits: Array });

const form = useForm({ forfait_id: props.forfaits[0]?.id ?? '' });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const submit = () => form.post(route('bailleur.commandes.store', props.annonce.id));
</script>

<template>
    <Head title="Choisir un forfait" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Choisir un forfait</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-600">
                    Annonce : <span class="font-semibold">{{ annonce.titre }}</span>
                </p>

                <p v-if="forfaits.length === 0" class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800">
                    Aucun forfait n'est disponible pour ce type de bien pour le moment.
                </p>

                <form v-else class="space-y-3" @submit.prevent="submit">
                    <label
                        v-for="f in forfaits"
                        :key="f.id"
                        class="flex cursor-pointer items-center justify-between rounded-lg border bg-white p-4 shadow-sm"
                        :class="form.forfait_id === f.id ? 'border-brand-600 ring-1 ring-brand-600' : 'border-gray-200'"
                    >
                        <span>
                            <span class="block font-semibold">{{ f.nom }}</span>
                            <span class="block text-sm text-gray-500">Visible pendant {{ f.duree_jours }} jours</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="font-semibold">{{ prix(f.prix) }}</span>
                            <input type="radio" :value="f.id" v-model="form.forfait_id" class="text-brand-600" />
                        </span>
                    </label>
                    <InputError :message="form.errors.forfait_id" />

                    <div class="flex items-center gap-4 pt-2">
                        <PrimaryButton :disabled="form.processing">Continuer</PrimaryButton>
                        <Link :href="route('bailleur.annonces.index')" class="text-sm text-gray-600 underline">Retour</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
