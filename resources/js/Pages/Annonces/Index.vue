<script setup>
import Icone from '@/Components/Icone.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    annonces: Object,
    filtres: Object,
    villes: Array,
    types_bien: Array,
    types_offre: Array,
});

const page = usePage();
const form = reactive({ ...props.filtres });
const plus = ref(Boolean(props.filtres.q || props.filtres.chambres_min || props.filtres.tri !== 'recent'));

const iconesBien = { chambre: 'lit', appartement: 'immeuble', maison: 'maison', terrain: 'terrain', hotel: 'hotel' };

const quartiers = computed(
    () => props.villes.find((v) => v.id === Number(form.ville_id))?.quartiers ?? [],
);

// Les alertes ne gèrent pas encore le nombre de chambres
const criteres = computed(() =>
    Object.fromEntries(
        Object.entries(form).filter(
            ([cle, valeur]) => !['tri', 'chambres_min'].includes(cle) && valeur !== '' && valeur !== null,
        ),
    ),
);

const aDesCriteres = computed(() => Object.keys(criteres.value).length > 0);

const filtresActifs = computed(() =>
    Object.entries(form).some(([cle, valeur]) => cle !== 'tri' && valeur !== '' && valeur !== null),
);

const erreurs = computed(() => Object.values(page.props.errors ?? {}));

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n);

const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

const ilYa = (iso) => {
    if (!iso) return '';

    const jours = Math.floor((Date.now() - new Date(iso).getTime()) / 86400000);

    if (jours <= 0) return "aujourd'hui";
    if (jours === 1) return 'hier';
    if (jours < 30) return `il y a ${jours} jours`;

    const mois = Math.floor(jours / 30);

    return mois === 1 ? 'il y a 1 mois' : `il y a ${mois} mois`;
};

// Trois caractéristiques au plus, pour que la ligne reste lisible sur téléphone
const traits = (a) => {
    const liste = [];

    if (a.nb_chambres) liste.push({ icone: 'lit', texte: pluriel(a.nb_chambres, 'chambre') });
    if (a.nb_salles_bain) liste.push({ icone: 'baignoire', texte: `${a.nb_salles_bain} ${a.nb_salles_bain > 1 ? 'salles' : 'salle'} de bain` });
    if (a.superficie) liste.push({ icone: 'surface', texte: `${a.superficie} m²` });
    if (a.type_code === 'terrain') liste.push({ icone: 'terrain', texte: 'Terrain' });

    return liste.slice(0, 3);
};

const appliquer = () => {
    const params = Object.fromEntries(
        Object.entries(form).filter(([, v]) => v !== '' && v !== null),
    );
    router.get(route('annonces.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const reinitialiser = () => {
    Object.keys(form).forEach((k) => (form[k] = k === 'tri' ? 'recent' : ''));
    appliquer();
};

// Un second appui sur la même pastille retire le filtre
const choisirOffre = (valeur) => {
    form.type_offre = form.type_offre === valeur ? '' : valeur;
    appliquer();
};

const choisirBien = (valeur) => {
    form.type_bien = valeur === '' || form.type_bien === valeur ? '' : valeur;

    // Un terrain n'a pas de chambres : on retire ce critère pour ne pas masquer tous les terrains
    if (form.type_bien === 'terrain') {
        form.chambres_min = '';
    }

    appliquer();
};

const basculerFavori = (a) => {
    const options = { preserveScroll: true, preserveState: true };

    if (a.favori) {
        router.delete(route('favoris.destroy', a.slug), options);
    } else {
        router.post(route('favoris.store', a.slug), {}, options);
    }
};

const creerAlerte = () => router.post(route('alertes.store'), criteres.value, { preserveScroll: true });

const champ =
    'w-full rounded-xl border-gray-200 bg-white text-sm focus:border-brand-600 focus:ring-brand-600 disabled:bg-gray-50 disabled:text-gray-400';

const pastille = (actif) =>
    actif
        ? 'border-brand-700 bg-brand-700 text-white'
        : 'border-gray-300 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-700';
</script>

<template>
    <Head title="Annonces immobilières au Tchad" />

    <PublicLayout>
        <!-- Bandeau -->
        <section
            class="relative overflow-hidden bg-brand-900"
            style="background-image: url('/images/hero-n-djamena.jpg'); background-size: cover; background-position: 72% center;"
        >
            <div class="absolute inset-0 bg-gradient-to-r from-brand-950/95 via-brand-900/75 to-brand-800/15"></div>

            <div class="relative mx-auto max-w-6xl px-4 pb-32 pt-12 sm:pb-36 sm:pt-16">
                <p class="flex items-center gap-3 text-sm font-semibold uppercase tracking-wide text-accent-400">
                    <span class="h-0.5 w-8 bg-accent-500"></span>
                    Immobilier au Tchad
                </p>

                <h1 class="mt-3 max-w-xl text-4xl font-extrabold leading-tight text-white sm:text-5xl">
                    Trouvez votre logement idéal <span class="text-accent-400">au Tchad</span>
                </h1>

                <p class="mt-4 max-w-md text-base text-brand-100">
                    Chambres, appartements, maisons et terrains à louer ou à vendre.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <button
                        v-for="o in types_offre"
                        :key="o.value"
                        type="button"
                        class="flex items-center gap-2 rounded-full border-2 px-5 py-2.5 text-sm font-semibold transition"
                        :class="form.type_offre === o.value
                            ? 'border-white bg-white text-brand-900'
                            : 'border-white/60 text-white hover:bg-white/10'"
                        @click="choisirOffre(o.value)"
                    >
                        <Icone :nom="o.value === 'vente' ? 'maison' : 'accueil'" />
                        {{ o.label }}
                    </button>

                    <a
                        href="#resultats"
                        class="flex items-center gap-2 rounded-full bg-accent-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-accent-600"
                    >
                        <Icone nom="recherche" />
                        Explorer les annonces
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>

        <div class="relative z-10 mx-auto -mt-20 max-w-6xl space-y-8 px-4 pb-12 sm:-mt-24">
            <ul v-if="erreurs.length" class="space-y-1 rounded-xl bg-red-100 p-3 text-sm text-red-800">
                <li v-for="e in erreurs" :key="e">{{ e }}</li>
            </ul>

            <!-- Barre de recherche qui chevauche le bandeau -->
            <form class="space-y-5 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-black/5 sm:p-6" @submit.prevent="appliquer">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                        :class="pastille(form.type_bien === '')"
                        @click="choisirBien('')"
                    >
                        <Icone nom="accueil" taille="h-4 w-4" />
                        Tous les biens
                    </button>
                    <button
                        v-for="t in types_bien"
                        :key="t.value"
                        type="button"
                        class="flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
                        :class="pastille(form.type_bien === t.value)"
                        @click="choisirBien(t.value)"
                    >
                        <Icone :nom="iconesBien[t.value] ?? 'accueil'" taille="h-4 w-4" />
                        {{ t.label }}
                    </button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="block">
                        <span class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-600">
                            <Icone nom="epingle" taille="h-4 w-4" class="text-brand-700" /> Ville
                        </span>
                        <select v-model="form.ville_id" :class="champ" @change="form.quartier_id = ''">
                            <option value="">Toutes les villes</option>
                            <option v-for="v in villes" :key="v.id" :value="v.id">{{ v.nom }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-600">
                            <Icone nom="carte" taille="h-4 w-4" class="text-brand-700" /> Quartier
                        </span>
                        <select v-model="form.quartier_id" :class="champ" :disabled="quartiers.length === 0">
                            <option value="">
                                {{ quartiers.length ? 'Tous les quartiers' : (form.ville_id ? 'Aucun quartier' : "Choisir d'abord une ville") }}
                            </option>
                            <option v-for="q in quartiers" :key="q.id" :value="q.id">{{ q.nom }}</option>
                        </select>
                    </label>

                    <div>
                        <span class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-600">
                            <Icone nom="billets" taille="h-4 w-4" class="text-brand-700" /> Budget (FCFA)
                        </span>
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="form.prix_min" type="number" min="0" inputmode="numeric" placeholder="Min" :class="champ" />
                            <input v-model="form.prix_max" type="number" min="0" inputmode="numeric" placeholder="Max" :class="champ" />
                        </div>
                    </div>

                    <label class="block">
                        <span class="mb-1 flex items-center gap-1.5 text-xs font-medium text-gray-600">
                            <Icone nom="echange" taille="h-4 w-4" class="text-brand-700" /> Type de transaction
                        </span>
                        <select v-model="form.type_offre" :class="champ">
                            <option value="">Location ou vente</option>
                            <option v-for="o in types_offre" :key="o.value" :value="o.value">{{ o.label }}</option>
                        </select>
                    </label>
                </div>

                <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 py-3 font-bold text-white hover:bg-brand-800">
                    <Icone nom="recherche" />
                    Rechercher
                </button>

                <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <button type="button" class="font-semibold text-brand-700" @click="plus = !plus">
                        {{ plus ? '− Moins de filtres' : '+ Plus de filtres' }}
                    </button>
                    <button type="button" class="text-gray-500 underline" @click="reinitialiser">Effacer les filtres</button>
                </div>

                <div v-if="plus" class="grid gap-3 sm:grid-cols-3">
                    <input v-model="form.q" type="search" placeholder="Mot-clé (ex. villa, clôturé)" :class="champ" />
                    <select v-model="form.chambres_min" :class="champ" :disabled="form.type_bien === 'terrain'">
                        <option value="">Chambres : indifférent</option>
                        <option value="1">1 chambre et plus</option>
                        <option value="2">2 chambres et plus</option>
                        <option value="3">3 chambres et plus</option>
                        <option value="4">4 chambres et plus</option>
                    </select>
                    <select v-model="form.tri" :class="champ">
                        <option value="recent">Plus récentes</option>
                        <option value="prix_asc">Prix croissant</option>
                        <option value="prix_desc">Prix décroissant</option>
                    </select>
                </div>
            </form>

            <!-- Arguments de confiance -->
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-4 rounded-2xl bg-gray-100/70 p-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-800 text-white">
                        <Icone nom="verifie" taille="h-6 w-6" />
                    </span>
                    <p class="text-sm">
                        <span class="block font-bold text-gray-900">Bailleurs vérifiés</span>
                        <span class="text-gray-500">Identité contrôlée pour plus de sécurité.</span>
                    </p>
                </div>
                <div class="flex items-center gap-4 rounded-2xl bg-gray-100/70 p-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-accent-500 text-white">
                        <Icone nom="etoile" taille="h-6 w-6" />
                    </span>
                    <p class="text-sm">
                        <span class="block font-bold text-gray-900">Annonces modérées</span>
                        <span class="text-gray-500">Des annonces fiables et vérifiées.</span>
                    </p>
                </div>
                <div class="flex items-center gap-4 rounded-2xl bg-gray-100/70 p-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-700 text-white">
                        <Icone nom="bulle" taille="h-6 w-6" />
                    </span>
                    <p class="text-sm">
                        <span class="block font-bold text-gray-900">Contact direct</span>
                        <span class="text-gray-500">Appelez ou écrivez sur WhatsApp.</span>
                    </p>
                </div>
            </div>

            <!-- Alertes -->
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <template v-if="$page.props.auth.user">
                    <button
                        v-if="aDesCriteres"
                        class="rounded-full border border-brand-600 px-4 py-1.5 font-semibold text-brand-700 hover:bg-brand-50"
                        @click="creerAlerte"
                    >
                        🔔 Créer une alerte pour cette recherche
                    </button>
                    <span v-else class="text-gray-500">Choisissez des critères pour pouvoir créer une alerte.</span>
                    <Link :href="route('alertes.index')" class="text-brand-700 underline">Mes alertes</Link>
                </template>
                <p v-else class="text-gray-600">
                    <Link :href="route('login')" class="font-semibold text-brand-700 underline">Connectez-vous</Link>
                    pour être alerté par e-mail des nouvelles annonces et enregistrer des favoris.
                </p>
            </div>

            <!-- Résultats -->
            <div id="resultats" class="flex items-end justify-between gap-3 pt-2">
                <h2 class="flex items-center gap-3 text-2xl font-extrabold text-brand-900">
                    <span class="h-0.5 w-8 bg-accent-500"></span>
                    {{ filtresActifs ? 'Résultats' : 'Dernières annonces' }}
                    <span class="text-base font-normal text-gray-500">({{ annonces.total }})</span>
                </h2>
                <Link :href="route('annonces.index')" class="text-sm font-semibold text-accent-600 underline">Voir tout →</Link>
            </div>

            <p v-if="annonces.data.length === 0" class="rounded-2xl bg-white p-8 text-center text-gray-500 shadow-sm ring-1 ring-black/5">
                Aucune annonce ne correspond à votre recherche.
            </p>

            <div class="grid gap-6 sm:grid-cols-2">
                <article
                    v-for="a in annonces.data"
                    :key="a.slug"
                    class="group relative overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg"
                >
                    <Link :href="route('annonces.show', a.slug)" class="block">
                        <div class="relative h-52 bg-brand-50">
                            <img
                                v-if="a.photo"
                                :src="a.photo"
                                :alt="a.titre"
                                loading="lazy"
                                class="h-full w-full object-cover transition group-hover:scale-105"
                            />
                            <div v-else class="flex h-full items-center justify-center text-sm text-brand-600">Pas de photo</div>

                            <span class="absolute left-3 top-3 rounded-full bg-brand-800 px-3 py-1 text-xs font-bold text-white">
                                {{ a.type_offre }}
                            </span>

                            <span
                                v-if="a.verifie"
                                class="absolute bottom-3 right-3 flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-brand-800 shadow"
                            >
                                <Icone nom="verifie" taille="h-4 w-4" class="text-brand-700" /> Vérifié
                            </span>
                        </div>

                        <div class="space-y-2 p-4">
                            <p class="text-xl font-extrabold text-brand-800">
                                {{ prix(a.prix) }} FCFA
                                <span v-if="a.periodicite" class="text-sm font-normal text-gray-500">/ {{ a.periodicite.replace('par ', '') }}</span>
                            </p>
                            <p class="font-semibold text-gray-900">{{ a.titre }}</p>
                            <p class="flex items-center gap-1.5 text-sm text-gray-600">
                                <Icone nom="epingle" taille="h-4 w-4" class="text-accent-500" />
                                {{ a.ville }}, {{ a.quartier }}
                            </p>

                            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t border-gray-100 pt-3 text-xs text-gray-500">
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <span v-for="t in traits(a)" :key="t.texte" class="flex items-center gap-1.5">
                                        <Icone :nom="t.icone" taille="h-4 w-4" /> {{ t.texte }}
                                    </span>
                                </div>
                                <span>{{ ilYa(a.publiee_le) }}</span>
                            </div>
                        </div>
                    </Link>

                    <!-- Favori -->
                    <button
                        v-if="$page.props.auth.user"
                        type="button"
                        class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white shadow transition hover:scale-110"
                        :class="a.favori ? 'text-red-500' : 'text-gray-500'"
                        :aria-label="a.favori ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                        @click="basculerFavori(a)"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="a.favori ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
                        </svg>
                    </button>
                    <Link
                        v-else
                        :href="route('login')"
                        class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white text-gray-500 shadow transition hover:scale-110"
                        aria-label="Se connecter pour ajouter aux favoris"
                    >
                        <Icone nom="coeur" />
                    </Link>
                </article>
            </div>

            <div class="flex justify-between text-sm">
                <Link v-if="annonces.prev_page_url" :href="annonces.prev_page_url" class="font-semibold text-brand-700">← Précédent</Link>
                <span v-else />
                <Link v-if="annonces.next_page_url" :href="annonces.next_page_url" class="font-semibold text-brand-700">Suivant →</Link>
            </div>
        </div>
    </PublicLayout>
</template>
