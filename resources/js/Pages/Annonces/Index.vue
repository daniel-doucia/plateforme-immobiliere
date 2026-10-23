<script setup>
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
const plus = ref(Boolean(props.filtres.q || props.filtres.tri !== 'recent'));

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

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

const resume = (a) =>
    [a.superficie ? `${a.superficie} m²` : null, a.nb_chambres ? pluriel(a.nb_chambres, 'chambre') : null]
        .filter(Boolean)
        .join(' · ');

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

const creerAlerte = () => router.post(route('alertes.store'), criteres.value, { preserveScroll: true });

const champ =
    'w-full rounded-lg border-gray-200 bg-gray-50 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:opacity-50';

const pastille = (actif) =>
    actif
        ? 'border-brand-700 bg-brand-700 text-white'
        : 'border-gray-300 text-gray-700 hover:border-brand-500 hover:text-brand-700';
</script>

<template>
    <Head title="Annonces immobilières au Tchad" />

    <PublicLayout>
        <!-- Bandeau -->
        <section
            class="relative overflow-hidden bg-brand-900"
            style="background-image: url('/images/hero-n-djamena.jpg'); background-size: cover; background-position: 72% center;"
        >
            <div class="absolute inset-0 bg-gradient-to-r from-brand-950/85 via-brand-900/55 to-brand-800/10"></div>

            <div class="relative mx-auto max-w-6xl px-4 pb-28 pt-10 sm:pb-32 sm:pt-14">
                <p class="text-sm font-semibold uppercase tracking-wide text-accent-400">Immobilier au Tchad</p>
                <h1 class="mt-2 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-5xl">
                    Trouvez votre logement idéal <span class="text-accent-400">au Tchad</span>
                </h1>
                <p class="mt-3 max-w-xl text-base text-brand-100">
                    Appartements, maisons, chambres, terrains : achetez ou louez en toute confiance auprès de bailleurs vérifiés.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <button
                        v-for="o in types_offre"
                        :key="o.value"
                        type="button"
                        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                        :class="form.type_offre === o.value
                            ? 'border-white bg-white text-brand-800'
                            : 'border-white/50 text-white hover:bg-white/10'"
                        @click="choisirOffre(o.value)"
                    >
                        {{ o.label }}
                    </button>
                    <a href="#resultats" class="rounded-full bg-accent-500 px-4 py-1.5 text-sm font-bold text-brand-950 hover:bg-accent-400">
                        Explorer les annonces →
                    </a>
                </div>
            </div>
        </section>

        <div class="relative z-10 mx-auto -mt-16 max-w-6xl space-y-6 px-4 pb-10 sm:-mt-20">
            <ul v-if="erreurs.length" class="space-y-1 rounded-xl bg-red-100 p-3 text-sm text-red-800">
                <li v-for="e in erreurs" :key="e">{{ e }}</li>
            </ul>

            <!-- Barre de recherche qui chevauche le bandeau -->
            <form class="space-y-4 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-black/5 sm:p-5" @submit.prevent="appliquer">
                <!-- Type de bien -->
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                        :class="pastille(form.type_bien === '')"
                        @click="choisirBien('')"
                    >
                        Tous les biens
                    </button>
                    <button
                        v-for="t in types_bien"
                        :key="t.value"
                        type="button"
                        class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-semibold transition"
                        :class="pastille(form.type_bien === t.value)"
                        @click="choisirBien(t.value)"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <!-- Lieu, budget, chambres -->
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-[1fr_1fr_1.3fr_1fr_auto] lg:items-end">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-gray-500">Ville</span>
                        <select v-model="form.ville_id" :class="champ" @change="form.quartier_id = ''">
                            <option value="">Toutes les villes</option>
                            <option v-for="v in villes" :key="v.id" :value="v.id">{{ v.nom }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-gray-500">Quartier</span>
                        <select v-model="form.quartier_id" :class="champ" :disabled="quartiers.length === 0">
                            <option value="">{{ quartiers.length ? 'Tous les quartiers' : (form.ville_id ? 'Aucun quartier' : "Choisir d'abord une ville") }}</option>
                            <option v-for="q in quartiers" :key="q.id" :value="q.id">{{ q.nom }}</option>
                        </select>
                    </label>

                    <div class="col-span-2 lg:col-span-1">
                        <span class="mb-1 block text-xs font-medium text-gray-500">Budget (FCFA)</span>
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="form.prix_min" type="number" min="0" inputmode="numeric" placeholder="Min" :class="champ" />
                            <input v-model="form.prix_max" type="number" min="0" inputmode="numeric" placeholder="Max" :class="champ" />
                        </div>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-gray-500">Chambres</span>
                        <select v-model="form.chambres_min" :class="champ" :disabled="form.type_bien === 'terrain'">
                            <option value="">Indifférent</option>
                            <option value="1">1 et plus</option>
                            <option value="2">2 et plus</option>
                            <option value="3">3 et plus</option>
                            <option value="4">4 et plus</option>
                        </select>
                    </label>

                    <button class="col-span-2 rounded-xl bg-brand-800 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-900 lg:col-span-1">
                        Rechercher
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-3 text-sm">
                    <button type="button" class="font-semibold text-brand-700" @click="plus = !plus">
                        {{ plus ? '− Moins de filtres' : '+ Mot-clé et tri' }}
                    </button>
                    <button type="button" class="text-gray-500 underline" @click="reinitialiser">Effacer les filtres</button>
                </div>

                <div v-if="plus" class="grid gap-3 sm:grid-cols-2">
                    <input v-model="form.q" type="search" placeholder="Mot-clé (ex. villa, clôturé)" :class="champ" />
                    <select v-model="form.tri" :class="champ">
                        <option value="recent">Plus récentes</option>
                        <option value="prix_asc">Prix croissant</option>
                        <option value="prix_desc">Prix décroissant</option>
                    </select>
                </div>
            </form>

            <!-- Arguments de confiance -->
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-lg text-brand-700">✓</span>
                    <p class="text-sm"><span class="block font-semibold text-gray-900">Bailleurs vérifiés</span><span class="text-gray-500">Identité contrôlée</span></p>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-accent-100 text-lg text-accent-600">★</span>
                    <p class="text-sm"><span class="block font-semibold text-gray-900">Annonces modérées</span><span class="text-gray-500">Relues avant publication</span></p>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-lg text-brand-700">☎</span>
                    <p class="text-sm"><span class="block font-semibold text-gray-900">Contact direct</span><span class="text-gray-500">Appel ou WhatsApp</span></p>
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
            <div id="resultats" class="flex items-end justify-between pt-2">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ filtresActifs ? 'Résultats' : 'Dernières annonces' }}
                </h2>
                <p class="text-sm text-gray-500">{{ pluriel(annonces.total, 'annonce') }}</p>
            </div>

            <p v-if="annonces.data.length === 0" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm ring-1 ring-black/5">
                Aucune annonce ne correspond à votre recherche.
            </p>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="a in annonces.data"
                    :key="a.slug"
                    :href="route('annonces.show', a.slug)"
                    class="group block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg"
                >
                    <div class="relative aspect-[4/3] bg-brand-50">
                        <img
                            v-if="a.photo"
                            :src="a.photo"
                            :alt="a.titre"
                            loading="lazy"
                            class="h-full w-full object-cover transition group-hover:scale-105"
                        />
                        <div v-else class="flex h-full items-center justify-center text-sm text-brand-600">Pas de photo</div>

                        <span
                            class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold text-white"
                            :class="a.type_offre === 'À vendre' ? 'bg-accent-500 !text-brand-950' : 'bg-brand-600'"
                        >
                            {{ a.type_offre }}
                        </span>
                        <span class="absolute bottom-3 left-3 rounded-full bg-black/60 px-3 py-1 text-xs font-medium text-white">
                            {{ a.type_bien }}
                        </span>
                    </div>

                    <div class="space-y-1 p-4">
                        <p class="text-lg font-extrabold text-brand-800">
                            {{ prix(a.prix) }}
                            <span v-if="a.periodicite" class="text-sm font-normal text-gray-500">{{ a.periodicite }}</span>
                        </p>
                        <p class="font-semibold text-gray-900">{{ a.titre }}</p>
                        <p class="text-sm text-gray-600">📍 {{ a.lieu }}</p>
                        <p v-if="resume(a)" class="text-xs text-gray-500">{{ resume(a) }}</p>
                    </div>
                </Link>
            </div>

            <div class="flex justify-between text-sm">
                <Link v-if="annonces.prev_page_url" :href="annonces.prev_page_url" class="font-semibold text-brand-700">← Précédent</Link>
                <span v-else />
                <Link v-if="annonces.next_page_url" :href="annonces.next_page_url" class="font-semibold text-brand-700">Suivant →</Link>
            </div>
        </div>
    </PublicLayout>
</template>
