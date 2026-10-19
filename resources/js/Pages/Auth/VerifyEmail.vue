<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Vérification de l'e-mail" />

        <div class="mb-4 text-sm text-gray-600">
            Merci pour votre inscription ! Avant de commencer, vérifiez votre adresse e-mail en cliquant sur le lien que
            nous venons de vous envoyer. Si vous ne l'avez pas reçu, nous vous en enverrons volontiers un autre.
        </div>

        <div class="mb-4 text-sm font-medium text-green-600" v-if="verificationLinkSent">
            Un nouveau lien de vérification a été envoyé à l'adresse e-mail de votre inscription.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Renvoyer l'e-mail de vérification
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Se déconnecter
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
