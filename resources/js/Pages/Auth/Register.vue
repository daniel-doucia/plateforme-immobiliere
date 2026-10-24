<script setup>
import ChampAuth from '@/Components/ChampAuth.vue';
import Icone from '@/Components/Icone.vue';
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    type_compte: 'client',
    role_bailleur: 'particulier',
    nom_commercial: '',
    prenom: '',
    nom: '',
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
});

// Le serveur reçoit toujours un seul champ « name »
const submit = () => {
    form
        .transform((data) => ({ ...data, name: `${data.prenom} ${data.nom}`.trim() }))
        .post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
};

const types = [
    { valeur: 'client', titre: 'Trouver un bien', texte: 'Louer ou acheter', icone: 'recherche' },
    { valeur: 'bailleur', titre: 'Publier un bien', texte: 'Louer ou vendre', icone: 'maison' },
];

const roles = [
    { valeur: 'particulier', libelle: 'Particulier' },
    { valeur: 'agence', libelle: 'Agence ou hôtel' },
];
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <form class="space-y-5" @submit.prevent="submit">
            <!-- Que souhaitez-vous faire ? -->
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700">Que souhaitez-vous faire ?</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label
                        v-for="t in types"
                        :key="t.valeur"
                        class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 p-3 transition focus-within:ring-2 focus-within:ring-brand-600"
                        :class="form.type_compte === t.valeur ? 'border-brand-700 bg-brand-50' : 'border-gray-200 bg-white hover:border-brand-300'"
                    >
                        <input v-model="form.type_compte" type="radio" :value="t.valeur" class="sr-only" />
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="form.type_compte === t.valeur ? 'bg-brand-700 text-white' : 'bg-brand-50 text-brand-700'"
                        >
                            <Icone :nom="t.icone" />
                        </span>
                        <span>
                            <span class="block font-bold text-gray-900">{{ t.titre }}</span>
                            <span class="block text-sm text-gray-500">{{ t.texte }}</span>
                        </span>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.type_compte" />
            </div>

            <!-- Rôle du bailleur -->
            <div v-if="form.type_compte === 'bailleur'" class="space-y-3 rounded-2xl bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-700">Quel est votre rôle ?</p>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="r in roles"
                        :key="r.valeur"
                        class="cursor-pointer rounded-full border px-4 py-1.5 text-sm font-semibold transition focus-within:ring-2 focus-within:ring-brand-600"
                        :class="form.role_bailleur === r.valeur
                            ? 'border-brand-700 bg-brand-700 text-white'
                            : 'border-gray-300 bg-white text-gray-700 hover:border-brand-500'"
                    >
                        <input v-model="form.role_bailleur" type="radio" :value="r.valeur" class="sr-only" />
                        {{ r.libelle }}
                    </label>
                </div>
                <p class="text-xs text-gray-500">
                    Vous pourrez le modifier plus tard dans « Mon profil bailleur ». Les hôtels choisissent « Agence ou hôtel »
                    et publient leurs chambres à la nuit.
                </p>
                <InputError :message="form.errors.role_bailleur" />

                <ChampAuth
                    v-if="form.role_bailleur === 'agence'"
                    id="nom_commercial"
                    v-model="form.nom_commercial"
                    label="Nom de l'agence ou de l'hôtel *"
                    icone="immeuble"
                    placeholder="Nom affiché sur vos annonces"
                    autocomplete="organization"
                    :erreur="form.errors.nom_commercial"
                />
            </div>

            <!-- Identité -->
            <div class="grid gap-4 sm:grid-cols-2">
                <ChampAuth
                    id="prenom"
                    v-model="form.prenom"
                    label="Prénom *"
                    icone="utilisateur"
                    placeholder="Votre prénom"
                    autocomplete="given-name"
                    required
                />
                <ChampAuth
                    id="nom"
                    v-model="form.nom"
                    label="Nom *"
                    icone="utilisateur"
                    placeholder="Votre nom"
                    autocomplete="family-name"
                    required
                />
            </div>
            <InputError :message="form.errors.name" />

            <ChampAuth
                id="email"
                v-model="form.email"
                label="E-mail *"
                type="email"
                icone="mail"
                placeholder="vous@exemple.com"
                autocomplete="username"
                required
                :erreur="form.errors.email"
            />

            <ChampAuth
                id="telephone"
                v-model="form.telephone"
                label="Téléphone *"
                type="tel"
                icone="telephone"
                placeholder="66 12 34 56"
                inputmode="tel"
                autocomplete="tel"
                required
                :erreur="form.errors.telephone"
            />

            <!-- Mots de passe -->
            <div class="space-y-4">
                <ChampAuth
                    id="password"
                    v-model="form.password"
                    label="Mot de passe *"
                    type="password"
                    icone="cadenas"
                    placeholder="8 caractères minimum"
                    autocomplete="new-password"
                    required
                    :erreur="form.errors.password"
                />
                <ChampAuth
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Confirmer le mot de passe *"
                    type="password"
                    icone="cadenas"
                    placeholder="Répétez le mot de passe"
                    autocomplete="new-password"
                    required
                    :erreur="form.errors.password_confirmation"
                />
            </div>

            <button
                class="flex w-full items-center justify-between rounded-full bg-brand-700 py-2 pl-6 pr-2 font-semibold text-white transition hover:bg-brand-800 disabled:opacity-50"
                :disabled="form.processing"
            >
                Créer mon compte
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-white">
                    <Icone nom="fleche" />
                </span>
            </button>

            <p class="text-center text-sm text-gray-600">
                Déjà inscrit ?
                <Link :href="route('login')" class="font-semibold text-brand-700 hover:underline">Se connecter</Link>
            </p>
        </form>
    </GuestLayout>
</template>
