<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ stats: Object });
</script>

<template>
    <Head title="Administration" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Administration</h2>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow">
                    <p class="text-sm text-gray-500">Utilisateurs inscrits</p>
                    <p class="text-3xl font-bold">{{ stats.utilisateurs }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
