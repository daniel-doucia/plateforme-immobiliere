<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ChampAuth from '@/Components/ChampAuth.vue';
import Icone from '@/Components/Icone.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <ChampAuth
                id="email"
                v-model="form.email"
                label="E-mail"
                type="email"
                icone="mail"
                placeholder="vous@exemple.com"
                autocomplete="username"
                required
                autofocus
                :erreur="form.errors.email"
            />

            <ChampAuth
                id="password"
                v-model="form.password"
                label="Mot de passe"
                type="password"
                icone="cadenas"
                placeholder="Votre mot de passe"
                autocomplete="current-password"
                required
                :erreur="form.errors.password"
            />

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-gray-600">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    Se souvenir de moi
                </label>

                <Link v-if="canResetPassword" :href="route('password.request')" class="font-medium text-brand-700 hover:underline">
                    Mot de passe oublié ?
                </Link>
            </div>

            <button
                class="flex w-full items-center justify-between rounded-full bg-brand-700 py-2 pl-6 pr-2 font-semibold text-white transition hover:bg-brand-800 disabled:opacity-50"
                :disabled="form.processing"
            >
                Se connecter
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-500 text-brand-950">
                    <Icone nom="fleche" />
                </span>
            </button>

            <p class="text-center text-sm text-gray-600">
                Pas encore de compte ?
                <Link :href="route('register')" class="font-semibold text-brand-700 hover:underline">Créer un compte</Link>
            </p>
        </form>
    </GuestLayout>
</template>
