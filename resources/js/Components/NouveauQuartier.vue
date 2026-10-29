<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ villeId: Number });

const form = useForm({ nom: '', arrondissement: '' });

const envoyer = () =>
    form.post(route('admin.quartiers.store', props.villeId), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <form class="mt-3 flex flex-wrap items-start gap-2 rounded-xl bg-white p-3 ring-1 ring-gray-200" @submit.prevent="envoyer">
        <div class="min-w-[10rem] flex-1">
            <input
                v-model="form.nom"
                type="text"
                placeholder="Nouveau quartier"
                aria-label="Nom du nouveau quartier"
                required
                class="block w-full rounded-lg border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600"
            />
            <InputError class="mt-1" :message="form.errors.nom" />
        </div>
        <div>
            <input
                v-model="form.arrondissement"
                type="number"
                min="1"
                max="50"
                placeholder="Arr."
                aria-label="Arrondissement"
                class="block w-20 rounded-lg border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600"
            />
            <InputError class="mt-1" :message="form.errors.arrondissement" />
        </div>
        <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60" :disabled="form.processing">
            Ajouter
        </button>
    </form>
</template>
