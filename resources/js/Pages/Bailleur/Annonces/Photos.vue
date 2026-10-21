<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Etapes from '@/Components/Etapes.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    annonce: Object,
    photos: Array,
    max: Number,
    peut_modifier: Boolean,
});

const form = useForm({ photos: [] });
const champFichier = ref(null);
const preparation = ref(false);

const restant = computed(() => props.max - props.photos.length);
const erreurs = computed(() => [...new Set(Object.values(form.errors))]);

// Réduit la photo à 1600 px maximum (JPEG) avant l'envoi : moins de données, envoi plus rapide
const reduire = async (fichier) => {
    try {
        const image = await createImageBitmap(fichier);
        const echelle = Math.min(1, 1600 / Math.max(image.width, image.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * echelle);
        canvas.height = Math.round(image.height * echelle);
        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
        image.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
        if (!blob) return fichier;

        return new File([blob], fichier.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch {
        return fichier; // le serveur reste le juge : il contrôle tout
    }
};

const choisir = async (event) => {
    const fichiers = Array.from(event.target.files ?? []).slice(0, restant.value);
    preparation.value = true;
    const prets = [];
    for (const fichier of fichiers) {
        prets.push(await reduire(fichier)); // une par une, pour ménager la mémoire de la tablette
    }
    form.photos = prets;
    preparation.value = false;
};

const envoyer = () =>
    form.post(route('bailleur.annonces.photos.store', props.annonce.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (champFichier.value) champFichier.value.value = '';
        },
    });

const supprimer = (photo) => {
    if (confirm('Supprimer cette photo ?')) {
        router.delete(route('bailleur.annonces.photos.destroy', [props.annonce.id, photo.id]), {
            preserveScroll: true,
        });
    }
};

const mettreEnPremiere = (photo) =>
    router.post(route('bailleur.annonces.photos.principale', [props.annonce.id, photo.id]), {}, {
        preserveScroll: true,
    });
</script>

<template>
    <Head title="Photos de l'annonce" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Photos de l'annonce</h2>
            <p class="text-sm text-gray-500">{{ annonce.titre }} · {{ annonce.statut_libelle }}</p>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-5">
                <Etapes :courante="3" />

                <div class="rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
                    <p class="font-semibold">Des photos qui donnent envie de vous appeler</p>
                    <p class="mt-1">
                        Prenez-les de jour, avec la lumière naturelle : la façade, chaque pièce, la douche, la cour.
                        La première photo est celle qui s'affiche dans la liste : choisissez la meilleure.
                        Évitez les photos floues, celles qui ne montrent pas le bien et celles où figure un numéro de téléphone.
                    </p>
                </div>

                <p class="text-sm text-gray-600">{{ photos.length }} / {{ max }} photos</p>

                <p v-if="photos.length === 0" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm ring-1 ring-black/5">
                    Aucune photo pour le moment.
                </p>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div v-for="(p, i) in photos" :key="p.id" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-black/5">
                        <div class="relative">
                            <img :src="p.url" alt="" loading="lazy" class="h-36 w-full object-cover" />
                            <span
                                v-if="i === 0"
                                class="absolute left-2 top-2 rounded bg-accent-500 px-2 py-0.5 text-xs font-bold text-brand-950"
                            >
                                Principale
                            </span>
                        </div>
                        <div v-if="peut_modifier" class="flex items-center justify-between p-2 text-xs">
                            <button v-if="i > 0" class="font-medium text-brand-700" @click="mettreEnPremiere(p)">
                                Mettre en première
                            </button>
                            <span v-else />
                            <button class="text-red-600" @click="supprimer(p)">Supprimer</button>
                        </div>
                    </div>
                </div>

                <form v-if="peut_modifier && restant > 0" class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-black/5" @submit.prevent="envoyer">
                    <p class="text-sm font-medium">Ajouter des photos ({{ restant }} possible{{ restant > 1 ? 's' : '' }})</p>
                    <input
                        ref="champFichier"
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm"
                        @change="choisir"
                    />
                    <p class="text-xs text-gray-500">
                        JPG, PNG ou WebP, 400 × 300 pixels minimum. Les photos sont réduites automatiquement avant l'envoi.
                    </p>

                    <ul v-if="erreurs.length" class="space-y-1 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        <li v-for="e in erreurs" :key="e">{{ e }}</li>
                    </ul>

                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing || preparation || form.photos.length === 0">
                            Envoyer {{ form.photos.length || '' }}
                        </PrimaryButton>
                        <span v-if="preparation" class="text-sm text-gray-500">Préparation des photos…</span>
                        <span v-if="form.progress" class="text-sm text-gray-500">{{ form.progress.percentage }} %</span>
                    </div>
                </form>

                <p v-else-if="peut_modifier" class="text-sm text-gray-500">Le maximum de photos est atteint.</p>
                <p v-else class="rounded-xl bg-yellow-50 p-4 text-sm text-yellow-800">
                    Les photos ne se modifient que lorsque l'annonce est en brouillon. Utilisez « Corriger » dans
                    « Mes annonces » pour la rouvrir.
                </p>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4">
                    <Link :href="route('bailleur.annonces.index')" class="text-sm text-gray-500 underline">← Mes annonces</Link>
                    <Link
                        :href="route('bailleur.annonces.recapitulatif', annonce.id)"
                        class="rounded-full bg-brand-700 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-800"
                    >
                        Continuer vers le récapitulatif →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
