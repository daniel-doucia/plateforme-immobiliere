<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ stats: { type: Object, default: null } });

const page = usePage();

const roles = computed(() => Object.values(page.props.auth.roles ?? []));
const permissions = computed(() => Object.values(page.props.auth.permissions ?? []));

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const raccourcis = computed(() => {
    const liste = [
        { titre: 'Parcourir les annonces', texte: 'Louer ou acheter un bien.', route: 'annonces.index' },
        { titre: 'Mes favoris', texte: 'Retrouver les annonces enregistrées.', route: 'favoris.index' },
        { titre: 'Mes alertes', texte: 'Être prévenu des nouvelles annonces.', route: 'alertes.index' },
    ];

    if (roles.value.includes('bailleur')) {
        liste.push(
            { titre: 'Mes annonces', texte: 'Créer, suivre et renouveler vos annonces.', route: 'bailleur.annonces.index' },
            { titre: 'Mon profil bailleur', texte: 'Vérification de votre identité.', route: 'bailleur.profil.edit' },
        );
    }

    if (permissions.value.includes('annonces.moderer')) {
        liste.push({ titre: 'Modération', texte: 'Valider ou rejeter les annonces payées.', route: 'admin.moderation.index' });
    }

    if (roles.value.includes('admin')) {
        liste.push(
            { titre: 'Administration', texte: "Vue d'ensemble de la plateforme.", route: 'admin.dashboard' },
            { titre: 'Bailleurs', texte: 'Compteurs par bailleur.', route: 'admin.bailleurs.index' },
            { titre: 'Vérifications', texte: "Valider l'identité des bailleurs.", route: 'admin.verifications.index' },
            { titre: 'Villes et quartiers', texte: 'Gérer la localisation.', route: 'admin.localisation.index' },
        );
    }

    return liste;
});

const compteurs = computed(() =>
    props_stats.value
        ? [
              { label: 'Mes annonces', valeur: props_stats.value.total },
              { label: 'Payées', valeur: props_stats.value.annonces_payees },
              { label: 'En modération', valeur: props_stats.value.en_moderation },
              { label: 'Publiées', valeur: props_stats.value.publiee },
              { label: 'Rejetées', valeur: props_stats.value.rejetee },
          ]
        : [],
);

const props_stats = computed(() => page.props.stats);
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Tableau de bord</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p class="text-gray-700">
                    Bonjour <span class="font-semibold">{{ $page.props.auth.user.name }}</span>, que souhaitez-vous faire ?
                </p>

                <div v-if="stats" class="space-y-2">
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <div v-for="c in compteurs" :key="c.label" class="rounded-lg bg-white p-4 text-center shadow">
                            <p class="text-2xl font-bold text-gray-900">{{ c.valeur }}</p>
                            <p class="text-xs text-gray-500">{{ c.label }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Montant payé au total : {{ prix(stats.montant_paye) }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <Link
                        v-for="r in raccourcis"
                        :key="r.route"
                        :href="route(r.route)"
                        class="block rounded-lg bg-white p-5 shadow transition hover:bg-gray-50"
                    >
                        <p class="font-semibold text-gray-900">{{ r.titre }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ r.texte }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
