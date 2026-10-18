<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    annonce: Object,
    bailleur: Object,
    contact: Object,
    peut_voir_contact: Boolean,
});

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

const caracteristiques = [
    props.annonce.superficie ? `${props.annonce.superficie} m²` : null,
    props.annonce.nb_pieces ? pluriel(props.annonce.nb_pieces, 'pièce') : null,
    props.annonce.nb_chambres ? pluriel(props.annonce.nb_chambres, 'chambre') : null,
    props.annonce.nb_salles_bain ? pluriel(props.annonce.nb_salles_bain, 'salle de bain') : null,
].filter(Boolean);

const date = props.annonce.publiee_le
    ? new Date(props.annonce.publiee_le).toLocaleDateString('fr-FR')
    : null;
</script>

<template>
    <Head :title="annonce.titre" />

    <PublicLayout>
        <div class="mx-auto max-w-3xl space-y-4 px-4 py-6">
            <Link :href="route('annonces.index')" class="text-sm text-indigo-600">← Toutes les annonces</Link>

            <div class="flex h-48 items-center justify-center rounded-lg bg-gray-200 text-sm text-gray-500">
                Photos à venir
            </div>

            <div class="space-y-3 rounded-lg bg-white p-6 shadow">
                <p class="text-xs font-semibold uppercase text-indigo-600">{{ annonce.type_offre }} · {{ annonce.type_bien }}</p>
                <h1 class="text-2xl font-bold text-gray-900">{{ annonce.titre }}</h1>
                <p class="text-gray-600">{{ annonce.lieu }}</p>
                <p v-if="annonce.adresse_indicative" class="text-sm text-gray-500">Repère : {{ annonce.adresse_indicative }}</p>

                <p class="text-3xl font-bold text-gray-900">
                    {{ prix(annonce.prix) }}
                    <span v-if="annonce.periodicite" class="text-base font-normal text-gray-500">{{ annonce.periodicite }}</span>
                </p>
                <p v-if="annonce.caution" class="text-sm text-gray-600">Caution : {{ prix(annonce.caution) }}</p>

                <ul v-if="caracteristiques.length" class="flex flex-wrap gap-2">
                    <li v-for="c in caracteristiques" :key="c" class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">
                        {{ c }}
                    </li>
                </ul>

                <p class="whitespace-pre-line pt-2 text-gray-800">{{ annonce.description }}</p>
                <p v-if="date" class="text-xs text-gray-400">Publiée le {{ date }}</p>
            </div>

            <div class="space-y-3 rounded-lg bg-white p-6 shadow">
                <h2 class="font-semibold">Contacter le bailleur</h2>
                <p class="text-sm text-gray-600">{{ bailleur.nom }}</p>

                <div v-if="contact" class="flex flex-wrap gap-3">
                    <a :href="`tel:${contact.telephone}`" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                        Appeler {{ contact.telephone }}
                    </a>
                    <a
                        :href="contact.whatsapp"
                        target="_blank"
                        rel="noopener"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white"
                    >
                        WhatsApp
                    </a>
                </div>

                <p v-else-if="peut_voir_contact" class="text-sm text-gray-500">Le contact n'est pas disponible.</p>

                <p v-else class="text-sm text-gray-600">
                    <Link :href="route('login')" class="font-semibold text-indigo-600 underline">Connectez-vous</Link>
                    ou
                    <Link :href="route('register')" class="font-semibold text-indigo-600 underline">créez un compte</Link>
                    gratuitement pour voir le numéro du bailleur.
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
