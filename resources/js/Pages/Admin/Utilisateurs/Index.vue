<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ utilisateurs: Object, filtre: String, recherche: String });

const q = ref(props.recherche ?? '');

const onglets = [
    { valeur: 'tous', texte: 'Tous' },
    { valeur: 'bailleurs', texte: 'Bailleurs' },
    { valeur: 'clients', texte: 'Clients' },
    { valeur: 'desactives', texte: 'Désactivés' },
];

const libellesRoles = {
    admin: 'Administrateur',
    moderateur: 'Modérateur',
    bailleur: 'Bailleur',
    client: 'Client',
};

const parametres = (filtre) => {
    const p = {};
    if (filtre !== 'tous') p.filtre = filtre;
    if (q.value) p.q = q.value;

    return p;
};

const rechercher = () =>
    router.get(route('admin.utilisateurs.index'), parametres(props.filtre), { preserveState: true, replace: true });

const enCours = ref(null);
const formDesactivation = useForm({ motif: '' });

const ouvrir = (id) => {
    formDesactivation.reset();
    formDesactivation.clearErrors();
    enCours.value = enCours.value === id ? null : id;
};

const desactiver = (id) =>
    formDesactivation.post(route('admin.utilisateurs.desactiver', id), {
        preserveScroll: true,
        onSuccess: () => {
            formDesactivation.reset();
            enCours.value = null;
        },
    });

const reactiver = (u) => {
    if (confirm(`Réactiver le compte de « ${u.nom} » ?`)) {
        router.post(route('admin.utilisateurs.reactiver', u.id), {}, { preserveScroll: true });
    }
};

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '');
</script>

<template>
    <Head title="Utilisateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Utilisateurs inscrits</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <form class="flex gap-2" @submit.prevent="rechercher">
                    <input
                        v-model="q"
                        type="search"
                        placeholder="Nom, e-mail ou téléphone"
                        class="block w-full rounded-xl border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600"
                    />
                    <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Chercher</button>
                </form>

                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="o in onglets"
                        :key="o.valeur"
                        :href="route('admin.utilisateurs.index', parametres(o.valeur))"
                        class="rounded-full px-4 py-1 text-sm"
                        :class="filtre === o.valeur ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 shadow'"
                    >
                        {{ o.texte }}
                    </Link>
                </div>

                <p v-if="utilisateurs.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucun utilisateur trouvé.
                </p>

                <article
                    v-for="u in utilisateurs.data"
                    :key="u.id"
                    class="space-y-3 rounded-xl bg-white p-5 shadow"
                    :class="u.desactive ? 'border-l-4 border-red-500' : ''"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ u.nom }}</p>
                            <p class="text-sm text-gray-600">{{ u.email }}<template v-if="u.telephone"> · {{ u.telephone }}</template></p>
                            <p class="text-xs text-gray-500">Inscrit le {{ date(u.inscrit_le) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="r in u.roles"
                                :key="r"
                                class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800"
                            >
                                {{ libellesRoles[r] ?? r }}
                            </span>
                            <span v-if="u.desactive" class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                Désactivé
                            </span>
                        </div>
                    </div>

                    <p v-if="u.desactive" class="rounded-lg bg-red-50 p-3 text-sm text-red-800">
                        Désactivé le {{ date(u.desactive_le) }}<template v-if="u.motif"> · Motif : {{ u.motif }}</template>
                    </p>

                    <div v-if="u.gerable" class="space-y-2">
                        <button
                            v-if="u.desactive"
                            type="button"
                            class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700"
                            @click="reactiver(u)"
                        >
                            Réactiver le compte
                        </button>
                        <button
                            v-else
                            type="button"
                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-600 hover:text-white"
                            @click="ouvrir(u.id)"
                        >
                            Désactiver le compte
                        </button>

                        <form v-if="enCours === u.id" class="space-y-2 rounded-lg bg-gray-50 p-3" @submit.prevent="desactiver(u.id)">
                            <label :for="'motif-' + u.id" class="text-sm font-medium text-gray-700">Motif (facultatif)</label>
                            <textarea
                                :id="'motif-' + u.id"
                                v-model="formDesactivation.motif"
                                rows="2"
                                maxlength="255"
                                class="block w-full rounded-xl border-gray-300 text-sm focus:border-brand-600 focus:ring-brand-600"
                            ></textarea>
                            <p v-if="formDesactivation.errors.motif" class="text-sm text-red-600">{{ formDesactivation.errors.motif }}</p>
                            <p class="text-xs text-gray-500">
                                La personne ne pourra plus se connecter et sera déconnectée à sa prochaine action.
                            </p>
                            <div class="flex gap-2">
                                <button
                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                                    :disabled="formDesactivation.processing"
                                >
                                    Confirmer la désactivation
                                </button>
                                <button type="button" class="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100" @click="enCours = null">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                    <p v-else class="text-xs text-gray-500">Ce compte ne peut pas être désactivé ici (administrateur ou votre propre compte).</p>
                </article>

                <div v-if="utilisateurs.last_page > 1" class="flex flex-wrap gap-1">
                    <template v-for="l in utilisateurs.links" :key="l.label">
                        <Link v-if="l.url" :href="l.url" class="rounded px-3 py-1 text-sm" :class="l.active ? 'bg-brand-600 text-white' : 'bg-white shadow'" v-html="l.label" />
                        <span v-else class="rounded px-3 py-1 text-sm text-gray-400" v-html="l.label" />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
