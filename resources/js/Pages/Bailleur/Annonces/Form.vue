<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Etapes from '@/Components/Etapes.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    annonce: Object,
    villes: Array,
    types_bien: Array,
    types_offre: Array,
    periodicites: Array,
    options_hotel: Object,
});

const a = props.annonce;
const enEdition = !!a;
const etape = ref(1);
const locales = ref({});

const form = useForm({
    quartier_id: a?.quartier_id ?? '',
    titre: a?.titre ?? '',
    description: a?.description ?? '',
    type_bien: a?.type_bien ?? 'chambre',
    type_offre: a?.type_offre ?? 'location',
    prix: a?.prix ?? '',
    periodicite: a?.periodicite ?? 'mensuel',
    caution: a?.caution ?? '',
    superficie: a?.superficie ?? '',
    nb_pieces: a?.nb_pieces ?? '',
    nb_chambres: a?.nb_chambres ?? '',
    nb_salles_bain: a?.nb_salles_bain ?? '',
    adresse_indicative: a?.adresse_indicative ?? '',
    details_hotel: {
        etoiles: a?.details_hotel?.etoiles ?? 0,
        types_chambre: a?.details_hotel?.types_chambre ?? [],
        equipements: a?.details_hotel?.equipements ?? [],
        paiements: a?.details_hotel?.paiements ?? [],
        arrivee: a?.details_hotel?.arrivee ?? '14:00',
        depart: a?.details_hotel?.depart ?? '12:00',
    },
});

// Choix en deux temps : d'abord la ville, ensuite un quartier de cette ville
const trouverVille = (quartierId) =>
    props.villes.find((v) => v.quartiers.some((q) => q.id === quartierId))?.id ?? '';

const villeId = ref(trouverVille(form.quartier_id));
const villeChoisie = computed(() => props.villes.find((v) => v.id === villeId.value) ?? null);
const quartiersDeLaVille = computed(() => villeChoisie.value?.quartiers ?? []);

// Changer de ville vide le quartier ; s'il n'y en a qu'un, on le sélectionne
watch(villeId, (nouvelle, ancienne) => {
    if (nouvelle === ancienne) return;
    const quartiers = props.villes.find((v) => v.id === nouvelle)?.quartiers ?? [];
    form.quartier_id = quartiers.length === 1 ? quartiers[0].id : '';
});

const champsEtape1 = ['type_offre', 'type_bien', 'titre', 'description'];

// Un hôtel facture à la nuit : on propose cette périodicité d'office
watch(
    () => form.type_bien,
    (type) => {
        if (type === 'hotel') form.periodicite = 'nuit';
        else if (form.periodicite === 'nuit') form.periodicite = 'mensuel';
    },
);

// Contrôles de confort côté navigateur : le serveur reste seul juge
const verifier = (numero) => {
    const e = {};

    if (numero === 1) {
        if (String(form.titre).trim().length < 10) e.titre = 'Le titre doit contenir au moins 10 caractères.';
        if (String(form.description).trim().length < 30) e.description = 'La description doit contenir au moins 30 caractères.';
    } else {
        if (!form.quartier_id) e.quartier_id = villeId.value ? 'Choisissez un quartier.' : 'Choisissez d\'abord votre ville, puis le quartier.';
        if (!(Number(form.prix) > 0)) e.prix = 'Indiquez un prix valide.';
    }

    return e;
};

const erreur = (champ) => locales.value[champ] ?? form.errors[champ];

// Première erreur serveur concernant les détails d'hôtel
const erreurHotel = computed(() => {
    const cle = Object.keys(form.errors).find((k) => k.startsWith('details_hotel'));

    return cle ? form.errors[cle] : null;
});

const groupesHotel = [
    { cle: 'types_chambre', titre: 'Types de chambre' },
    { cle: 'equipements', titre: 'Équipements et services' },
    { cle: 'paiements', titre: 'Paiements acceptés' },
];

const continuer = () => {
    locales.value = verifier(1);

    if (Object.keys(locales.value).length === 0) {
        etape.value = 2;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const retour = () => {
    locales.value = {};
    etape.value = 1;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const enregistrer = () => {
    const e1 = verifier(1);
    if (Object.keys(e1).length) {
        locales.value = e1;
        etape.value = 1;

        return;
    }

    locales.value = verifier(2);
    if (Object.keys(locales.value).length) return;

    if (enEdition) {
        form.put(route('bailleur.annonces.update', a.id));
    } else {
        form.post(route('bailleur.annonces.store'));
    }
};

const valider = () => (etape.value === 1 ? continuer() : enregistrer());

// Après une erreur du serveur, on revient à la première étape concernée
watch(
    () => form.errors,
    (erreurs) => {
        const cles = Object.keys(erreurs);
        if (cles.length) etape.value = champsEtape1.some((c) => cles.includes(c)) ? 1 : 2;
    },
);

const champ = 'mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600';
const choix = (actif) =>
    actif
        ? 'border-brand-600 bg-brand-50 font-semibold text-brand-900'
        : 'border-gray-300 text-gray-700 hover:border-brand-400';
</script>

<template>
    <Head :title="enEdition ? 'Modifier l\'annonce' : 'Nouvelle annonce'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ enEdition ? "Modifier l'annonce" : 'Nouvelle annonce' }}
            </h2>
            <p class="text-sm text-gray-500">Quelques étapes suffisent : les photos viennent juste après.</p>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-6">
                <Etapes :courante="etape" />

                <form class="space-y-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 sm:p-8" @submit.prevent="valider">
                    <!-- Étape 1 -->
                    <section v-show="etape === 1" class="space-y-5">
                        <div class="rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
                            <p class="font-semibold">Conseils pour un bon début</p>
                            <p class="mt-1">
                                Un bon titre dit l'essentiel : le type de bien, le quartier et un atout
                                (par exemple « Chambre moderne avec douche interne à Moursal »). Dans la description,
                                parlez de l'état du bien, de l'eau, de l'électricité, de la sécurité et de l'accès.
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-700">Je veux</p>
                            <div class="mt-1 grid grid-cols-2 gap-3">
                                <button
                                    v-for="o in types_offre"
                                    :key="o.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-sm transition"
                                    :class="choix(form.type_offre === o.value)"
                                    @click="form.type_offre = o.value"
                                >
                                    {{ o.label }}
                                </button>
                            </div>
                            <p v-if="erreur('type_offre')" class="mt-1 text-sm text-red-600">{{ erreur('type_offre') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-700">Type de bien</p>
                            <div class="mt-1 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <button
                                    v-for="t in types_bien"
                                    :key="t.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-sm transition"
                                    :class="choix(form.type_bien === t.value)"
                                    @click="form.type_bien = t.value"
                                >
                                    {{ t.label }}
                                </button>
                            </div>
                            <p v-if="erreur('type_bien')" class="mt-1 text-sm text-red-600">{{ erreur('type_bien') }}</p>
                        </div>

                        <div>
                            <label for="titre" class="text-sm font-medium text-gray-700">Titre de l'annonce</label>
                            <input id="titre" v-model="form.titre" type="text" maxlength="120" :class="champ" />
                            <p v-if="erreur('titre')" class="mt-1 text-sm text-red-600">{{ erreur('titre') }}</p>
                        </div>

                        <div>
                            <label for="description" class="text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" v-model="form.description" rows="6" :class="champ"></textarea>
                            <p class="mt-1 text-xs text-gray-500">{{ String(form.description).trim().length }} caractères (30 au minimum)</p>
                            <p v-if="erreur('description')" class="mt-1 text-sm text-red-600">{{ erreur('description') }}</p>
                        </div>
                    </section>

                    <!-- Étape 2 -->
                    <section v-show="etape === 2" class="space-y-5">
                        <div class="rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
                            <p class="font-semibold">Conseils sur le lieu et le prix</p>
                            <p class="mt-1">
                                Indiquez le prix réel en FCFA : c'est ce qui décide un visiteur à vous appeler.
                                Pour une location, précisez si c'est par mois ou par an, et la caution éventuelle.
                                Un repère connu (près du goudron, derrière un marché) aide les visiteurs à vous trouver.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="ville_id" class="text-sm font-medium text-gray-700">Ville</label>
                                <select id="ville_id" v-model="villeId" :class="champ">
                                    <option value="" disabled>Choisir une ville</option>
                                    <option v-for="v in villes" :key="v.id" :value="v.id">{{ v.nom }}</option>
                                </select>
                                <p v-if="!villeId && erreur('quartier_id')" class="mt-1 text-sm text-red-600">{{ erreur('quartier_id') }}</p>
                            </div>

                            <div>
                                <label for="quartier_id" class="text-sm font-medium text-gray-700">Quartier</label>
                                <select
                                    id="quartier_id"
                                    v-model="form.quartier_id"
                                    :disabled="!villeChoisie"
                                    :class="[champ, 'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400']"
                                >
                                    <option value="" disabled>
                                        {{ villeChoisie ? 'Choisir un quartier' : 'Choisissez d\u2019abord une ville' }}
                                    </option>
                                    <option v-for="q in quartiersDeLaVille" :key="q.id" :value="q.id">{{ q.nom }}</option>
                                </select>
                                <p v-if="villeId && erreur('quartier_id')" class="mt-1 text-sm text-red-600">{{ erreur('quartier_id') }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="adresse_indicative" class="text-sm font-medium text-gray-700">Repère ou adresse indicative (facultatif)</label>
                            <input id="adresse_indicative" v-model="form.adresse_indicative" type="text" :class="champ" />
                            <p v-if="erreur('adresse_indicative')" class="mt-1 text-sm text-red-600">{{ erreur('adresse_indicative') }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="prix" class="text-sm font-medium text-gray-700">Prix (FCFA)</label>
                                <input id="prix" v-model="form.prix" type="number" min="1" inputmode="numeric" :class="champ" />
                                <p v-if="erreur('prix')" class="mt-1 text-sm text-red-600">{{ erreur('prix') }}</p>
                            </div>
                            <div v-if="form.type_offre === 'location'">
                                <label for="periodicite" class="text-sm font-medium text-gray-700">Paiement</label>
                                <select id="periodicite" v-model="form.periodicite" :class="champ">
                                    <option v-for="p in periodicites" :key="p.value" :value="p.value">{{ p.label }}</option>
                                </select>
                                <p v-if="erreur('periodicite')" class="mt-1 text-sm text-red-600">{{ erreur('periodicite') }}</p>
                            </div>
                        </div>

                        <div v-if="form.type_offre === 'location'">
                            <label for="caution" class="text-sm font-medium text-gray-700">Caution (FCFA, facultatif)</label>
                            <input id="caution" v-model="form.caution" type="number" min="0" inputmode="numeric" :class="champ" />
                            <p v-if="erreur('caution')" class="mt-1 text-sm text-red-600">{{ erreur('caution') }}</p>
                        </div>

                        <div v-if="form.type_bien !== 'hotel'">
                            <label for="superficie" class="text-sm font-medium text-gray-700">Superficie (m², facultatif)</label>
                            <input id="superficie" v-model="form.superficie" type="number" min="1" inputmode="numeric" :class="champ" />
                            <p v-if="erreur('superficie')" class="mt-1 text-sm text-red-600">{{ erreur('superficie') }}</p>
                        </div>

                        <div v-if="form.type_bien !== 'terrain' && form.type_bien !== 'hotel'" class="grid grid-cols-3 gap-3">
                            <div>
                                <label for="nb_pieces" class="text-sm font-medium text-gray-700">Pièces</label>
                                <input id="nb_pieces" v-model="form.nb_pieces" type="number" min="0" inputmode="numeric" :class="champ" />
                                <p v-if="erreur('nb_pieces')" class="mt-1 text-sm text-red-600">{{ erreur('nb_pieces') }}</p>
                            </div>
                            <div>
                                <label for="nb_chambres" class="text-sm font-medium text-gray-700">Chambres</label>
                                <input id="nb_chambres" v-model="form.nb_chambres" type="number" min="0" inputmode="numeric" :class="champ" />
                                <p v-if="erreur('nb_chambres')" class="mt-1 text-sm text-red-600">{{ erreur('nb_chambres') }}</p>
                            </div>
                            <div>
                                <label for="nb_salles_bain" class="text-sm font-medium text-gray-700">Salles de bain</label>
                                <input id="nb_salles_bain" v-model="form.nb_salles_bain" type="number" min="0" inputmode="numeric" :class="champ" />
                                <p v-if="erreur('nb_salles_bain')" class="mt-1 text-sm text-red-600">{{ erreur('nb_salles_bain') }}</p>
                            </div>
                        </div>

                        <!-- Informations propres aux hôtels -->
                        <div
                            v-if="form.type_bien === 'hotel'"
                            class="space-y-5 rounded-2xl border border-brand-100 bg-brand-50/40 p-4 sm:p-5"
                        >
                            <div>
                                <p class="font-semibold text-brand-900">Informations de l’hôtel</p>
                                <p class="text-sm text-gray-600">
                                    Ces précisions aident les clients à choisir. Cochez seulement ce que vous proposez réellement.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="nb_chambres_hotel" class="text-sm font-medium text-gray-700">Nombre de chambres</label>
                                    <input id="nb_chambres_hotel" v-model="form.nb_chambres" type="number" min="0" inputmode="numeric" :class="champ" />
                                    <p v-if="erreur('nb_chambres')" class="mt-1 text-sm text-red-600">{{ erreur('nb_chambres') }}</p>
                                </div>
                                <div>
                                    <label for="etoiles" class="text-sm font-medium text-gray-700">Classement</label>
                                    <select id="etoiles" v-model.number="form.details_hotel.etoiles" :class="champ">
                                        <option :value="0">Non classé</option>
                                        <option v-for="n in 5" :key="n" :value="n">{{ n }} étoile{{ n > 1 ? 's' : '' }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-for="g in groupesHotel" :key="g.cle">
                                <p class="text-sm font-medium text-gray-700">{{ g.titre }}</p>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label
                                        v-for="o in options_hotel[g.cle]"
                                        :key="o.value"
                                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50"
                                    >
                                        <input
                                            v-model="form.details_hotel[g.cle]"
                                            type="checkbox"
                                            :value="o.value"
                                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-600"
                                        />
                                        {{ o.label }}
                                    </label>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="arrivee" class="text-sm font-medium text-gray-700">Heure d’arrivée</label>
                                    <input id="arrivee" v-model="form.details_hotel.arrivee" type="time" :class="champ" />
                                </div>
                                <div>
                                    <label for="depart" class="text-sm font-medium text-gray-700">Heure de départ</label>
                                    <input id="depart" v-model="form.details_hotel.depart" type="time" :class="champ" />
                                </div>
                            </div>

                            <p v-if="erreurHotel" class="text-sm text-red-600">{{ erreurHotel }}</p>
                        </div>
                    </section>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <div>
                            <button
                                v-if="etape === 2"
                                type="button"
                                class="rounded-full border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                @click="retour"
                            >
                                ← Retour
                            </button>
                            <Link
                                v-else
                                :href="route('bailleur.annonces.index')"
                                class="text-sm text-gray-500 underline"
                            >
                                Annuler
                            </Link>
                        </div>

                        <button
                            class="rounded-full bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{ etape === 1 ? 'Continuer →' : 'Enregistrer et ajouter les photos →' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
