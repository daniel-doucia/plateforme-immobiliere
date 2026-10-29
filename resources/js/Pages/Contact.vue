<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ contact: Object });

const envoye = ref(false);

const form = useForm({
    nom: '',
    email: '',
    telephone: '',
    sujet: '',
    message: '',
    site_web: '', // champ piège : doit rester vide
});

const envoyer = () => {
    envoye.value = false;
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            envoye.value = true;
        },
    });
};

const lienTel = (numero) => 'tel:' + numero.replace(/\s/g, '');

const champ = 'mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600';
</script>

<template>
    <Head title="Contact" />

    <PublicLayout>
        <section class="bg-brand-900 text-white">
            <div class="mx-auto max-w-4xl px-4 py-12 text-center">
                <h1 class="text-3xl font-extrabold">Contactez-nous</h1>
                <p class="mx-auto mt-3 max-w-xl text-brand-100">
                    Une question sur une annonce, sur la publication ou sur le paiement ? Écrivez-nous.
                </p>
            </div>
        </section>

        <div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 lg:grid-cols-3">
            <aside class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:col-span-1">
                <h2 class="text-lg font-bold text-brand-900">Nos coordonnées</h2>
                <div>
                    <p class="text-sm font-semibold text-gray-500">Téléphone</p>
                    <p v-for="t in contact.telephones" :key="t">
                        <a :href="lienTel(t)" class="text-brand-800 hover:underline">{{ t }}</a>
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">E-mail</p>
                    <a :href="'mailto:' + contact.email" class="break-all text-brand-800 hover:underline">{{ contact.email }}</a>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">Zone desservie</p>
                    <p class="text-gray-800">{{ contact.zone }}</p>
                </div>
            </aside>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:col-span-2">
                <p v-if="envoye" class="mb-4 rounded-xl bg-brand-50 p-4 text-brand-900" role="status">
                    Merci, votre message a bien été envoyé. Nous vous répondrons dès que possible.
                </p>

                <form class="space-y-4" novalidate @submit.prevent="envoyer">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nom" class="text-sm font-medium text-gray-700">Nom complet</label>
                            <input id="nom" v-model="form.nom" type="text" maxlength="120" required autocomplete="name" :class="champ" />
                            <InputError :message="form.errors.nom" class="mt-1" />
                        </div>
                        <div>
                            <label for="email" class="text-sm font-medium text-gray-700">E-mail</label>
                            <input id="email" v-model="form.email" type="email" maxlength="190" required autocomplete="email" :class="champ" />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="telephone" class="text-sm font-medium text-gray-700">Téléphone (facultatif)</label>
                            <input id="telephone" v-model="form.telephone" type="tel" maxlength="30" autocomplete="tel" :class="champ" />
                            <InputError :message="form.errors.telephone" class="mt-1" />
                        </div>
                        <div>
                            <label for="sujet" class="text-sm font-medium text-gray-700">Sujet</label>
                            <input id="sujet" v-model="form.sujet" type="text" maxlength="150" required :class="champ" />
                            <InputError :message="form.errors.sujet" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label for="message" class="text-sm font-medium text-gray-700">Message</label>
                        <textarea id="message" v-model="form.message" rows="6" maxlength="3000" required :class="champ"></textarea>
                        <InputError :message="form.errors.message" class="mt-1" />
                    </div>

                    <!-- Champ piège, invisible pour les humains -->
                    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                        <label for="site_web">Ne pas remplir</label>
                        <input id="site_web" v-model="form.site_web" type="text" tabindex="-1" autocomplete="off" />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-accent-500 px-6 py-2.5 font-bold text-white hover:bg-accent-600 disabled:opacity-60"
                    >
                        {{ form.processing ? 'Envoi…' : 'Envoyer le message' }}
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
