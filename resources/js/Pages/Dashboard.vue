<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, default: null },
    annonces_recentes: { type: Array, default: () => [] },
    vues: { type: Number, default: 0 },
    favoris_recus: { type: Number, default: 0 },
    peut_creer: { type: Boolean, default: false },
    favoris_count: { type: Number, default: 0 },
    alertes_count: { type: Number, default: 0 },
    recommandees: { type: Array, default: () => [] },
});

const page = usePage();

const roles = computed(() => Object.values(page.props.auth.roles ?? []));
const estBailleur = computed(() => roles.value.includes('bailleur'));
const prenom = computed(() => (page.props.auth.user.name ?? '').split(' ')[0]);

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const couleurs = {
    brouillon: 'bg-gray-100 text-gray-700',
    en_attente_paiement: 'bg-yellow-100 text-yellow-800',
    en_moderation: 'bg-blue-100 text-blue-800',
    publiee: 'bg-green-100 text-green-800',
    rejetee: 'bg-red-100 text-red-800',
    expiree: 'bg-orange-100 text-orange-800',
    cloturee: 'bg-gray-200 text-gray-600',
};

const cartes = computed(() =>
    props.stats
        ? [
              { label: 'Mes annonces', valeur: props.stats.total, classe: 'bg-brand-800 text-white' },
              { label: 'En ligne', valeur: props.stats.publiee, classe: 'bg-brand-100 text-brand-900' },
              {
                  label: 'En attente',
                  valeur: props.stats.en_attente_paiement + props.stats.en_moderation,
                  classe: 'bg-accent-400 text-brand-950',
              },
              { label: 'Refusées', valeur: props.stats.rejetee, classe: 'bg-rose-100 text-rose-900' },
          ]
        : [],
);

const raccourcis = computed(() => {
    const liste = [
        { titre: 'Parcourir les annonces', texte: 'Louer ou acheter un bien.', route: 'annonces.index' },
        { titre: 'Mes favoris', texte: 'Retrouver les annonces enregistrées.', route: 'favoris.index' },
        { titre: 'Mes alertes', texte: 'Être prévenu des nouvelles annonces.', route: 'alertes.index' },
    ];

    if (estBailleur.value) {
        liste.unshift(
            { titre: 'Gérer mes annonces', texte: 'Créer, suivre et renouveler vos annonces.', route: 'bailleur.annonces.index' },
            { titre: 'Mon profil bailleur', texte: 'Vérification de votre identité.', route: 'bailleur.profil.edit' },
        );
    }

    return liste;
});
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Bonjour, {{ prenom }}</h2>
                    <p class="text-sm text-gray-500">Voici un aperçu de votre espace.</p>
                </div>
                <Link
                    v-if="estBailleur && peut_creer"
                    :href="route('bailleur.annonces.create')"
                    class="rounded-full bg-accent-500 px-5 py-2 text-sm font-bold text-brand-950 hover:bg-accent-400"
                >
                    + Ajouter une annonce
                </Link>
            </div>
        </template>

        <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Espace bailleur -->
            <template v-if="estBailleur && stats">
                <section class="space-y-3">
                    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                        <div v-for="c in cartes" :key="c.label" class="rounded-2xl p-4 shadow-sm" :class="c.classe">
                            <p class="text-3xl font-extrabold">{{ c.valeur }}</p>
                            <p class="text-sm opacity-80">{{ c.label }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-black/5">
                            <p class="text-xl font-extrabold text-gray-900">{{ stats.annonces_payees }}</p>
                            <p class="text-xs text-gray-500">Annonces payées</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-black/5">
                            <p class="text-xl font-extrabold text-gray-900">{{ vues }}</p>
                            <p class="text-xs text-gray-500">Vues de vos annonces</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-black/5">
                            <p class="text-xl font-extrabold text-gray-900">{{ favoris_recus }}</p>
                            <p class="text-xs text-gray-500">Mises en favori</p>
                        </div>
                        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-black/5">
                            <p class="text-lg font-extrabold text-gray-900">{{ prix(stats.montant_paye) }}</p>
                            <p class="text-xs text-gray-500">Montant payé au total</p>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Mes annonces</h3>
                        <Link :href="route('bailleur.annonces.index')" class="text-sm font-semibold text-brand-700">Tout gérer →</Link>
                    </div>

                    <p
                        v-if="annonces_recentes.length === 0"
                        class="rounded-xl bg-white p-6 text-center text-sm text-gray-500 shadow-sm ring-1 ring-black/5"
                    >
                        Vous n'avez pas encore d'annonce.
                    </p>

                    <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
                        <div
                            class="hidden grid-cols-[72px_1.6fr_1fr_1fr_1fr_auto] gap-3 border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs font-semibold uppercase text-gray-500 md:grid"
                        >
                            <span>Image</span>
                            <span>Titre</span>
                            <span>Type</span>
                            <span>Prix</span>
                            <span>Statut</span>
                            <span>Actions</span>
                        </div>

                        <div
                            v-for="a in annonces_recentes"
                            :key="a.id"
                            class="grid grid-cols-[72px_1fr] gap-x-3 gap-y-1 border-b border-gray-100 px-4 py-3 last:border-0 md:grid-cols-[72px_1.6fr_1fr_1fr_1fr_auto] md:items-center md:gap-3"
                        >
                            <div class="row-span-5 h-14 w-[72px] overflow-hidden rounded-lg bg-brand-50 md:row-span-1">
                                <img v-if="a.photo" :src="a.photo" alt="" loading="lazy" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full items-center justify-center text-[10px] text-brand-600">Pas de photo</div>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ a.titre }}</p>
                                <p class="truncate text-xs text-gray-500">{{ a.lieu }}</p>
                            </div>

                            <p class="col-start-2 text-sm text-gray-600 md:col-start-auto">{{ a.type_bien }}</p>

                            <p class="col-start-2 text-sm font-bold text-brand-800 md:col-start-auto">
                                {{ prix(a.prix) }}
                                <span v-if="a.periodicite" class="text-xs font-normal text-gray-500">{{ a.periodicite }}</span>
                            </p>

                            <p class="col-start-2 md:col-start-auto">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium" :class="couleurs[a.statut]">
                                    {{ a.statut_libelle }}
                                </span>
                            </p>

                            <p class="col-start-2 flex gap-3 text-sm font-semibold md:col-start-auto">
                                <Link v-if="a.slug" :href="route('annonces.show', a.slug)" class="text-brand-700">Voir</Link>
                                <Link v-else :href="route('bailleur.annonces.recapitulatif', a.id)" class="text-brand-700">Voir</Link>
                                <Link v-if="a.peut_modifier" :href="route('bailleur.annonces.edit', a.id)" class="text-gray-600">Modifier</Link>
                            </p>
                        </div>
                    </div>
                </section>
            </template>

            <!-- Espace client -->
            <template v-else>
                <section class="space-y-3">
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                        <Link :href="route('favoris.index')" class="rounded-2xl bg-brand-800 p-4 text-white shadow-sm transition hover:shadow-md">
                            <p class="text-3xl font-extrabold">{{ favoris_count }}</p>
                            <p class="text-sm opacity-80">Mes favoris</p>
                        </Link>
                        <Link :href="route('alertes.index')" class="rounded-2xl bg-accent-400 p-4 text-brand-950 shadow-sm transition hover:shadow-md">
                            <p class="text-3xl font-extrabold">{{ alertes_count }}</p>
                            <p class="text-sm opacity-80">Alertes actives</p>
                        </Link>
                        <Link :href="route('annonces.index')" class="col-span-2 rounded-2xl bg-brand-100 p-4 text-brand-900 shadow-sm transition hover:shadow-md md:col-span-1">
                            <p class="text-lg font-extrabold">Parcourir les annonces</p>
                            <p class="text-sm opacity-80">Louer ou acheter un bien →</p>
                        </Link>
                    </div>
                </section>

                <section v-if="recommandees.length" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">Annonces recommandées</h3>
                        <Link :href="route('annonces.index')" class="text-sm font-semibold text-brand-700">Voir toutes →</Link>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <Link
                            v-for="a in recommandees"
                            :key="a.slug"
                            :href="route('annonces.show', a.slug)"
                            class="group block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            <div class="relative aspect-[4/3] bg-brand-50">
                                <img v-if="a.photo" :src="a.photo" :alt="a.titre" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105" />
                                <div v-else class="flex h-full items-center justify-center text-sm text-brand-600">Pas de photo</div>
                                <span
                                    class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold text-white"
                                    :class="a.type_offre === 'À vendre' ? 'bg-accent-500 !text-brand-950' : 'bg-brand-600'"
                                >
                                    {{ a.type_offre }}
                                </span>
                            </div>
                            <div class="space-y-1 p-4">
                                <p class="text-lg font-extrabold text-brand-800">
                                    {{ prix(a.prix) }}
                                    <span v-if="a.periodicite" class="text-sm font-normal text-gray-500">{{ a.periodicite }}</span>
                                </p>
                                <p class="font-semibold text-gray-900">{{ a.titre }}</p>
                                <p class="text-sm text-gray-600">📍 {{ a.lieu }}</p>
                            </div>
                        </Link>
                    </div>
                </section>
            </template>

            <section class="space-y-3">
                <h3 class="text-lg font-bold text-gray-900">Accès rapide</h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="r in raccourcis"
                        :key="r.route"
                        :href="route(r.route)"
                        class="block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md"
                    >
                        <p class="font-semibold text-gray-900">{{ r.titre }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ r.texte }}</p>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
