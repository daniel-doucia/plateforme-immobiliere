<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ favoris: Object });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const retirer = (favori) => {
    if (favori.slug && confirm('Retirer cette annonce de vos favoris ?')) {
        router.delete(route('favoris.destroy', favori.slug), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Mes favoris" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes favoris</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="favoris.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Vous n'avez pas encore de favori. Ouvrez une annonce et touchez « Ajouter aux favoris ».
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div v-for="f in favoris.data" :key="f.id" class="overflow-hidden rounded-lg bg-white shadow">
                        <template v-if="f.disponible">
                            <Link :href="route('annonces.show', f.slug)" class="block">
                                <div class="h-36 bg-gray-200">
                                    <img v-if="f.photo" :src="f.photo" :alt="f.titre" loading="lazy" class="h-full w-full object-cover" />
                                    <div v-else class="flex h-full items-center justify-center text-sm text-gray-500">Pas de photo</div>
                                </div>
                                <div class="space-y-1 p-4">
                                    <p class="text-xs font-semibold uppercase text-indigo-600">{{ f.type_offre }} · {{ f.type_bien }}</p>
                                    <p class="font-semibold text-gray-900">{{ f.titre }}</p>
                                    <p class="text-sm text-gray-600">{{ f.lieu }}</p>
                                    <p class="font-bold text-gray-900">
                                        {{ prix(f.prix) }}
                                        <span v-if="f.periodicite" class="text-sm font-normal text-gray-500">{{ f.periodicite }}</span>
                                    </p>
                                </div>
                            </Link>
                        </template>
                        <div v-else class="space-y-1 p-4">
                            <p class="font-semibold text-gray-700">{{ f.titre }}</p>
                            <p class="text-sm text-orange-700">Cette annonce n'est plus disponible.</p>
                        </div>

                        <div class="border-t border-gray-100 px-4 py-2 text-right">
                            <button class="text-sm text-red-600" @click="retirer(f)">Retirer des favoris</button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between text-sm">
                    <Link v-if="favoris.prev_page_url" :href="favoris.prev_page_url" class="text-indigo-600">← Précédent</Link>
                    <span v-else />
                    <Link v-if="favoris.next_page_url" :href="favoris.next_page_url" class="text-indigo-600">Suivant →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
