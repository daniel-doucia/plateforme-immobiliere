<script setup>
import ChampAuth from '@/Components/ChampAuth.vue';
import Icone from '@/Components/Icone.vue';
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    telephone: '',
    type_compte: 'client',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const types = [
    { valeur: 'client', titre: 'Client', texte: 'Je cherche un bien' },
    { valeur: 'bailleur', titre: 'Bailleur', texte: 'Particulier, agence ou hôtel' },
];
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <p class="mb-1 text-sm font-medium text-gray-700">Je m'inscris en tant que</p>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        v-for="t in types"
                        :key="t.valeur"
                        class="cursor-pointer rounded-xl border p-3 text-center text-sm transition"
                        :class="form.type_compte === t.valeur ? 'border-brand-600 bg-brand-50 font-semibold text-brand-900' : 'border-gray-300 text-gray-700'"
                    >
                        <input v-model="form.type_compte" type="radio" :value="t.valeur" class="sr-only" />
                        {{ t.titre }}<br />
                        <span class="text-xs font-normal text-gray-500">{{ t.texte }}</span>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.type_compte" />
            </div>

            <ChampAuth
                id="name"
                v-model="form.name"
                label="Nom complet"
                icone="utilisateur"
                placeholder="Prénom et nom"
                autocomplete="name"
                required
                autofocus
                :erreur="form.errors.name"
            />

            <ChampAuth
                id="email"
                v-model="form.email"
                label="E-mail"
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
                label="Téléphone"
                type="tel"
                icone="telephone"
                placeholder="66 12 34 56"
                inputmode="tel"
                autocomplete="tel"
                required
                :erreur="form.errors.telephone"
            />

            <ChampAuth
                id="password"
                v-model="form.password"
                label="Mot de passe"
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
                label="Confirmer le mot de passe"
                type="password"
                icone="cadenas"
                placeholder="Répétez le mot de passe"
                autocomplete="new-password"
                required
                :erreur="form.errors.password_confirmation"
            />

            <button
                class="flex w-full items-center justify-between rounded-full bg-brand-700 py-2 pl-6 pr-2 font-semibold text-white transition hover:bg-brand-800 disabled:opacity-50"
                :disabled="form.processing"
            >
                S'inscrire
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-brand-950">
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
