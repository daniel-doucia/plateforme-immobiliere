<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    annonces: Object,
    filtres: Object,
    villes: Array,
    types_bien: Array,
    types_offre: Array,
});

const form = reactive({ ...props.filtres });

const quartiers = computed(
    () => props.villes.find((v) => v.id === Number(form.ville_id))?.quartiers ?? [],
);

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

const champ = 'rounded-md border-gray-300 text-sm';
</script>

<template>
    <Head title="Annonces immobilières au Tchad" />

    <PublicLayout>
        <div class="mx-auto max-w-6xl space-y-6 px-4 py-6">
            <form
                class="grid grid-cols-2 gap-3 rounded-lg bg-white p-4 shadow md:grid-cols-4"
                @submit.prevent="appliquer"
            >
                <input
                    v-model="form.q"
                    type="search"
                    placeholder="Rechercher (ex. villa, studio, clôturé)"
                    :class="[champ, 'col-span-2 md:col-span-4']"
                />

                <select v-model="form.type_offre" :class="champ">
                    <option value="">Louer ou acheter</option>
                    <option v-for="o in types_offre" :key="o.value" :value="o.value">{{ o.label }}</option>
                </select>

                <select v-model="form.type_bien" :class="champ">
                    <option value="">Tous les biens</option>
                    <option v-for="t in types_bien" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>

                <select v-model="form.ville_id" :class="champ" @change="form.quartier_id = ''">
                    <option value="">Toutes les villes</option>
                    <option v-for="v in villes" :key="v.id" :value="v.id">{{ v.nom }}</option>
                </select>

                <select v-model="form.quartier_id" :class="champ" :disabled="quartiers.length === 0">
                    <option value="">Tous les quartiers</option>
                    <option v-for="q in quartiers" :key="q.id" :value="q.id">{{ q.nom }}</option>
                </select>

                <input v-model="form.prix_min" type="number" min="0" inputmode="numeric" placeholder="Prix min" :class="champ" />
                <input v-model="form.prix_max" type="number" min="0" inputmode="numeric" placeholder="Prix max" :class="champ" />

                <select v-model="form.tri" :class="champ">
                    <option value="recent">Plus récentes</option>
                    <option value="prix_asc">Prix croissant</option>
                    <option value="prix_desc">Prix décroissant</option>
                </select>

                <div class="flex gap-2">
                    <button class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">
                        Rechercher
                    </button>
                    <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm" @click="reinitialiser">
                        Effacer
                    </button>
                </div>
            </form>

            <p v-if="annonces.data.length === 0" class="rounded-lg bg-white p-8 text-center text-gray-500 shadow">
                Aucune annonce ne correspond à votre recherche.
            </p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="a in annonces.data"
                    :key="a.slug"
                    :href="route('annonces.show', a.slug)"
                    class="block overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md"
                >
                    <div class="flex h-36 items-center justify-center bg-gray-200 text-sm text-gray-500">
                        Photo à venir
                    </div>
                    <div class="space-y-1 p-4">
                        <p class="text-xs font-semibold uppercase text-indigo-600">{{ a.type_offre }} · {{ a.type_bien }}</p>
                        <p class="font-semibold text-gray-900">{{ a.titre }}</p>
                        <p class="text-sm text-gray-600">{{ a.lieu }}</p>
                        <p class="font-bold text-gray-900">
                            {{ prix(a.prix) }}
                            <span v-if="a.periodicite" class="text-sm font-normal text-gray-500">{{ a.periodicite }}</span>
                        </p>
                        <p v-if="resume(a)" class="text-xs text-gray-500">{{ resume(a) }}</p>
                    </div>
                </Link>
            </div>

            <div class="flex justify-between text-sm">
                <Link v-if="annonces.prev_page_url" :href="annonces.prev_page_url" class="text-indigo-600">← Précédent</Link>
                <span v-else />
                <Link v-if="annonces.next_page_url" :href="annonces.next_page_url" class="text-indigo-600">Suivant →</Link>
            </div>
        </div>
    </PublicLayout>
</template>
