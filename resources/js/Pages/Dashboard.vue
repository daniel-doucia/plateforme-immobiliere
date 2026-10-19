<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const roles = computed(() => Object.values(page.props.auth.roles ?? []));
const permissions = computed(() => Object.values(page.props.auth.permissions ?? []));

const raccourcis = computed(() => {
    const liste = [
        { titre: 'Parcourir les annonces', texte: 'Louer ou acheter un bien.', route: 'annonces.index' },
    ];

    if (roles.value.includes('bailleur')) {
        liste.push(
            { titre: 'Mes annonces', texte: 'Créer, suivre et renouveler vos annonces.', route: 'bailleur.annonces.index' },
            { titre: 'Mon profil bailleur', texte: "Vérification de votre identité.", route: 'bailleur.profil.edit' },
        );
    }

    if (permissions.value.includes('annonces.moderer')) {
        liste.push({ titre: 'Modération', texte: 'Valider ou rejeter les annonces payées.', route: 'admin.moderation.index' });
    }

    if (roles.value.includes('admin')) {
        liste.push(
            { titre: 'Administration', texte: "Vue d'ensemble de la plateforme.", route: 'admin.dashboard' },
            { titre: 'Vérifications', texte: "Valider l'identité des bailleurs.", route: 'admin.verifications.index' },
            { titre: 'Villes et quartiers', texte: 'Gérer la localisation.', route: 'admin.localisation.index' },
        );
    }

    return liste;
});
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
