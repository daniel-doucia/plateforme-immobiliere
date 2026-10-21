<script setup>
import Icone from '@/Components/Icone.vue';
import { computed, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    icone: { type: String, default: '' },
    erreur: { type: String, default: '' },
});

const model = defineModel({ type: [String, Number], default: '' });

const visible = ref(false);
const estMotDePasse = computed(() => props.type === 'password');
const typeEffectif = computed(() => (estMotDePasse.value && visible.value ? 'text' : props.type));
</script>

<template>
    <div>
        <label :for="id" class="mb-1 block text-sm font-medium text-gray-700">{{ label }}</label>

        <div class="relative">
            <span v-if="icone" class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                <Icone :nom="icone" />
            </span>

            <input
                :id="id"
                v-model="model"
                :type="typeEffectif"
                v-bind="$attrs"
                class="block w-full rounded-xl border-gray-300 py-2.5 text-sm focus:border-brand-600 focus:ring-brand-600"
                :class="[icone ? 'pl-10' : 'pl-3', estMotDePasse ? 'pr-11' : 'pr-3', erreur ? 'border-red-400' : '']"
            />

            <button
                v-if="estMotDePasse"
                type="button"
                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
                :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                @click="visible = !visible"
            >
                <Icone :nom="visible ? 'oeilBarre' : 'oeil'" />
            </button>
        </div>

        <p v-if="erreur" class="mt-1 text-sm text-red-600">{{ erreur }}</p>
    </div>
</template>
