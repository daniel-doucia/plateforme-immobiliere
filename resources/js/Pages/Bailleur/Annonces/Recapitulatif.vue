<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Etapes from '@/Components/Etapes.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    annonce: Object,
    photos: Array,
    controles: Object,
    peut: Object,
});

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

const caracteristiques = computed(() =>
    [
        props.annonce.superficie ? `${props.annonce.superficie} m²` : null,
        props.annonce.nb_pieces ? pluriel(props.annonce.nb_pieces, 'pièce') : null,
        props.annonce.nb_chambres ? pluriel(props.annonce.nb_chambres, 'chambre') : null,
        props.annonce.nb_salles_bain ? pluriel(props.annonce.nb_salles_bain, 'salle de bain') : null,
    ].filter(Boolean),
);

const verifications = computed(() => [
    {
        ok: props.controles.compte_verifie,
        texte: 'Compte bailleur vérifié',
        aide: 'Envoyez votre pièce d\'identité dans « Mon profil bailleur » : un administrateur la valide.',
        lien: route('bailleur.profil.edit'),
        action: 'Aller au profil',
    },
    {
        ok: props.controles.photo,
        texte: 'Au moins une photo',
        aide: 'Ajoutez une photo de votre bien.',
        lien: route('bailleur.annonces.photos.index', props.annonce.id),
        action: 'Ajouter des photos',
    },
]);

const soumettre = () => {
    if (confirm('Soumettre cette annonce pour publication ?')) {
        router.post(route('bailleur.annonces.soumettre', props.annonce.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Récapitulatif de l'annonce" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Récapitulatif de l'annonce</h2>
            <p class="text-sm text-gray-500">Relisez tout avant de soumettre.</p>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-5">
                <Etapes :courante="4" />

                <div v-if="photos.length" class="flex gap-2 overflow-x-auto pb-1">
                    <img
                        v-for="p in photos"
                        :key="p.id"
                        :src="p.url"
                        alt=""
                        loading="lazy"
                        class="h-28 w-36 shrink-0 rounded-xl object-cover"
                    />
                </div>

                <div class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase text-brand-700">{{ annonce.type_offre }} · {{ annonce.type_bien }}</p>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ annonce.statut_libelle }}</span>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900">{{ annonce.titre }}</h3>
                    <p class="text-gray-600">📍 {{ annonce.lieu }}</p>
                    <p v-if="annonce.adresse_indicative" class="text-sm text-gray-500">Repère : {{ annonce.adresse_indicative }}</p>

                    <p class="text-2xl font-extrabold text-brand-800">
                        {{ prix(annonce.prix) }}
                        <span v-if="annonce.periodicite" class="text-base font-normal text-gray-500">{{ annonce.periodicite }}</span>
                    </p>
                    <p v-if="annonce.caution" class="text-sm text-gray-600">Caution : {{ prix(annonce.caution) }}</p>

                    <ul v-if="caracteristiques.length" class="flex flex-wrap gap-2">
                        <li v-for="c in caracteristiques" :key="c" class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700">{{ c }}</li>
                    </ul>

                    <p class="whitespace-pre-line text-gray-800">{{ annonce.description }}</p>

                    <div v-if="peut.modifier" class="flex flex-wrap gap-4 border-t border-gray-100 pt-3 text-sm">
                        <Link :href="route('bailleur.annonces.edit', annonce.id)" class="font-semibold text-brand-700">Modifier les informations</Link>
                        <Link :href="route('bailleur.annonces.photos.index', annonce.id)" class="font-semibold text-brand-700">Modifier les photos</Link>
                    </div>
                </div>

                <!-- Brouillon : vérifications avant soumission -->
                <div v-if="controles.brouillon" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-6">
                    <p class="font-semibold text-gray-900">Avant de soumettre</p>
                    <ul class="space-y-2">
                        <li v-for="v in verifications" :key="v.texte" class="flex flex-wrap items-center gap-2 text-sm">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                :class="v.ok ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'"
                            >
                                {{ v.ok ? '✓' : '!' }}
                            </span>
                            <span :class="v.ok ? 'text-gray-700' : 'font-medium text-gray-900'">{{ v.texte }}</span>
                            <template v-if="!v.ok">
                                <span class="text-gray-500">— {{ v.aide }}</span>
                                <Link :href="v.lien" class="font-semibold text-brand-700 underline">{{ v.action }}</Link>
                            </template>
                        </li>
                    </ul>

                    <p class="text-sm text-gray-600">
                        Après la soumission, vous choisissez un forfait et vous payez. L'annonce est ensuite relue par notre équipe,
                        puis publiée.
                    </p>

                    <button
                        class="rounded-full bg-accent-500 px-6 py-2.5 text-sm font-bold text-brand-950 hover:bg-accent-400 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!peut.soumettre"
                        @click="soumettre"
                    >
                        Soumettre pour publication
                    </button>
                </div>

                <!-- Déjà soumise -->
                <div v-else class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-6">
                    <p class="font-semibold text-gray-900">Statut : {{ annonce.statut_libelle }}</p>
                    <Link
                        v-if="annonce.statut === 'en_attente_paiement'"
                        :href="route('bailleur.commandes.create', annonce.id)"
                        class="inline-block rounded-full bg-accent-500 px-6 py-2.5 text-sm font-bold text-brand-950 hover:bg-accent-400"
                    >
                        Choisir un forfait et payer →
                    </Link>
                    <p v-else-if="annonce.statut === 'en_moderation'" class="text-sm text-gray-600">
                        Votre annonce est en cours de relecture : elle sera publiée après validation.
                    </p>
                </div>

                <Link :href="route('bailleur.annonces.index')" class="inline-block text-sm text-gray-500 underline">← Mes annonces</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
