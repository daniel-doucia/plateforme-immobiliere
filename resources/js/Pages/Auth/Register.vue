<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel value="Je m'inscris en tant que" />
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label
                        class="cursor-pointer rounded-lg border p-3 text-center text-sm"
                        :class="form.type_compte === 'client' ? 'border-brand-600 bg-brand-50 font-semibold' : 'border-gray-300'"
                    >
                        <input type="radio" value="client" v-model="form.type_compte" class="sr-only" />
                        Client<br /><span class="text-xs font-normal text-gray-500">Je cherche un bien</span>
                    </label>
                    <label
                        class="cursor-pointer rounded-lg border p-3 text-center text-sm"
                        :class="form.type_compte === 'bailleur' ? 'border-brand-600 bg-brand-50 font-semibold' : 'border-gray-300'"
                    >
                        <input type="radio" value="bailleur" v-model="form.type_compte" class="sr-only" />
                        Bailleur<br /><span class="text-xs font-normal text-gray-500">Je publie des annonces</span>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.type_compte" />
            </div>

            <div class="mt-4">
                <InputLabel for="name" value="Nom complet" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="E-mail" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="telephone" value="Téléphone (ex. 66 12 34 56)" />
                <TextInput
                    id="telephone"
                    type="tel"
                    inputmode="tel"
                    class="mt-1 block w-full"
                    v-model="form.telephone"
                    required
                    autocomplete="tel"
                />
                <InputError class="mt-2" :message="form.errors.telephone" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                >
                    Déjà inscrit ?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    S'inscrire
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
