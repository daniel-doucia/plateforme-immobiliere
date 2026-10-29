<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ messages: Object, filtre: String, non_lus: Number });

const onglets = [
    { valeur: 'non_lus', texte: 'Non lus' },
    { valeur: 'tous', texte: 'Tous' },
];

const basculer = (id) => router.patch(route('admin.messages.lu', id), {}, { preserveScroll: true });

const supprimer = (id) => {
    if (confirm('Supprimer définitivement ce message ?')) {
        router.delete(route('admin.messages.destroy', id), { preserveScroll: true });
    }
};

const date = (iso) => (iso ? new Date(iso).toLocaleString('fr-FR') : '');
</script>

<template>
    <Head title="Messages de contact" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Messages de contact
                <span v-if="non_lus" class="ml-2 rounded-full bg-accent-500 px-2 py-0.5 text-sm text-white">{{ non_lus }}</span>
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex gap-2">
                    <Link
                        v-for="o in onglets"
                        :key="o.valeur"
                        :href="route('admin.messages.index', { filtre: o.valeur })"
                        class="rounded-full px-4 py-1 text-sm"
                        :class="filtre === o.valeur ? 'bg-brand-600 text-white' : 'bg-white text-gray-700 shadow'"
                    >
                        {{ o.texte }}
                    </Link>
                </div>

                <p v-if="messages.data.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                    Aucun message.
                </p>

                <article v-for="m in messages.data" :key="m.id" class="space-y-3 rounded-lg bg-white p-5 shadow" :class="m.lu ? '' : 'border-l-4 border-accent-500'">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ m.sujet }}</p>
                            <p class="text-sm text-gray-600">
                                {{ m.nom }} ·
                                <a :href="'mailto:' + m.email" class="text-brand-700 hover:underline">{{ m.email }}</a>
                                <template v-if="m.telephone"> · {{ m.telephone }}</template>
                            </p>
                        </div>
                        <p class="text-xs text-gray-500">{{ date(m.cree_le) }}</p>
                    </div>

                    <p class="whitespace-pre-line break-words text-gray-800">{{ m.message }}</p>

                    <div class="flex gap-2">
                        <button class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700" @click="basculer(m.id)">
                            {{ m.lu ? 'Marquer non lu' : 'Marquer lu' }}
                        </button>
                        <button class="rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" @click="supprimer(m.id)">
                            Supprimer
                        </button>
                    </div>
                </article>

                <div v-if="messages.links && messages.last_page > 1" class="flex flex-wrap gap-1">
                    <template v-for="l in messages.links" :key="l.label">
                        <Link v-if="l.url" :href="l.url" class="rounded px-3 py-1 text-sm" :class="l.active ? 'bg-brand-600 text-white' : 'bg-white shadow'" v-html="l.label" />
                        <span v-else class="rounded px-3 py-1 text-sm text-gray-400" v-html="l.label" />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
