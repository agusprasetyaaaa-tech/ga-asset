<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    message: String,
    type: { type: String, default: 'success' }, // success, error, info
    show: Boolean
});

const emit = defineEmits(['close']);
const visible = ref(false);

const close = () => {
    visible.value = false;
    emit('close');
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        visible.value = true;
        setTimeout(() => {
            visible.value = false;
            emit('close');
        }, 3000);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white shadow-xl rounded-xl border overflow-hidden transition-all duration-300"
            :class="type === 'success' ? 'border-emerald-100' : 'border-red-100'">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg v-if="type === 'success'" class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-xs font-bold text-gray-900">
                            {{ type === 'success' ? 'Berhasil' : 'Peringatan' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 leading-relaxed font-medium">
                            {{ message }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="visible = false" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg p-1 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Progress Bar -->
            <div class="h-1 w-full bg-gray-50">
                <div class="h-full bg-emerald-500 transition-all duration-[3000ms] ease-linear"
                     :style="{ width: visible ? '0%' : '100%' }"></div>
            </div>
        </div>
    </Transition>
</template>
