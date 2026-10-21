<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, default: null },
    annonces_recentes: { type: Array, default: () => [] },
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
              { label: 'Payées', valeur: props.stats.annonces_payees, classe: 'bg-accent-400 text-brand-950' },
              { label: 'En modération', valeur: props.stats.en_moderation, classe: 'bg-sky-100 text-sky-900' },
              { label: 'Publiées', valeur: props.stats.publiee, classe: 'bg-brand-100 text-brand-900' },
              { label: 'Rejetées', valeur: props.stats.rejetee, classe: 'bg-rose-100 text-rose-900' },
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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Bonjour, {{ prenom }}</h2>
            <p class="text-sm text-gray-500">Voici un aperçu de votre espace.</p>
        </template>

        <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <section v-if="stats" class="space-y-3">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                    <div v-for="c in cartes" :key="c.label" class="rounded-2xl p-4 shadow-sm" :class="c.classe">
                        <p class="text-3xl font-extrabold">{{ c.valeur }}</p>
                        <p class="text-sm opacity-80">{{ c.label }}</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600">
                    Montant payé au total :
                    <span class="font-semibold text-gray-900">{{ prix(stats.montant_paye) }}</span>
                </p>
            </section>

            <section v-if="estBailleur" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Mes dernières annonces</h3>
                    <Link :href="route('bailleur.annonces.index')" class="text-sm font-semibold text-brand-700">Tout voir →</Link>
                </div>

                <p
                    v-if="annonces_recentes.length === 0"
                    class="rounded-xl bg-white p-6 text-center text-sm text-gray-500 shadow-sm ring-1 ring-black/5"
                >
                    Vous n'avez pas encore d'annonce.
                </p>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="a in annonces_recentes"
                        :key="a.id"
                        :href="route('bailleur.annonces.index')"
                        class="flex gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition hover:shadow-md"
                    >
                        <div class="h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-brand-50">
                            <img v-if="a.photo" :src="a.photo" alt="" loading="lazy" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full items-center justify-center text-xs text-brand-600">Pas de photo</div>
                        </div>
                        <div class="min-w-0 space-y-1">
                            <p class="truncate font-semibold text-gray-900">{{ a.titre }}</p>
                            <p class="truncate text-xs text-gray-500">{{ a.lieu }}</p>
                            <p class="text-sm font-bold text-brand-800">
                                {{ prix(a.prix) }}
                                <span v-if="a.periodicite" class="text-xs font-normal text-gray-500">{{ a.periodicite }}</span>
                            </p>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="couleurs[a.statut]">
                                {{ a.statut_libelle }}
                            </span>
                        </div>
                    </Link>
                </div>
            </section>

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
