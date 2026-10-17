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
    <form class="mt-3 flex flex-wrap items-start gap-2" @submit.prevent="envoyer">
        <div class="min-w-[10rem] flex-1">
            <input
                v-model="form.nom"
                type="text"
                placeholder="Nouveau quartier"
                required
                class="block w-full rounded-md border-gray-300 text-sm"
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
                class="block w-20 rounded-md border-gray-300 text-sm"
            />
            <InputError class="mt-1" :message="form.errors.arrondissement" />
        </div>
        <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white" :disabled="form.processing">
            Ajouter
        </button>
    </form>
</template>
