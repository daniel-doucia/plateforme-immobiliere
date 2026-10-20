<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ alertes: Array, max: Number });

const date = (iso) => new Date(iso).toLocaleDateString('fr-FR');

const basculer = (alerte) =>
    router.patch(route('alertes.toggle', alerte.id), {}, { preserveScroll: true });

const supprimer = (alerte) => {
    if (confirm('Supprimer cette alerte ?')) {
        router.delete(route('alertes.destroy', alerte.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Mes alertes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes alertes</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="$page.props.errors.alerte" class="rounded-lg bg-red-100 p-3 text-sm text-red-800">
                    {{ $page.props.errors.alerte }}
                </p>

                <p class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">
                    Chaque matin, vous recevez un e-mail si de nouvelles annonces correspondent à une alerte active.
                    Pour en créer une, faites une recherche puis touchez « Créer une alerte ».
                    <Link :href="route('annonces.index')" class="font-semibold underline">Aller à la recherche</Link>
                </p>

                <p v-if="alertes.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Vous n'avez pas encore d'alerte.
                </p>

                <div v-for="a in alertes" :key="a.id" class="space-y-2 rounded-lg bg-white p-4 shadow">
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium text-gray-900">{{ a.resume }}</p>
                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium"
                            :class="a.actif ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ a.actif ? 'Active' : 'En pause' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">Créée le {{ date(a.creee_le) }}</p>
                    <div class="flex gap-3 text-sm">
                        <button class="text-indigo-600" @click="basculer(a)">{{ a.actif ? 'Mettre en pause' : 'Réactiver' }}</button>
                        <button class="text-red-600" @click="supprimer(a)">Supprimer</button>
                    </div>
                </div>

                <p class="text-xs text-gray-500">{{ alertes.length }} / {{ max }} alertes</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
