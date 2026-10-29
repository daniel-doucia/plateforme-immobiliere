<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Icone from '@/Components/Icone.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    annonce: Object,
    photos: Array,
    bailleur: Object,
    contact: Object,
    peut_voir_contact: Boolean,
    slug: String,
    est_favori: Boolean,
});

const courante = ref(0);

const prix = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';

const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

const caracteristiques = [
    props.annonce.superficie ? `${props.annonce.superficie} m²` : null,
    props.annonce.nb_pieces ? pluriel(props.annonce.nb_pieces, 'pièce') : null,
    props.annonce.nb_chambres ? pluriel(props.annonce.nb_chambres, 'chambre') : null,
    props.annonce.nb_salles_bain ? pluriel(props.annonce.nb_salles_bain, 'salle de bain') : null,
].filter(Boolean);

const date = props.annonce.publiee_le
    ? new Date(props.annonce.publiee_le).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
    : null;

const initiales = computed(() =>
    String(props.bailleur.nom ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((m) => m[0].toUpperCase())
        .join('') || '?',
);

const basculerFavori = () => {
    if (props.est_favori) {
        router.delete(route('favoris.destroy', props.slug), { preserveScroll: true });
    } else {
        router.post(route('favoris.store', props.slug), {}, { preserveScroll: true });
    }
};

const coeur = 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z';
</script>

<template>
    <Head :title="annonce.titre" />

    <PublicLayout>
        <div class="mx-auto max-w-6xl space-y-5 px-4 py-6">
            <Link :href="route('annonces.index')" class="inline-block text-sm font-medium text-brand-700 hover:underline">
                ← Toutes les annonces
            </Link>

            <p v-if="$page.props.errors.favori" class="rounded-xl bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200">
                {{ $page.props.errors.favori }}
            </p>

            <!-- Galerie -->
            <div v-if="photos.length" class="space-y-3">
                <div class="relative overflow-hidden rounded-3xl bg-gray-200">
                    <img
                        :src="photos[courante].url"
                        :alt="annonce.titre"
                        class="h-64 w-full object-cover sm:h-[28rem]"
                    />

                    <button
                        v-if="$page.props.auth.user"
                        type="button"
                        class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/95 shadow-md transition hover:scale-105"
                        :aria-label="est_favori ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                        :aria-pressed="est_favori"
                        @click="basculerFavori"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" :fill="est_favori ? '#ea580c' : 'none'" :stroke="est_favori ? '#ea580c' : '#14532d'" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                            <path :d="coeur" />
                        </svg>
                    </button>
                    <Link
                        v-else
                        :href="route('login')"
                        class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/95 shadow-md transition hover:scale-105"
                        aria-label="Connectez-vous pour ajouter aux favoris"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="#14532d" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                            <path :d="coeur" />
                        </svg>
                    </Link>

                    <span class="absolute bottom-4 left-4 rounded-full bg-black/55 px-3 py-1.5 text-sm font-medium text-white backdrop-blur">
                        {{ courante + 1 }}/{{ photos.length }} photo{{ photos.length > 1 ? 's' : '' }}
                    </span>
                </div>

                <div v-if="photos.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                    <button
                        v-for="(p, i) in photos"
                        :key="p.id"
                        type="button"
                        class="shrink-0"
                        :aria-label="'Voir la photo ' + (i + 1)"
                        @click="courante = i"
                    >
                        <img
                            :src="p.url"
                            alt=""
                            loading="lazy"
                            class="h-16 w-24 rounded-xl object-cover transition"
                            :class="i === courante ? 'ring-2 ring-accent-500' : 'opacity-70 hover:opacity-100'"
                        />
                    </button>
                </div>
            </div>
            <div v-else class="flex h-48 items-center justify-center rounded-3xl bg-gray-200 text-sm text-gray-500">
                Pas de photo
            </div>

            <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
                <!-- Colonne principale -->
                <div class="space-y-5 lg:col-span-2">
                    <div class="space-y-5 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 sm:p-8">
                        <span class="inline-block rounded-full bg-brand-800 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide text-white">
                            {{ annonce.type_offre }} · {{ annonce.type_bien }}
                        </span>

                        <div class="space-y-2">
                            <h1 class="text-2xl font-extrabold text-brand-950 sm:text-3xl">{{ annonce.titre }}</h1>
                            <p class="flex items-center gap-1.5 text-gray-600">
                                <Icone nom="epingle" taille="h-4 w-4" />
                                {{ annonce.lieu }}
                            </p>
                            <p v-if="annonce.adresse_indicative" class="text-sm text-gray-500">Repère : {{ annonce.adresse_indicative }}</p>
                        </div>

                        <div>
                            <p class="text-3xl font-extrabold text-brand-900 sm:text-4xl">
                                {{ prix(annonce.prix) }}
                                <span v-if="annonce.periodicite" class="text-base font-normal text-gray-500">/ {{ annonce.periodicite }}</span>
                            </p>
                            <p v-if="annonce.caution" class="mt-1 text-sm text-gray-600">Caution : {{ prix(annonce.caution) }}</p>
                        </div>

                        <ul v-if="caracteristiques.length" class="flex flex-wrap gap-2">
                            <li
                                v-for="c in caracteristiques"
                                :key="c"
                                class="rounded-full border border-gray-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-700"
                            >
                                {{ c }}
                            </li>
                        </ul>

                        <hr class="border-gray-100" />

                        <section class="space-y-2">
                            <h2 class="text-lg font-bold text-brand-950">À propos de ce logement</h2>
                            <p class="whitespace-pre-line leading-relaxed text-gray-700">{{ annonce.description }}</p>
                        </section>

                        <section v-if="annonce.hotel" class="space-y-4">
                            <h2 class="text-lg font-bold text-brand-950">Informations sur l’hôtel</h2>

                            <p v-if="annonce.hotel.etoiles > 0" class="flex items-center gap-2">
                                <span class="text-xl tracking-wider text-accent-500" :aria-label="annonce.hotel.etoiles + ' étoiles'">{{ '★'.repeat(annonce.hotel.etoiles) }}</span>
                                <span class="text-sm text-gray-600">{{ annonce.hotel.etoiles }} étoile{{ annonce.hotel.etoiles > 1 ? 's' : '' }}</span>
                            </p>

                            <div v-if="annonce.hotel.types_chambre.length">
                                <p class="text-sm font-semibold text-gray-700">Types de chambre</p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    <li v-for="t in annonce.hotel.types_chambre" :key="t" class="rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-900">{{ t }}</li>
                                </ul>
                            </div>

                            <div v-if="annonce.hotel.equipements.length">
                                <p class="text-sm font-semibold text-gray-700">Équipements et services</p>
                                <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                                    <li v-for="e in annonce.hotel.equipements" :key="e" class="flex items-center gap-2 text-sm text-gray-700">
                                        <svg class="h-4 w-4 shrink-0 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10" /></svg>
                                        {{ e }}
                                    </li>
                                </ul>
                            </div>

                            <p v-if="annonce.hotel.arrivee || annonce.hotel.depart" class="text-sm text-gray-700">
                                <template v-if="annonce.hotel.arrivee">Arrivée : <strong>{{ annonce.hotel.arrivee }}</strong></template>
                                <template v-if="annonce.hotel.arrivee && annonce.hotel.depart"> · </template>
                                <template v-if="annonce.hotel.depart">Départ : <strong>{{ annonce.hotel.depart }}</strong></template>
                            </p>

                            <p v-if="annonce.hotel.paiements.length" class="text-sm text-gray-700">
                                Paiements acceptés : <strong>{{ annonce.hotel.paiements.join(', ') }}</strong>
                            </p>
                        </section>

                        <dl class="divide-y divide-gray-100 border-t border-gray-100 text-sm">
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-gray-600">Type de bien</dt>
                                <dd class="font-medium text-gray-900">{{ annonce.type_bien }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-gray-600">Type d’offre</dt>
                                <dd class="font-medium text-gray-900">{{ annonce.type_offre }}</dd>
                            </div>
                            <div v-if="annonce.superficie" class="flex items-center justify-between py-3">
                                <dt class="text-gray-600">Superficie</dt>
                                <dd class="font-medium text-gray-900">{{ annonce.superficie }} m²</dd>
                            </div>
                            <div v-if="date" class="flex items-center justify-between py-3">
                                <dt class="text-gray-600">Publiée le</dt>
                                <dd class="font-medium text-gray-900">{{ date }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex items-start gap-3 rounded-2xl bg-accent-50 p-4 ring-1 ring-accent-100">
                        <svg class="mt-0.5 h-6 w-6 shrink-0 text-accent-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3 4 6v6c0 4.5 3.4 7.8 8 9 4.6-1.2 8-4.5 8-9V6l-8-3Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Annonce examinée par notre équipe</p>
                            <p class="text-sm text-gray-600">Cette annonce a été examinée par un modérateur avant sa mise en ligne.</p>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <aside class="space-y-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:sticky lg:top-24">
                    <h2 class="text-xl font-bold text-brand-950">Contacter le bailleur</h2>

                    <div class="flex items-center gap-3">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-900 text-lg font-bold text-white">
                            {{ initiales }}
                        </span>
                        <div>
                            <p class="font-semibold text-gray-900">{{ bailleur.nom }}</p>
                            <p class="text-sm text-gray-500">Bailleur</p>
                        </div>
                    </div>

                    <div v-if="contact" class="space-y-3">
                        <a
                            :href="`tel:${contact.telephone}`"
                            class="flex items-center justify-center gap-2 rounded-xl bg-brand-800 px-4 py-3 font-semibold text-white transition hover:bg-brand-900"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />
                            </svg>
                            Appeler {{ contact.telephone }}
                        </a>
                        <a
                            :href="contact.whatsapp"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-3 font-semibold text-white transition hover:bg-green-700"
                        >
                            WhatsApp
                        </a>
                    </div>

                    <p v-else-if="peut_voir_contact" class="text-sm text-gray-500">Le contact n'est pas disponible.</p>

                    <p v-else class="text-sm text-gray-600">
                        <Link :href="route('login')" class="font-semibold text-brand-700 underline">Connectez-vous</Link>
                        ou
                        <Link :href="route('register')" class="font-semibold text-brand-700 underline">créez un compte</Link>
                        gratuitement pour voir le numéro du bailleur.
                    </p>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
