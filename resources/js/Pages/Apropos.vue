<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Icone from '@/Components/Icone.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ contact: Object });
</script>

<template>
    <Head title="À propos" />

    <PublicLayout>
        <section class="bg-brand-900 text-white">
            <div class="mx-auto max-w-4xl px-4 py-14 text-center sm:py-20">
                <p class="text-sm font-semibold uppercase tracking-widest text-accent-400">À propos</p>
                <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">Trouvez votre chez-vous au Tchad</h1>
                <p class="mx-auto mt-4 max-w-2xl text-brand-100">
                    ImmoTchad facilite la recherche et la publication d’annonces immobilières au Tchad,
                    et la mise en relation entre locataires et bailleurs.
                </p>
            </div>
        </section>

        <div class="mx-auto max-w-5xl space-y-12 px-4 py-12">
            <section class="grid gap-6 md:grid-cols-2">
                <div class="space-y-3 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <h2 class="text-xl font-bold text-brand-900">Pour ceux qui cherchent</h2>
                    <ul class="space-y-2 text-gray-700">
                        <li>Chambres, appartements, maisons, terrains et hôtels, à louer ou à vendre.</li>
                        <li>Recherche par ville et par quartier, dans toutes les villes du Tchad.</li>
                        <li>Favoris et alertes de recherche pour ne manquer aucune nouvelle annonce.</li>
                    </ul>
                    <Link :href="route('annonces.index')" class="inline-block rounded-xl bg-brand-800 px-4 py-2 font-semibold text-white hover:bg-brand-900">
                        Voir les annonces
                    </Link>
                </div>

                <div class="space-y-3 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                    <h2 class="text-xl font-bold text-brand-900">Pour les bailleurs et les hôtels</h2>
                    <ul class="space-y-2 text-gray-700">
                        <li>Publiez vos biens avec photos, prix et description.</li>
                        <li>Chaque annonce est examinée par un modérateur avant d’être mise en ligne.</li>
                        <li>L’annonce est payée avant sa publication, selon le forfait choisi.</li>
                    </ul>
                    <Link :href="route('register')" class="inline-block rounded-xl bg-accent-500 px-4 py-2 font-semibold text-white hover:bg-accent-600">
                        Publier une annonce
                    </Link>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                <h2 class="text-xl font-bold text-brand-900">Une question ? Parlons-en</h2>
                <p class="mt-2 text-gray-700">
                    Nous desservons tout le {{ contact.zone }}. Écrivez-nous ou appelez-nous.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <Link :href="route('contact.create')" class="flex items-center gap-2 rounded-xl bg-brand-800 px-4 py-2 font-semibold text-white hover:bg-brand-900">
                        <Icone nom="plus" taille="h-4 w-4" />
                        Nous contacter
                    </Link>
                    <a :href="'mailto:' + contact.email" class="text-brand-800 underline">{{ contact.email }}</a>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
