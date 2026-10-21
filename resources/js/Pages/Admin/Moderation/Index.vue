<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ annonces: Object });

const rejetEnCours = ref(null);
const formRejet = useForm({ motif_rejet: '' });

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const valider = (a) => {
    if (confirm(`Publier « ${a.titre} » pour ${a.paiement.duree_jours} jours ?`)) {
        router.post(route('admin.moderation.valider', a.id), {}, {
            preserveScroll: true,
            onError: (e) => alert(e.moderation ?? 'Action impossible.'),
        });
    }
};

const rejeter = (id) => {
    formRejet.post(route('admin.moderation.rejeter', id), {
        preserveScroll: true,
        onSuccess: () => {
            formRejet.reset();
            rejetEnCours.value = null;
        },
        onError: (e) => {
            if (e.moderation) alert(e.moderation);
        },
    });
};

const detail = (a) =>
    [
        a.superficie ? `${a.superficie} m²` : null,
        a.nb_pieces != null ? `${a.nb_pieces} pièces` : null,
        a.nb_chambres != null ? `${a.nb_chambres} chambres` : null,
        a.nb_salles_bain != null ? `${a.nb_salles_bain} salles de bain` : null,
    ]
        .filter(Boolean)
        .join(' · ');
</script>

<template>
    <Head title="Modération des annonces" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Modération des annonces</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="annonces.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucune annonce en attente de modération.
                </p>

                <div v-for="a in annonces.data" :key="a.id" class="space-y-3 rounded-lg bg-white p-5 shadow">
                    <div>
                        <p class="text-lg font-semibold">{{ a.titre }}</p>
                        <p class="text-sm text-gray-600">{{ a.type_bien }} · {{ a.type_offre }} · {{ a.lieu }}</p>
                        <p class="font-medium">
                            {{ prix(a.prix) }}
                            <span v-if="a.periodicite" class="font-normal text-gray-500">{{ a.periodicite }}</span>
                        </p>
                        <p v-if="a.caution" class="text-sm text-gray-600">Caution : {{ prix(a.caution) }}</p>
                        <p v-if="detail(a)" class="text-sm text-gray-600">{{ detail(a) }}</p>
                        <p v-if="a.adresse_indicative" class="text-sm text-gray-600">Repère : {{ a.adresse_indicative }}</p>
                    </div>

                    <p class="whitespace-pre-line rounded-md bg-gray-50 p-3 text-sm text-gray-800">{{ a.description }}</p>

                    <div v-if="a.photos.length" class="flex gap-2 overflow-x-auto pb-1">
                        <a
                            v-for="p in a.photos"
                            :key="p.id"
                            :href="p.url"
                            target="_blank"
                            rel="noopener"
                            class="shrink-0"
                        >
                            <img :src="p.url" alt="" loading="lazy" class="h-24 w-32 rounded object-cover" />
                        </a>
                    </div>
                    <p v-else class="text-sm font-medium text-orange-700">Aucune photo.</p>

                    <div class="text-sm text-gray-600">
                        Bailleur : <span class="font-medium">{{ a.bailleur.nom }}</span>
                        · {{ a.bailleur.telephone }} · {{ a.bailleur.email }}
                    </div>

                    <p v-if="a.paiement" class="text-sm text-green-700">
                        Paiement reçu : {{ a.paiement.libelle }} ({{ prix(a.paiement.montant) }}),
                        publication de {{ a.paiement.duree_jours }} jours à partir de la validation.
                    </p>
                    <p v-else class="text-sm font-medium text-red-700">Aucun paiement disponible : publication impossible.</p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="a.paiement"
                            class="rounded-md bg-green-600 px-3 py-1 text-sm text-white"
                            @click="valider(a)"
                        >
                            Valider et publier
                        </button>
                        <button
                            class="rounded-md bg-red-600 px-3 py-1 text-sm text-white"
                            @click="rejetEnCours = rejetEnCours === a.id ? null : a.id"
                        >
                            Rejeter
                        </button>
                    </div>

                    <form v-if="rejetEnCours === a.id" class="space-y-2" @submit.prevent="rejeter(a.id)">
                        <textarea
                            v-model="formRejet.motif_rejet"
                            rows="2"
                            placeholder="Motif du rejet (visible par le bailleur)"
                            class="block w-full rounded-md border-gray-300 text-sm"
                        />
                        <InputError :message="formRejet.errors.motif_rejet" />
                        <button class="rounded-md bg-red-600 px-3 py-1 text-sm text-white" :disabled="formRejet.processing">
                            Confirmer le rejet
                        </button>
                    </form>
                </div>

                <div class="flex justify-between text-sm">
                    <Link v-if="annonces.prev_page_url" :href="annonces.prev_page_url" class="text-brand-600">← Précédent</Link>
                    <span v-else />
                    <Link v-if="annonces.next_page_url" :href="annonces.next_page_url" class="text-brand-600">Suivant →</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
