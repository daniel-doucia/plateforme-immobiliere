<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GraphiqueAnneau from '@/Components/GraphiqueAnneau.vue';
import GraphiqueLigne from '@/Components/GraphiqueLigne.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: Object,
    annonces: Object,
    repartition: Array,
    evolution: Array,
    comptes: Object,
    revenus: Object,
    en_attente: Array,
    derniers_paiements: Array,
});

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '');

const couleurs = {
    chambre: '#2d6a4f',
    appartement: '#f0b429',
    maison: '#74c69d',
    terrain: '#1b4332',
};

const segments = computed(() =>
    props.repartition.map((r) => ({ label: r.label, valeur: r.total, couleur: couleurs[r.type] })),
);

const pastilles = computed(() => [
    { label: 'Payées', valeur: props.annonces.annonces_payees, classe: 'bg-accent-100 text-brand-950' },
    { label: 'En modération', valeur: props.annonces.en_moderation, classe: 'bg-sky-100 text-sky-900', lien: 'admin.moderation.index' },
    { label: 'Rejetées', valeur: props.annonces.rejetee, classe: 'bg-rose-100 text-rose-900' },
    { label: 'Rejets cumulés', valeur: props.annonces.rejets_historique, classe: 'bg-gray-200 text-gray-800' },
]);
</script>

<template>
    <Head title="Administration" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Administration</h2>
            <p class="text-sm text-gray-500">Vue d'ensemble de la plateforme.</p>
        </template>

        <div class="space-y-8 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Chiffres clés -->
            <section class="space-y-3">
                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="rounded-2xl bg-brand-800 p-4 text-white shadow-sm">
                        <p class="text-sm opacity-80">Annonces</p>
                        <p class="text-3xl font-extrabold">{{ annonces.total }}</p>
                        <p class="text-xs opacity-80">{{ annonces.publiee }} en ligne</p>
                    </div>

                    <Link :href="route('admin.bailleurs.index')" class="rounded-2xl bg-brand-100 p-4 text-brand-900 shadow-sm transition hover:shadow-md">
                        <p class="text-sm opacity-80">Bailleurs</p>
                        <p class="text-3xl font-extrabold">{{ comptes.bailleurs }}</p>
                        <p class="text-xs opacity-80">+{{ comptes.bailleurs_nouveaux }} ce mois</p>
                    </Link>

                    <div class="rounded-2xl bg-sky-100 p-4 text-sky-900 shadow-sm">
                        <p class="text-sm opacity-80">Clients</p>
                        <p class="text-3xl font-extrabold">{{ comptes.clients }}</p>
                        <p class="text-xs opacity-80">+{{ comptes.clients_nouveaux }} ce mois</p>
                    </div>

                    <div class="rounded-2xl bg-accent-400 p-4 text-brand-950 shadow-sm">
                        <p class="text-sm opacity-80">Revenus encaissés</p>
                        <p class="text-2xl font-extrabold">{{ prix(revenus.total) }}</p>
                        <p class="text-xs opacity-80">Ce mois : {{ prix(revenus.mois) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <component
                        :is="p.lien ? Link : 'div'"
                        v-for="p in pastilles"
                        :key="p.label"
                        v-bind="p.lien ? { href: route(p.lien) } : {}"
                        class="rounded-xl p-3 shadow-sm"
                        :class="p.classe"
                    >
                        <p class="text-2xl font-extrabold">{{ p.valeur }}</p>
                        <p class="text-xs opacity-80">{{ p.label }}</p>
                    </component>
                </div>
            </section>

            <!-- Graphiques -->
            <section class="grid gap-4 lg:grid-cols-5">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 lg:col-span-3">
                    <h3 class="font-bold text-gray-900">Évolution des annonces</h3>
                    <p class="mb-2 text-xs text-gray-500">Annonces créées par mois, sur les six derniers mois.</p>
                    <GraphiqueLigne :points="evolution" />
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 lg:col-span-2">
                    <h3 class="font-bold text-gray-900">Répartition des biens</h3>
                    <p class="mb-3 text-xs text-gray-500">Toutes les annonces, par type de bien.</p>
                    <GraphiqueAnneau :segments="segments" titre="Annonces" />
                </div>
            </section>

            <!-- Listes -->
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900">Annonces en attente de modération</h3>
                        <Link :href="route('admin.moderation.index')" class="text-sm font-semibold text-brand-700">Tout voir →</Link>
                    </div>

                    <p v-if="en_attente.length === 0" class="py-4 text-center text-sm text-gray-500">Rien à relire pour le moment.</p>

                    <ul class="divide-y divide-gray-100">
                        <li v-for="a in en_attente" :key="a.id" class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ a.titre }}</p>
                                <p class="truncate text-xs text-gray-500">{{ a.type_bien }} · {{ a.quartier }} · {{ a.bailleur }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-brand-800">{{ prix(a.prix) }}</span>
                        </li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                    <h3 class="mb-3 font-bold text-gray-900">Derniers paiements</h3>

                    <p v-if="derniers_paiements.length === 0" class="py-4 text-center text-sm text-gray-500">Aucun paiement pour le moment.</p>

                    <ul class="divide-y divide-gray-100">
                        <li v-for="p in derniers_paiements" :key="p.id" class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ p.bailleur }}</p>
                                <p class="truncate text-xs text-gray-500">{{ p.libelle }} · {{ date(p.date) }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-sm font-bold text-green-800">{{ prix(p.montant) }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- À faire et à gérer -->
            <section class="space-y-3">
                <h3 class="text-lg font-bold text-gray-900">À faire et à gérer</h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link :href="route('admin.verifications.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="text-sm text-gray-500">Bailleurs en attente de vérification</p>
                        <p class="text-3xl font-extrabold text-gray-900">{{ stats.bailleurs_en_attente }}</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Vérifier les dossiers →</p>
                    </Link>

                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5">
                        <p class="text-sm text-gray-500">Utilisateurs inscrits</p>
                        <p class="text-3xl font-extrabold text-gray-900">{{ stats.utilisateurs }}</p>
                    </div>

                    <Link :href="route('admin.bailleurs.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Bailleurs et leurs annonces</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Voir les compteurs par bailleur →</p>
                    </Link>

                    <Link :href="route('admin.forfaits.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Tarifs des forfaits</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Modifier les prix et les durées →</p>
                    </Link>

                    <Link :href="route('admin.localisation.index')" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">Villes et quartiers</p>
                        <p class="mt-1 text-sm font-semibold text-brand-700">Gérer la localisation →</p>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
